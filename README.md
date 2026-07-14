# Türk bankaları için sanal pos paketi (Laravel)

## Temel Paket
[mews/pos](https://github.com/mewebstudio/pos)

## Ana başlıklar

- [Minimum Gereksinimler](#minimum-gereksinimler)
- [Kurulum](#kurulum)
- [Gateway'lere Erişim](#gatewaylere-erişim)
- [PosQuery Kullanımı](./docs/POS-QUERY.md)
- [Kullanım (3D Secure Ödeme)](./docs/3D-SECURE-EXAMPLE.md)
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

### Troubleshoots

- Error: "_cURL error 60: SSL certificate problem: unable to get local issuer certificate (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://..._"
  Genellikle lokal ortamda bu sorunla karşılaşabilirsiniz. Lokal ortamınızda CA certificate bulunmadığında oluşur.
  Bu durumda sunucuda çalıştırmayı deneyiniz.


License
----

MIT
