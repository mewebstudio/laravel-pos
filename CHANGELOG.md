# Değişiklik Geçmişi

## [2.0.0] - 2026-07-16

Bkz. [docs/UPGRADE-2.0.md](./docs/UPGRADE-2.0.md) — v1'den v2'ye geçiş kılavuzu.

### Kırıcı Değişiklikler

- **PHP minimum 8.0** (`>=7.4` → `>=8.0`).

- **`mews/pos ^2.0` gereksinimi.**
  `mews/pos` v2 major versiyon yükseltmesi içermektedir; namespace'ler, gateway sınıf isimleri ve API imzaları değişmiştir.

- **`gateway_class` değerleri güncellendi.**
  `Mews\Pos\Gateways\*` → `Mews\Pos\Gateway\*`. Yeniden adlandırılanlar:
  `EstV3Pos` → `AssecoPos`, `PosNet` → `PosNetPos`,
  `EstPos` kaldırıldı.

- **`enc_key` → `secret_key`** credential alanı yeniden adlandırıldı.

- **`payment_model` credentials'dan kaldırıldı.**

- **`lang` üst düzeyden `gateway_configs.lang`'a taşındı.**

- **`AccountFactoryInterface` kaldırıldı.**
  Özel hesap fabrikasına olan ihtiyaç, `mews/pos`'un kendi `AccountFactory::createForGateway()` metodu tarafından karşılanmaktadır.
  Container'da `AccountFactoryInterface::class`'a bind etmişseniz kaldırın.

- **`setTestMode()` kaldırıldı** (`mews/pos` v2 değişikliği).
  Test modu yalnızca `gateway_configs.test_mode` ile ayarlanır.

- **KuveytPos ve VakifKatilimPos için `gateway_3d` endpoint'i kaldırıldı.**

- **PayFlexCPV4Pos için `gateway_3d` kaldırıldı; `payment_api` URL'i kısaltıldı.**

- **ParamPos 3DHost akışı ayrı bir gateway'e taşındı: `Param3DHostPos`.**

- **KuveytPos için ekstra order alanları** artık event listener yerine `$order` dizisine ekleniyor.

### Yeni Özellikler

- **`PosQueryInterface` ve `PosQueryRegistry` desteği eklendi.**
  Sipariş geçmişi (`history()`) ve ham API sorgusu (`customQuery()`) işlemleri için `PosQueryInterface` container'a otomatik olarak kaydedilir (gateway'in query desteği varsa).

  ```php
  // İnjection
  public function __construct(private \Mews\Pos\PosQuery\PosQueryInterface $posQuery) {}

  // Belirli bir banka
  $posQuery = app('laravel-pos:query:akbank');
  $posQuery = app(\Mews\LaravelPos\PosQueryRegistry::class)->query('akbank');

  // Tüm query servisleri
  $all = app()->tagged('laravel-pos:query');
  ```

  `KuveytPos` ve `Param3DHostPos` query desteği sunmadığından bu gateway'ler için binding oluşturulmaz.

- **Yeni desteklenen gateway'ler:** `IyzicoPos`, `PayTrPos`, `Param3DHostPos`.

---

## [1.4.0] - 2026-06-22

### Yeni Özellikler

- **Laravel 13 desteği eklendi.**

- **`GatewayRegistry` servisi ve `LaravelPos` facade'i eklendi.**
  Gateway'lere artık container key'i yerine tip güvenli bir servis veya facade üzerinden erişilebilir:
  ```php
  // Servis injection
  public function __construct(private \Mews\LaravelPos\GatewayRegistry $registry) {}
  $pos = $this->registry->gateway('kuveytpos');

  // Facade
  $pos = \Mews\LaravelPos\Facades\LaravelPos::gateway('kuveytpos');

  // Tüm gateway'ler
  $all = $this->registry->all(); // PosInterface[]
  ```

- **`AccountFactoryInterface` ile özelleştirilebilir hesap fabrikası.**
  `mews/pos`'ta yeni çıkan bir gateway için `laravel-pos` güncellemesini beklemeden kendi
  implementasyonunuzu yazabilirsiniz. Bkz. [Özel AccountFactory Kullanımı](./docs/CUSTOM-ACCOUNT-FACTORY.md).


### İyileştirmeler

- **Gateway'ler artık ihtiyaç duyulduğunda oluşturuluyor (lazy loading).**
  Daha önce tüm bankalar için gateway nesneleri uygulama başlangıcında oluşturuluyordu.
  Artık her gateway yalnızca ilk erişimde oluşturulur ve sonraki çağrılarda önbellekten döner.

- **KuveytPos ve PayFor endpoint URL'leri güncellendi.**

---
