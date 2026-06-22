# Özel AccountFactory Kullanımı

`mews/pos` kütüphanesi yeni bir gateway yayımladığında, bu gateway için `AccountFactory`'ye destek
eklenmeden önce kısa bir süre beklemek gerekebilir. Bu süreçte kendi `AccountFactoryInterface`
implementasyonunuzu yazarak yeni gateway'i hemen kullanabilirsiniz.

## Ne Zaman Gerekir?

`AccountFactory`, `mews/pos`'un desteklediği her gateway için hesap nesnesi (`AbstractPosAccount`)
oluşturmayı bilir. Ancak `mews/pos` yeni bir gateway çıkardığında ve `laravel-pos` henüz
güncellenmemişse, o gateway için hesap oluşturma adımı başarısız olur ve `DomainException` fırlatılır.

## Nasıl Yapılır?

### 1. Özel Factory Sınıfı Oluşturun

Bilinmeyen gateway için hesap oluşturma mantığını kendiniz yazın, geri kalan her şeyi varsayılan
`AccountFactory`'ye delege edin.

```php
<?php
# app/Factory/CustomAccountFactory.php

namespace App\Factory;

use Mews\LaravelPos\Factory\AccountFactoryInterface;
use Mews\Pos\Entity\Account\AbstractPosAccount;
use Mews\Pos\Factory\AccountFactory as MewsPosAccountFactory;
use Mews\Pos\Gateways\NewBankPos; // mews/pos'un yeni eklediği gateway
use Mews\Pos\PosInterface;

class CustomAccountFactory implements AccountFactoryInterface
{
    public function __construct(private AccountFactoryInterface $default) {}

    public function create(
        string $gatewayClass,
        string $name,
        array  $credentials,
        string $lang = PosInterface::LANG_TR
    ): AbstractPosAccount {
        if ($gatewayClass === NewBankPos::class) {
            // mews/pos'un yeni gateway'i için uygun AccountFactory metodunu çağırın.
            // Hangi metodu kullanacağınızı mews/pos kaynak kodundan öğrenebilirsiniz.
            return MewsPosAccountFactory::createNewBankPosAccount(
                $name,
                $credentials['merchant_id'],
                $credentials['enc_key'],
                $credentials['payment_model'],
                $lang,
            );
        }

        // Desteklenen tüm diğer gateway'ler için varsayılan factory'e delege et.
        return $this->default->create($gatewayClass, $name, $credentials, $lang);
    }
}
```

### 2. AppServiceProvider'a Kayıt Edin

`CustomAccountFactory`, constructor'ında varsayılan `AccountFactoryInterface` implementasyonunu
bekler. Bunu sağlamak için binding'i closure ile tanımlayın:

```php
<?php
# app/Providers/AppServiceProvider.php

namespace App\Providers;

use App\Factory\CustomAccountFactory;
use Illuminate\Support\ServiceProvider;
use Mews\LaravelPos\Factory\AccountFactory;
use Mews\LaravelPos\Factory\AccountFactoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccountFactoryInterface::class, function () {
            return new CustomAccountFactory(new AccountFactory());
        });
    }
}
```

### 3. Konfigürasyona Yeni Gateway'i Ekleyin

```php
# config/laravel-pos.php

return [
    'banks' => [
        'new_bank' => [
            'gateway_class'     => \Mews\Pos\Gateways\NewBankPos::class,
            'lang'              => \Mews\Pos\PosInterface::LANG_TR,
            'credentials'       => [
                'payment_model' => \Mews\Pos\PosInterface::MODEL_3D_SECURE,
                'merchant_id'   => 'XXXXXXXX',
                'enc_key'       => 'XXXXXXXX',
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://...',
                'gateway_3d'  => 'https://...',
            ],
        ],
    ],
];
```

## `laravel-pos` Güncellendiğinde

`laravel-pos` yeni gateway için resmi destek eklediğinde `CustomAccountFactory`'yi kaldırabilir,
`AppServiceProvider`'daki binding'i silebilirsiniz. Konfigürasyon dosyasında herhangi bir değişiklik
gerekmez.
