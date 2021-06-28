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

$pos->account([
    'bank'          => 'akbank',
    'model'         => 'regular',
    'client_id'     => 'XXXXX',
    'username'      => 'XXXXX',
    'password'      => 'XXXXX',
    'env'           => 'test',
]);

$order = [
    'id'            => 'unique-order-id-' . str_random(16),
    'name'          => 'John Doe', // optional
    'email'         => 'mail@customer.com', // optional
    'user_id'       => '12', // optional
    'amount'        => (double) 100,
    'installment'   => '0',
    'currency'      => 'TRY',
    'ip'            => request()->ip(),
    'transaction'   => 'pay', // pay => Auth, pre PreAuth
];

$card = [
    'number'        => 'XXXXXXXXXXXXXXXX',
    'month'         => 'XX',
    'year'          => 'XX',
    'cvv'           => 'XXX',
];

$pos->prepare($order);

$payment = $pos->payment($card);

dd($payment->response);

```

License
----

MIT
