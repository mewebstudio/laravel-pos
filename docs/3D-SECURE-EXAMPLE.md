## 3D Secure Örnek Ödeme Kullanımı

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mews\Pos\Exception\CardTypeNotSupportedException;
use Mews\Pos\Exception\CardTypeRequiredException;
use Mews\Pos\Exception\HashMismatchException;
use Mews\Pos\Factory\CreditCardFactory;
use Mews\Pos\Gateway\PayFlexCPV4Pos;
use Mews\Pos\Gateway\PayFlexV4Pos;
use Mews\Pos\Model\Card\CreditCardInterface;
use Mews\Pos\PosInterface;

class ThreeDSecurePaymentController extends Controller
{
    private string $paymentModel = PosInterface::MODEL_3D_SECURE;

    // Tek banka örneği:
    public function __construct(
        private PosInterface $pos,
    ) {
    }

    /**
     * route: /payment/3d/form
     * Kullanıcıdan kredi kart bilgileri alıp buraya POST ediyoruz.
     */
    public function form(Request $request)
    {
        $session = $request->getSession();
    
        // START: birden fazla banka ile örnek
//        $secilenBanka = $request->get('installment') > 1 ? 'kuveytpos' : 'asseco_payten';
//        $this->pos = \Mews\LaravelPos\Facades\LaravelPos::gateway($secilenBanka);
//        $session->set('secilen_banka', $secilenBanka);
        // END: birden fazla banka ile örnek 

        $transaction = $request->get('tx', PosInterface::TX_TYPE_PAY_AUTH);

        $callbackUrl = url("/payment/3d/response");
        $order       = $this->createNewOrder(
            $this->paymentModel,
            $callbackUrl,
            $request->getClientIp(),
            $request->get('currency', PosInterface::CURRENCY_TRY),
            $request->get('installment'),
        );
        $session->set('order', $order);

        $card = $this->createCard($this->pos, $request->request->all());
        
        /**
         * PayFlex'te provizyonu (odemeyi) tamamlamak icin tekrar kredi kart bilgileri isteniyor,
         * bu yuzden kart bilgileri kaydediyoruz
         */
        if ($this->pos::class === PayFlexV4Pos::class) {
            // Laravel 8'de set() yerine put() metodu kullanmanız gerekiyor.
            $session->set('card', $request->request->all());
        }
        $session->set('tx', $transaction);

        try {
            $formData = $this->pos->get3DFormData(
                $order,
                $this->paymentModel,
                $transaction,
                $card,
                false
            );
        } catch (\Throwable $e) {
            dd($e);
        }

        if (is_array($formData) && $formData['method'] === 'GET' && $formData['inputs'] === []) {
            return redirect($formData['gateway']);
        }

        return view('redirect-form', [
            'formData' => $formData,
        ]);
    }

    /**
     * route: /payment/3d/response
     * Kullanıcı bankadan geri buraya redirect edilir.
     * Bu route için CSRF disable edilmesi gerekir.
     */
    public function response(Request $request)
    {
        $session = $request->getSession();

        // START: birden fazla banka ile örnek
        // $secilenBanka = $session->get('secilen_banka');
        // $this->pos = \Mews\LaravelPos\Facades\LaravelPos::gateway($secilenBanka);
        // END: birden fazla banka ile örnek 
        
        $transaction = $session->get('tx', PosInterface::TX_TYPE_PAY_AUTH);

        // Bankadan POST veya GET ile veri gelmesi gerekiyor.
        if (($request->getMethod() !== 'POST')
            // PayFlex-CP GET request ile cevapliyor
            && ($request->getMethod() === 'GET' && ($this->pos::class !== PayFlexCPV4Pos::class || [] === $request->query->all()))
        ) {
            return redirect('/');
        }

        $card = null;
        if ($this->pos::class === PayFlexV4Pos::class) {
            // bu gateway için ödemeyi tamamlarken tekrar kart bilgisi lazım.
            $savedCard = $session->get('card');
            $card      = $this->createCard($this->pos, $savedCard);
        }

        $order = $session->get('order');
        if (!$order) {
            throw new \Exception('Sipariş bulunamadı, session sıfırlanmış olabilir.');
        }

        // PayFlexCPV4Pos bankadan GET ile yanıt alır, diğerleri POST.
        $gatewayResponseData = $this->pos::class === PayFlexCPV4Pos::class
            ? $request->query()
            : $request->post();

        try {
            $response = $this->pos->payment($this->paymentModel, $order, $transaction, $card, $gatewayResponseData);
        } catch (HashMismatchException $e) {
            dd($request->request->all(), $request->query->all(), $e);
        } catch (\Exception|\Error $e) {
            dd($request->request->all(), $request->query->all(), $e);
        }

        // İptal, iade, sipariş durum sorgulama işlemleri yapabilmek için $response'u kaydediyoruz.
        $session->set('last_response', $response);

        if ($this->pos->isSuccess()) {
            echo 'success';
        }

        dd($response);
    }

    /**
    * Not!
    * IyzicoPos, KuveytPos ve PayTrPos alt yapılarda ekstra veriler eklenmesi gerekiyor.
    * Detaylı bilgi için mews/pos kütühanede /examples altındaki örnekleri inceleyebilirsiniz.
    */
    private function createNewOrder(
        string $paymentModel,
        string $callbackUrl,
        string $ip,
        string $currency,
        ?int   $installment = 0,
    ): array {
        $orderId = date('Ymd') . strtoupper(substr(uniqid(sha1(time())), 0, 4));

        $order = [
            'id'          => $orderId,
            'amount'      => 10.01,
            'currency'    => $currency,
            'installment' => $installment,
            'ip'          => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ip : '127.0.0.1',
        ];

        if (in_array($paymentModel, [
            PosInterface::MODEL_3D_SECURE,
            PosInterface::MODEL_3D_PAY,
            PosInterface::MODEL_3D_HOST,
            PosInterface::MODEL_3D_PAY_HOSTING,
        ], true)) {
            $order['success_url'] = $callbackUrl;
            $order['fail_url']    = $callbackUrl;
        }

        return $order;
    }

    private function createCard(PosInterface $pos, array $card): CreditCardInterface
    {
        try {
            return CreditCardFactory::createForGateway(
                $pos,
                $card['number'],
                $card['year'],
                $card['month'],
                $card['cvv'],
                $card['name'],
                $card['type'] ?? null
            );
        } catch (CardTypeRequiredException|CardTypeNotSupportedException $e) {
            dd($e);
        } catch (\LogicException $e) {
            dd($e);
        }
    }
}
```

```php
# /routes/web.php
Route::match(['POST'], '/payment/3d/form', [\App\Http\Controllers\ThreeDSecurePaymentController::class, 'form']);
Route::match(['GET','POST'], '/payment/3d/response', [\App\Http\Controllers\ThreeDSecurePaymentController::class, 'response']);
```

```html
<!--/resources/views/redirect-form.blade.php-->
@if(is_string($formData))
    {!! $formData !!}
@else
   <form method="{{ $formData['method'] }}" action="{{ $formData['gateway'] }}"  class="redirect-form" role="form">
      @foreach($formData['inputs'] as $key => $value)
      <input type="hidden" name="{{ $key }}" value="{{ $value }}">
      @endforeach
      <div class="text-center">Redirecting...</div>
      <hr>
      <div class="form-group text-center">
         <button type="submit" class="btn btn-lg btn-block btn-success">Submit</button>
      </div>
   </form>
   <script>
      document.querySelector('form.redirect-form').redirectForm.submit();

   </script>
@endif
```
