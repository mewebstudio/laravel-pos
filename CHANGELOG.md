# Değişiklik Geçmişi

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
