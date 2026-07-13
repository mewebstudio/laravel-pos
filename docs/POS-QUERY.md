### PosQuery Kullanımı

Sipariş geçmişi ve özel sorgu işlemleri için `PosQueryInterface` kullanın.
Bu servis, query desteği olan gateway'ler için otomatik olarak container'a kaydedilir.

> **Not:** `KuveytPos` ve `Param3DHostPos` query desteği sunmamaktadır.

```php
use Mews\Pos\PosQuery\PosQueryInterface;
use Mews\LaravelPos\PosQueryRegistry;
use Mews\LaravelPos\Facades\LaravelPosQuery;

// Tek bank ya da default (ilk banka query destekliyorsa)
public function __construct(private PosQueryInterface $posQuery) {}

// Facade
$posQuery = LaravelPosQuery::query('akbank');

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
