# v1'den v2'ye Geçiş Kılavuzu

Bu belge, `mews/laravel-pos` paketinin v1'den v2'ye yükseltilmesi için yapılması gereken değişiklikleri açıklamaktadır.

---

## İçindekiler

- [Gereksinimler](#gereksinimler)
- [1. Konfigurasyon Değişiklikleri](#1-konfigurasyon-değişiklikleri)
- [2. Credential Alan Adları](#2-credential-alan-adları)
- [3. Exception Namespace Değişiklikleri](#3-exception-namespace-değişiklikleri)
- [4. PosInterface — Kırıcı Değişiklikler](#4-posinterface--kırıcı-değişiklikler)
- [5. Kaldırılan Servisler](#5-kaldırılan-servisler)
- [6. Yeni: PosQueryInterface Desteği](#6-yeni-posqueryinterface-desteği)
- [Kontrol Listesi](#kontrol-listesi)

---

## Gereksinimler

| | v1 | v2 |
|---|---|---|
| Minimum PHP | 7.4 | **8.0** |
| mews/pos | ^1.7 | **^2.0** |

---

## 1. Konfigurasyon Değişiklikleri

### 1a. `gateway_class` değerleri

Gateway namespace'leri `Gateways` → `Gateway` olarak değişti. İki gateway yeniden adlandırıldı, biri kaldırıldı:

| v1 | v2 |
|---|---|
| `Mews\Pos\Gateways\EstV3Pos` | `Mews\Pos\Gateway\AssecoPos` |
| `Mews\Pos\Gateways\PosNet` | `Mews\Pos\Gateway\PosNetPos` |
| `Mews\Pos\Gateways\EstPos` | **kaldırıldı** — `AssecoPos` kullanın |
| `Mews\Pos\Gateways\*` | `Mews\Pos\Gateway\*` (diğerleri) |

Yeni eklenen gateway'ler: `IyzicoPos`, `Param3DHostPos`, `PayTrPos`

### 1b. `enc_key` → `secret_key`

Credentials dizisindeki `enc_key` anahtarı `secret_key` olarak yeniden adlandırıldı:

```php
// v1
'credentials' => [
    'enc_key' => 'store-key-value',
],

// v2
'credentials' => [
    'secret_key' => 'store-key-value',
],
```

### 1c. `payment_model` kaldırıldı

`payment_model` artık account'da saklanmıyor; her işlem çağrısında `$pos->payment($model, ...)` ile geçiliyor. Credentials dizisinden kaldırın:

```php
// v1
'credentials' => [
    'payment_model' => \Mews\Pos\PosInterface::MODEL_3D_SECURE, // kaldırın
    'merchant_id'   => 'xxx',
    // ...
],

// v2
'credentials' => [
    'merchant_id' => 'xxx',
    // ...
],
```

### 1d. `lang` → `gateway_configs` içine taşındı

```php
// v1
'akbank' => [
    'lang' => \Mews\Pos\PosInterface::LANG_TR, // üst düzey
    // ...
],

// v2
'akbank' => [
    'gateway_configs' => [
        'lang' => \Mews\Pos\PosInterface::LANG_TR, // gateway_configs içinde
    ],
    // ...
],
```

### 1e. Gateway'e özel endpoint değişiklikleri

`KuveytPos` ve `VakifKatilimPos` için `gateway_3d` artık gerekmez — kaldırın:

```php
// v1
'gateway_endpoints' => [
    'payment_api' => 'https://boatest.kuveytturk.com.tr/...',
    'gateway_3d'  => 'https://...', // kaldırın
],

// v2
'gateway_endpoints' => [
    'payment_api' => 'https://boatest.kuveytturk.com.tr/...',
],
```

`PayFlexCPV4Pos` için `gateway_3d` kaldırıldı ve `payment_api` URL'i kısaltıldı:

```php
// v1
'gateway_endpoints' => [
    'payment_api' => 'https://cptest.vakifbank.com.tr/CommonPayment/api/VposTransaction',
    'gateway_3d'  => 'https://cptest.vakifbank.com.tr/CommonPayment/api/RegisterTransaction',
],

// v2
'gateway_endpoints' => [
    'payment_api' => 'https://cptest.vakifbank.com.tr/CommonPayment/api',
],
```

### 1f. `ParamPos` → `ParamPos` + `Param3DHostPos` ayrımı

v1'de `ParamPos` hem normal hem de 3DHost ödemelerini yönetiyordu. v2'de 3DHost için ayrı bir gateway eklendi:

```php
// v1
'param-pos' => [
    'class'             => \Mews\Pos\Gateways\ParamPos::class,
    'gateway_endpoints' => [
        'payment_api'     => 'https://...service_turkpos.asmx',
        'payment_api_2'   => 'https://...Service_Odeme.asmx', // kaldırıldı
        'gateway_3d_host' => 'https://...default.aspx',       // kaldırıldı
    ],
],

// v2
'param-pos' => [
    'gateway_class'     => \Mews\Pos\Gateway\ParamPos::class,
    'gateway_endpoints' => [
        'payment_api' => 'https://...service_turkpos.asmx',
    ],
],
'param-3d-host-pos' => [
    'gateway_class'     => \Mews\Pos\Gateway\Param3DHostPos::class,
    'gateway_endpoints' => [
        'payment_api'     => 'https://...Service_Odeme.asmx',
        'gateway_3d_host' => 'https://...default.aspx',
    ],
],
```

---

## 2. Credential Alan Adları

Tam v2 credential anahtarları (gateway başına):

| Gateway | Zorunlu | Opsiyonel |
|---|---|---|
| `AssecoPos` | `merchant_id`, `user_name`, `user_password` | `secret_key` |
| `AkbankPos` | `merchant_id`, `terminal_id`, `secret_key` | `sub_merchant_id` |
| `GarantiPos` | `merchant_id`, `user_name`, `user_password`, `terminal_id` | `secret_key`, `refund_user_name`, `refund_user_password` |
| `InterPos` | `merchant_id`, `user_name`, `user_password` | `secret_key` |
| `IyzicoPos` | `merchant_id` (ApiKey), `secret_key` | `sub_merchant_id` |
| `KuveytPos` | `merchant_id`, `user_name`, `terminal_id`, `secret_key` | `sub_merchant_id` |
| `VakifKatilimPos` | `merchant_id`, `user_name`, `terminal_id`, `secret_key` | `sub_merchant_id` |
| `ParamPos` / `Param3DHostPos` | `merchant_id`, `user_name`, `user_password`, `secret_key` (Guid) | `terminal_id` |
| `PayFlexV4Pos` / `PayFlexCPV4Pos` | `merchant_id`, `user_password`, `terminal_id` | `merchant_type`, `sub_merchant_id` |
| `PayForPos` | `merchant_id`, `user_name`, `user_password` | `secret_key`, `mbr_id` |
| `PayTrPos` | `merchant_id`, `user_password` (Salt), `secret_key` (Key) | — |
| `PosNetPos` / `PosNetV1Pos` | `merchant_id`, `terminal_id`, `user_name` (PosNetId) | `secret_key` |
| `ToslaPos` | `merchant_id`, `user_name`, `secret_key` (ApiPass) | — |

---

## 3. Exception Namespace Değişiklikleri

```php
// v1
use Mews\Pos\Exceptions\HashMismatchException;
use Mews\Pos\Exceptions\CardTypeNotSupportedException;

// v2
use Mews\Pos\Exception\HashMismatchException;
use Mews\Pos\Exception\CardTypeNotSupportedException;
```

---

## 4. PosInterface — Kırıcı Değişiklikler

### 4a. Tüm işlem metotları artık `array` döndürüyor

`getResponse()` kaldırıldı. Metotlar artık sonucu doğrudan döndürüyor:

```php
// v1
$pos->payment($paymentModel, $order, $txType, $card);
$response = $pos->getResponse();

// v2
$response = $pos->payment($paymentModel, $order, $txType, $card);
```

### 4b. 3D ödeme callback'lerinde `$_POST` / `$_GET` dizisi geçilir

v1'de `make3DPayment()` artık yok; v2'de `payment()` 5. parametreyle bankadan gelen callback verisini alır:

```php
// v1
$pos->make3DPayment($symfonyRequest, $order, $txType);
$response = $pos->getResponse();

// v2
$gatewayResponseData = $request->post(); // veya $_POST
if ($pos::class === \Mews\Pos\Gateway\PayFlexCPV4Pos::class) {
    $gatewayResponseData = $request->query(); // veya $_GET
}
$response = $pos->payment($paymentModel, $order, $txType, $card, $gatewayResponseData);
```

### 4c. `setTestMode()` kaldırıldı

Test modunu `gateway_configs.test_mode` ile ayarlayın. Konfigurasyon dışında değiştirilemez.

### 4d. KuveytPos'ta ekstra order alanları

v1'de KuveytPos için ekstra veriler `RequestDataPreparedEvent` listener'ı ile ekleniyor, v2'de doğrudan `$order` dizisine ekleniyor:

```php
// v1 — event listener ile
$eventDispatcher->addListener(RequestDataPreparedEvent::class, function ($event) {
    $data = $event->getRequestData();
    $data['CardHolderData'] = [...];
    $event->setRequestData($data);
});

// v2 — $order dizisine ekleyin
$order['payment_channel'] = '02'; // Web Browser
$order['buyer'] = [
    'email'         => 'musteri@example.com',
    'gsm_number_cc' => '90',
    'gsm_number'    => '5001234567',
];
$order['billing_address'] = [
    'city'     => 'İstanbul',
    'country'  => '792',
    'address'  => 'Örnek Mah. No:1',
    'zip_code' => '34000',
    'state'    => '34',
];
$pos->get3DFormData($order, $paymentModel, $txType, $card);
```

---

## 5. Kaldırılan Servisler

### `AccountFactoryInterface` kaldırıldı

v1'de `AccountFactoryInterface` container'a bound ediliyordu ve özel implementasyon yazılabiliyordu.
v2'de `mews/pos`'un kendi `AccountFactory::createForGateway()` metodu tüm gateway'leri destekliyor;
özel bir factory implementasyonuna gerek kalmadı.

Container'da `AccountFactoryInterface::class`'a bind etmişseniz veya inject ediyorsanız kaldırın.

---

## 6. Yeni: PosQueryInterface Desteği

v2'de sipariş geçmişi ve özel sorgu işlemleri `PosQueryInterface` üzerinden yapılıyor.
Bu servis otomatik olarak container'a kaydedilir (gateway'in query desteği varsa):

```php
use Mews\Pos\PosQuery\PosQueryInterface;
use Mews\LaravelPos\PosQueryRegistry;
use Mews\LaravelPos\Facades\LaravelPosQuery;

// Default bank (ilk banka query destekliyorsa)
$posQuery = app(PosQueryInterface::class);

// Facade
$posQuery = LaravelPosQuery::query('akbank');

// Belirli bir banka
$posQuery = app('laravel-pos:query:akbank');

// Registry üzerinden
$posQuery = app(PosQueryRegistry::class)->query('akbank');

// Kullanım
$response = $posQuery->history([
    'start_date' => new \DateTime('-1 month'),
    'end_date'   => new \DateTime(),
]);
```

> **Not:** `KuveytPos` ve `Param3DHostPos` için `PosQueryInterface` desteği bulunmamaktadır.
> Bu gateway'ler için `laravel-pos:query:*` container binding'i oluşturulmaz.

---

## Kontrol Listesi

- [ ] `composer.json`'da PHP `>=8.0` yapıldı mı?
- [ ] `composer require mews/laravel-pos:^2.0` çalıştırıldı mı?
- [ ] `config/laravel-pos.php` yayımlandı ve güncellendi mi?
- [ ] `gateway_class` değerleri `Mews\Pos\Gateway\*` namespace'ine güncellendi mi?
- [ ] `EstPos` → `AssecoPos`, `EstV3Pos` → `AssecoPos`, `PosNet` → `PosNetPos` değiştirildi mi?
- [ ] `enc_key` → `secret_key` olarak yeniden adlandırıldı mı?
- [ ] `payment_model` credentials'dan kaldırıldı mı?
- [ ] Üst düzey `lang` → `gateway_configs.lang` içine taşındı mı?
- [ ] `KuveytPos`/`VakifKatilimPos` için `gateway_3d` endpoint'i kaldırıldı mı?
- [ ] `PayFlexCPV4Pos` için `gateway_3d` kaldırıldı ve `payment_api` URL'i kısaltıldı mı?
- [ ] `ParamPos` için `payment_api_2` ve `gateway_3d_host` kaldırıldı mı? `Param3DHostPos` eklendi mi?
- [ ] `$pos->payment(...)` dönüş değeri (`$response`) kullanılıyor mu? (`getResponse()` kaldırıldı)
- [ ] 3D callback'lerde `$_POST`/`$_GET` dizisi `payment()` 5. parametresi olarak geçiliyor mu?
- [ ] `Exception\HashMismatchException` gibi exception namespace'leri güncellendi mi? (`Exceptions` → `Exception`)
- [ ] `setTestMode()` çağrıları kaldırıldı mı?
- [ ] KuveytPos için extra veriler `$order` dizisine taşındı mı? (event listener yerine)
- [ ] `AccountFactoryInterface` inject eden / bind eden kod kaldırıldı mı?
