# Türk bankaları için sanal pos paketi (Laravel)

## Temel Paket
[mews/pos](https://github.com/mewebstudio/pos)

## Ana başlıklar

- [Minimum Gereksinimler](#minimum-gereksinimler)
- [Kurulum](#kurulum)
- [Kullanım (3D Secure Ödeme)](#3d-secure-odeme-ornek-kullanim)
- [Troubelshoots](#troubleshoots)
- [Konfigurasyon Yapısı ve Örnekler](./docs/EXAMPLE_CONFIGURATIONS.md)
- [API ve 3D Form verisini degiştirme](./docs/EXAMPLE-API-ISTEK-VE-3D-FORM-VERSINI-DEGISTIRME.md)

### Minimum Gereksinimler
- PHP >= 7.4
- mews/pos ^1.6
- laravel 8, 9, 10, 11

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
            # array keyleri unique olmalıdır, bu keylerle Controller'larda su sekilde erisilebilir:
            # $this->container->get('laravel-pos:gateway:kuveytpos');
            'kuveytpos' => [ # ilk sıradaki banka injection için default olur.
                'gateway_class'     => \Mews\Pos\Gateways\KuveytPos::class,
                'test_mode'         => true,
                'lang'              => \Mews\Pos\PosInterface::LANG_TR,
                'credentials'       => [
                    'payment_model' => \Mews\Pos\PosInterface::MODEL_3D_SECURE,
                    'merchant_id'   => 'xxx',
                    'terminal_id'   => 'yyyyyyy',
                    'user_name'     => 'zzzzzzz',
                    'enc_key'       => 'www123',
                ],
                'gateway_endpoints' => [
                    'payment_api'     => 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home',
                    'gateway_3d'      => 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelPayGate',
                    'query_api'       => 'https://boatest.kuveytturk.com.tr/BOA.Integration.WCFService/BOA.Integration.VirtualPos/VirtualPosService.svc?wsdl',
                ],
            ],
            'estpos_payten' => [
                'gateway_class'     => \Mews\Pos\Gateways\EstV3Pos::class,
                'test_mode'         => true,
                'lang'              => \Mews\Pos\PosInterface::LANG_TR,
                'credentials'       => [
                    'payment_model' => \Mews\Pos\PosInterface::MODEL_3D_SECURE,
                    'merchant_id'   => '7001132146464',
                    'user_name'     => 'ISBXXXXX',
                    'user_password' => 'ISBYYYYY',
                    'enc_key'       => 'TRPZZZZZ',
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
    
    **Laravel 11** için environment değişkenleri şu şekilde olacak:
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

   **Laravel 11** `withMiddleware()` method'la ayarı yapabilirsiniz.

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

5. **KuveytPos** için API isteklere ekstra alanlar eklemeniz gerekiyor, bunun için Event Listener'ları kullanabilirsiniz. Örnek:
   
    ```php
    <?php
    # /app/Listeners/KuveytPosV2RequestDataPreparedEventListener.php:
    namespace App\Listeners;
    
    use Mews\Pos\Event\RequestDataPreparedEvent;
    
    /**
     * KuveytPos TDV2.0.0 odemenin calismasi icin zorunlu eklenmesi gereken alan var.
     */
     class KuveytPosV2RequestDataPreparedEventListener
    {
        public function __invoke(RequestDataPreparedEvent $event): void
        {
            if ($event->getGatewayClass() !== \Mews\Pos\Gateways\KuveytPos::class) {
                return;
            }
            /**
             * ekstra eklenmesi gereken verileri isteseniz $order icine ekleyip sonra o verilere
             * $event->getOrder() ile erisebilirsiniz.
             */
            $additionalRequestDataForKuveyt = [
                'DeviceData'     => [
                    'DeviceChannel' => '02',
                ],
                'CardHolderData' => [
                    'BillAddrCity'     => 'İstanbul',
                    'BillAddrCountry'  => '792',
                    'BillAddrLine1'    => 'XXX Mahallesi XXX Caddesi No 55 Daire 1',
                    'BillAddrPostCode' => '34000',
                    'BillAddrState'    => '40',
                    'Email'            => 'xxxxx@gmail.com',
                    'MobilePhone'      => [
                        'Cc'         => '90',
                        'Subscriber' => '5554567899',
                    ],
                ],
            ];
            $requestData                    = $event->getRequestData();
            $requestData                    = \array_merge_recursive($requestData, $additionalRequestDataForKuveyt);
            $event->setRequestData($requestData);
        }
    }
    ```

    Sonra bu yeni Listener'i `AppServiceProvider`'da register etmeniz gerekiyor.
    
    ```php
    # /app/Providers/AppServiceProvider.php
    namespace App\Providers;
    
    class AppServiceProvider extends ServiceProvider
    {
        public function boot(): void
        {
            // ...
            \Illuminate\Support\Facades\Event::listen(
                \Mews\Pos\Event\RequestDataPreparedEvent::class,
                \App\Listeners\KuveytPosV2RequestDataPreparedEventListener::class
            );
        }
    }
    ```



### 3D Secure Odeme Ornek Kullanim

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Mews\Pos\Entity\Card\CreditCardInterface;
use Mews\Pos\Exceptions\CardTypeNotSupportedException;
use Mews\Pos\Exceptions\CardTypeRequiredException;
use Mews\Pos\Exceptions\HashMismatchException;
use Mews\Pos\Factory\CreditCardFactory;
use Mews\Pos\Gateways\PayFlexV4Pos;
use Mews\Pos\PosInterface;

class ThreeDSecurePaymentController extends Controller
{
    private string $paymentModel = PosInterface::MODEL_3D_SECURE;

    // Tek banka örnegi:
    // Tek banka anlamı /config/laravel-pos.php'de sadece bir banka tanımlanmış olmasıdır.
    public function __construct(
        private PosInterface $pos,
    ) {
    }
    
    // START: birden fazla banka ile örnek:
//    public function __construct(
//        private Container $container,
//    ) {}
//    
//    private function getPosService(string $bank): PosInterface
//    {
//        return $this->container->get('laravel-pos:gateway:'.$bank);
//    }
    // END: birden fazla banka ile örnek

    /**
     * route: /payment/3d/form
     * Kullanicidan kredi kart bilgileri alip buraya POST ediyoruz
     */
    public function form(Request $request)
    {
        $session = $request->getSession();
    
        // START: birden fazla banka ile örnek
//        $secilenBanka = $request->get('installment') > 1 ? 'kuveytpos' : 'estpos_payten';
//        $this->pos = $this->posService($secilenBanka);
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
            /**
            * MODEL_3D_SECURE veya MODEL_3D_PAY ödemelerde kredi kart verileri olmadan
            * form verisini oluşturmak için true yapabilirsiniz.
            * Yine de bazı gatewaylerde kartsız form verisi oluşturulamıyor.
            */
            false
            );
        } catch (\Throwable $e) {
            dd($e);
        }

        return view('redirect-form', [
            'formData' => $formData,
        ]);
    }

    /**
     * route: /payment/3d/response
     * Kullanici bankadan geri buraya redirect edilir.
     * Bu route icin CSRF disable edilmesi gerekiyor.
     */
    public function response(Request $request)
    {
        $session = $request->getSession();

        // START: birden fazla banka ile örnek
        // $this->pos = $this->posService($session->get('secilen_banka'));
        // END: birden fazla banka ile örnek 
        
        $transaction = $session->get('tx', PosInterface::TX_TYPE_PAY_AUTH);

        // bankadan POST veya GET ile veri gelmesi gerekiyor
        if (($request->getMethod() !== 'POST')
            // PayFlex-CP GET request ile cevapliyor
            && ($request->getMethod() === 'GET' && ($this->pos::class !== \Mews\Pos\Gateways\PayFlexCPV4Pos::class || [] === $request->query->all()))
        ) {
            return redirect('/');
        }

        $card = null;
        if ($this->pos::class === \Mews\Pos\Gateways\PayFlexV4Pos::class) {
            // bu gateway için ödemeyi tamamlarken tekrar kart bilgisi lazım.
            $savedCard = $session->get('card');
            $card      = $this->createCard($this->pos, $savedCard);
        }

        $order = $session->get('order');
        if (!$order) {
            throw new \Exception('Sipariş bulunamadı, session sıfırlanmış olabilir.');
        }

        try {
            $this->pos->payment($this->paymentModel, $order, $transaction, $card);
        } catch (HashMismatchException $e) {
            dd($request->request->all(), $request->query->all(), $e);
        } catch (\Exception|\Error $e) {
            dd($request->request->all(), $request->query->all(), $e);
        }

        $response = $this->pos->getResponse();

        // iptal, iade, siparis durum sorgulama islemleri yapabilmek icin $response'u kaydediyoruz
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
        string $lang = PosInterface::LANG_TR
    ): array
    {
        $orderId = date('Ymd').strtoupper(substr(uniqid(sha1(time())), 0, 4));

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

        if ($lang) {
            //lang degeri verilmezse account (EstPosAccount) dili kullanilacak
            $order['lang'] = $lang;
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
      // Formu JS ile otomatik submit ederek kullaniciyi banka gatewayine yonlendiriyoruz.
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
