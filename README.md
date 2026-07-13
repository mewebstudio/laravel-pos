# Türk bankaları için sanal pos paketi (Laravel)

## Temel Paket
[mews/pos](https://github.com/mewebstudio/pos)

## Ana başlıklar

- [Minimum Gereksinimler](#minimum-gereksinimler)
- [Kurulum](#kurulum)
- [Gateway'lere Erişim](#gatewaylere-erişim)
- [PosQuery Kullanımı](#posquery-kullanımı)
- [Kullanım (3D Secure Ödeme)](#3d-secure-odeme-ornek-kullanim)
- [Troubelshoots](#troubleshoots)
- [Konfigurasyon Yapısı ve Örnekler](./docs/EXAMPLE_CONFIGURATIONS.md)
- [v1'den v2'ye Geçiş](./docs/UPGRADE-2.0.md)

### Minimum Gereksinimler
- PHP >= 8.0
- mews/pos ^2.0
- Laravel >= v8

### Kurulum
1. 
    ```sh
    $ composer require mews/laravel-pos
    $ php artisan vendor:publish --tag=laravel-pos
    ```

2. `/config/laravel-pos.php` ayarınızı elinizde gateway bilgileri göre güncelleyiniz.
   Örnek konfigurasyon:
    ```php
    <?php
    # /config/laravel-pos.php
    return [
        'banks' => [
            # array keyleri unique olmalıdır
            'kuveytpos' => [ # ilk sıradaki banka injection için default olur.
                'gateway_class'     => \Mews\Pos\Gateway\KuveytPos::class,
                'credentials'       => [
                    'merchant_id' => 'xxx',
                    'terminal_id' => 'yyyyyyy', // CustomerId
                    'user_name'   => 'zzzzzzz',
                    'secret_key'  => 'www123',
                ],
                'gateway_configs'   => [
                    'test_mode' => true,
                ],
                'gateway_endpoints' => [
                    'payment_api' => 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home',
                    'query_api'   => 'https://boatest.kuveytturk.com.tr/BOA.Integration.WCFService/BOA.Integration.VirtualPos/VirtualPosService.svc/Basic',
                ],
            ],
            'asseco_payten' => [
                'gateway_class'     => \Mews\Pos\Gateway\AssecoPos::class,
                'credentials'       => [
                    'merchant_id'   => '7001132146464',
                    'user_name'     => 'ISBXXXXX',
                    'user_password' => 'ISBYYYYY',
                    'secret_key'    => 'TRPZZZZZ',
                ],
                'gateway_endpoints' => [
                    'payment_api'     => 'https://entegrasyon.asseco-see.com.tr/fim/api',
                    'gateway_3d'      => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
                    'gateway_3d_host' => 'https://sanalpos.sanalakpos.com.tr/fim/est3Dgate',
                ],
            ],
        ],
    ];
    ```

3. PHP Session kullanıyorsanız 3D ödemeler için session'i alttaki şekilde ayarlamanız gerekir.
    
    **Laravel 11 ve üzeri** için environment değişkenleri şu şekilde olacak:
    ```
    SESSION_SECURE_COOKIE=true
    SESSION_SAME_SITE=Lax # ya da SESSION_SAME_SITE=None deneyiniz.
    ```
    **Laravel 10, 9, 8** için ise 
   1. Environment'da `SESSION_SECURE_COOKIE=true` yapılacak 
   2. Ve `/config/session.php`'de `same_site` değeri güncellenecek:
       ```php
       # /config/session.php:
       return [
           // ...
           'same_site' => 'lax', # ya da 'none' deneyiniz.
       ]
       ```
   _Değişikliklerden sonra var olan session'i silip yeni session oluşturunuz._

4. 3D ödemelerde bankadan websiteye geri redirect edilecek URL'larda (success/fail URL'lar) CSRF kapatılması gerekir.

   **Laravel 11 ve üzeri** `withMiddleware()` method'la ayarı yapabilirsiniz.

    ```php
        <?php
        # /bootstrap/app.php
        
        use Illuminate\Foundation\Application;
        use Illuminate\Foundation\Configuration\Exceptions;
        use Illuminate\Foundation\Configuration\Middleware;
        
        return Application::configure(basePath: dirname(__DIR__))
            // ...
            ->withMiddleware(function (Middleware $middleware) {
                $middleware->validateCsrfTokens(except: [
                    '/payment/3d/response'
                ]);
            });
    ```

    **Laravel 10, 9, 8** ise `/app/Http/Middleware/VerifyCsrfToken.php`'de ayarlayabilirsiniz.

    ```php
    <?php
    # /app/Http/Middleware/VerifyCsrfToken.php
    namespace App\Http\Middleware;
    
    use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
    
    class VerifyCsrfToken extends Middleware
    {
        protected $except = [
            // success ve fail URL'lar buraya eklenecek:
            '/payment/3d/response',
        ];
    }
    ```

###  Gateway'lere Erişim

Birden fazla banka yapılandırıldığında `GatewayRegistry` veya `LaravelPos`
facade'i ile gateway'e erişebilirsiniz:

```php
use Mews\LaravelPos\GatewayRegistry;
use Mews\LaravelPos\Facades\LaravelPos;

// Constructor injection
public function __construct(private GatewayRegistry $gatewayRegistry) {}
$pos = $this->gatewayRegistry->gateway('kuveytpos'); // PosInterface

// Facade
$pos = LaravelPos::gateway('kuveytpos');

// Tüm gateway'ler
$all = $this->gatewayRegistry->all(); // PosInterface[]
```

Tek banka yapılandırıldığında `PosInterface` doğrudan inject edilebilir:

```php
public function __construct(private \Mews\Pos\PosInterface $pos) {}
```

Bilinmeyen bir `$bankKey` verilirse `\InvalidArgumentException` fırlatılır.

### PosQuery Kullanımı

Sipariş geçmişi ve özel sorgu işlemleri için `PosQueryInterface` kullanın.
Bu servis, query desteği olan gateway'ler için otomatik olarak container'a kaydedilir.

> **Not:** `KuveytPos` ve `Param3DHostPos` query desteği sunmamaktadır.

```php
use Mews\Pos\PosQuery\PosQueryInterface;
use Mews\LaravelPos\PosQueryRegistry;

// Tek bank ya da default (ilk banka query destekliyorsa)
public function __construct(private PosQueryInterface $posQuery) {}

// Belirli bir banka
$posQuery = app('laravel-pos:query:akbank');

// Registry üzerinden
$posQuery = app(PosQueryRegistry::class)->query('akbank');

// Sipariş geçmişi
$response = $posQuery->history([
    'start_date' => new \DateTime('-1 month'),
    'end_date'   => new \DateTime(),
]);

// Ham API çağrısı
$response = $posQuery->customQuery($requestData, $apiUrl);
```

### 3D Secure Odeme Ornek Kullanim

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
      let redirectForm = document.querySelector('form.redirect-form');
      if (redirectForm) {
         redirectForm.submit();
      }
   </script>
@endif
```

### Troubleshoots

- Error: "_cURL error 60: SSL certificate problem: unable to get local issuer certificate (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://..._"
  Genellikle lokal ortamda bu sorunla karşılaşabilirsiniz. Lokal ortamınızda CA certificate bulunmadığında oluşur.
  Bu durumda sunucuda çalıştırmayı deneyiniz.


License
----

MIT
