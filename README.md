# Türk bankaları için sanal pos paketi (Laravel)

## Temel Paket
[Pos](https://github.com/mewebstudio/pos)

### Minimum Gereksinimler
  - PHP >= 7.1.3
  - ext-dom
  - ext-json
  - ext-openssl
  - ext-SimpleXML

### Kurulum
```sh
$ composer require mews/laravel-pos
```

### Laravel 5.6 için
```sh
$ composer require --no-update "mews/laravel-pos:0.2.0"
$ composer update
```

`config/app.php` dosyasındaki `providers` kısmına aşağıdaki kodu ekleyin:
```php
'providers' => [
    // ...
    Mews\LaravelPos\LaravelPosServiceProvider::class,
]
```

`config/app.php` dosyasındaki `aliases` kısmına aşağıdaki kodu ekleyin:
```php
'aliases' => [
    // ...
    'LaravelPos' => Mews\LaravelPos\Facades\LaravelPos::class,
]
```

Konsolda, proje ana dizinindeyken aşağıdaki komut girilir:
```sh
$ php artisan vendor:publish --provider="Mews\LaravelPos\LaravelPosServiceProvider"
```

### Kullanım
```php
$pos = \Mews\LaravelPos\Facades\LaravelPos::instance();

$account = \Mews\Pos\Factory\AccountFactory::createGarantiPosAccount(
    'garanti',
    'clientId',
    'username',
    'password',
    'terminalId',
    'regular',
    \Mews\Pos\Gateways\GarantiPos::LANG_TR
);

$pos->account($account);

$order = [
    'id'            => 'unique-order-id-' . Str::random(16),
    'name'          => 'John Doe', // optional
    'email'         => 'mail@customer.com', // optional
    'user_id'       => '12', // optional
    'amount'        => (double) 100,
    'installment'   => '0',
    'currency'      => 'TRY',
    'ip'            => request()->ip(),
    'transaction'   => 'pay', // pay => Auth, pre PreAuth
];

$card = new \Mews\Pos\Entity\Card\CreditCardGarantiPos('1111222233334444', '20', '01', '000');

$pos->prepare($order, \Mews\Pos\Gateways\AbstractGateway::TX_PAY);

$payment = $pos->payment($card);

dd($payment->response);
```

License
----

MIT
