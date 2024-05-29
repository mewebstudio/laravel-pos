Özel durumlarda 3D form verisini veya API istek verisini değiştirmeniz gerektiğinde
**EventListener**'ler kullanabilirsiniz.

### Before3DFormHashCalculatedEvent
3D form verisini hash hesaplamadan önce değiştirme:

```php
<?php

namespace App\Listeners;

use Mews\Pos\Event\Before3DFormHashCalculatedEvent;
use Mews\Pos\PosInterface;

/**
 * Bu Event'i dinleyerek 3D formun hash verisi hesaplanmadan önce formun input array içireğini güncelleyebilirsiniz.
 * Eger ekleyeceginiz veri hash hesaplamada kullanilmiyorsa form verisi olusturduktan sonra da ekleyebilirsiniz.
 */
final class Before3DFormHashCalculatedEventListener
{
    public function __invoke(Before3DFormHashCalculatedEvent $event)
    {
        //$this->imeceKoduEkle($event);
        //$this->callbackUrlEkleme($event);
    }

    private function imeceKoduEkle(Before3DFormHashCalculatedEvent $event): void
    {
        if ($event->getGatewayClass() !== \Mews\Pos\Gateways\EstV3Pos::class || $event->getGatewayClass() !== \Mews\Pos\Gateways\EstPos::class) {
            return;
        }
        // İşbank İmece Kart ile ödeme yaparken aşağıdaki verilerin eklenmesi gerekiyor:
        $supportedPaymentModels = [
            \Mews\Pos\PosInterface::MODEL_3D_PAY,
            \Mews\Pos\PosInterface::MODEL_3D_PAY_HOSTING,
            \Mews\Pos\PosInterface::MODEL_3D_HOST,
        ];
        if ($event->getTxType() === PosInterface::TX_TYPE_PAY_AUTH && \in_array($event->getPaymentModel(), $supportedPaymentModels, true)) {
            $formInputs           = $event->getFormInputs();
            $formInputs['IMCKOD'] = '9999'; // IMCKOD bilgisi bankadan alınmaktadır.
            $formInputs['FDONEM'] = '5'; // Ödemenin faizsiz ertelenmesini istediğiniz dönem sayısı.
            $event->setFormInputs($formInputs);
        }
    }

    private function callbackUrlEkleme(Before3DFormHashCalculatedEvent $event): void
    {
        if ($event->getGatewayClass() !== \Mews\Pos\Gateways\EstV3Pos::class) {
            return;
        }
        $formInputs                = $event->getFormInputs();
        $formInputs['callbackUrl'] = $formInputs['failUrl'];
        $formInputs['refreshTime'] = '10'; // birim: saniye; callbackUrl sisteminin doğru çalışması için eklenmesi gereken parametre
        $event->setFormInputs($formInputs);
    }
}
```

### RequestDataPreparedEvent
API istek verisini değiştirme:
```php
<?php

namespace App\Listeners;

use Mews\Pos\Event\RequestDataPreparedEvent;
use Mews\Pos\PosInterface;

final class RequestDataPreparedEventListener
{
    public function __invoke(RequestDataPreparedEvent $event): void
    {
////        Burda istek banka API'na gonderilmeden once gonderilecek veriyi degistirebilirsiniz.
////        Ornek:
//        $data         = $event->getRequestData();
//        $data['abcd'] = '1234';
//        $event->setRequestData($data);


        //$this->koiCodeEkleme($event);
        //$this->imeceKodEkle($event);
        //$this->threeDFormVerisiniOlusturmakIcinGonderilenIstekVerisiniDegistirme($event);
    }

    private function koiCodeEkleme(RequestDataPreparedEvent $event): void
    {
        /**
         * KOICodes:
         * 1:Ek Taksit
         * 2: Taksit Atlatma
         * 3: Ekstra Puan
         * 4: Kontur Kazanım
         * 5: Ekstre Erteleme
         * 6: Özel Vade Farkı
         */
        if ($event->getGatewayClass() instanceof \Mews\Pos\Gateways\PosNetV1Pos) {
             // Albaraka PosNet KOICode ekleme
             $data            = $event->getRequestData();
             $data['KOICode'] = '1';
             $event->setRequestData($data);
        }
        if ($event->getGatewayClass() instanceof \Mews\Pos\Gateways\PosNet) {
             // Yapikredi PosNet KOICode ekleme
             $data            = $event->getRequestData();
             $data['sale']['koiCode'] = '1';
             $event->setRequestData($data);
        }
    }

    /**
     * Isbank İMECE için ekstra alanların eklenme örneği
     */
    private function imeceKodEkle(RequestDataPreparedEvent $event): void
    {
        if ($event->getGatewayClass() !== \Mews\Pos\Gateways\EstV3Pos::class || $event->getGatewayClass() !== \Mews\Pos\Gateways\EstPos::class) {
            return;
        }

        if ($event->getPaymentModel() !== PosInterface::MODEL_3D_SECURE) {
            return;
        }

        if ($event->getTxType() === PosInterface::TX_TYPE_PAY_AUTH) {
            $data                    = $event->getRequestData();
            $data['Extra']['IMCKOD'] = '9999'; // IMCKOD bilgisi bankadan alınmaktadır.
            $data['Extra']['FDONEM'] = '5'; // Ödemenin faizsiz ertelenmesini istediğiniz dönem sayısı
            $event->setRequestData($data);
        }
    }

    private function threeDFormVerisiniOlusturmakIcinGonderilenIstekVerisiniDegistirme(RequestDataPreparedEvent $event): void
    {
        if ($event->getPaymentModel() === PosInterface::MODEL_NON_SECURE) {
            return;
        }

        $formVerisiniOlusturmakIcinApiIstegiGonderenGatewayler = [
            \Mews\Pos\Gateways\PosNet::class,
            \Mews\Pos\Gateways\KuveytPos::class,
            \Mews\Pos\Gateways\ToslaPos::class,
            \Mews\Pos\Gateways\VakifKatilimPos::class,
            \Mews\Pos\Gateways\PayFlexV4Pos::class,
            \Mews\Pos\Gateways\PayFlexCPV4Pos::class,
        ];

        if (\in_array($event->getGatewayClass(), $formVerisiniOlusturmakIcinApiIstegiGonderenGatewayler, true)) {
//            // Burda istek banka API'na gonderilmeden once gonderilecek veriyi degistirebilirsiniz.
//            // Ornek:
//            if ($event->getTxType() === PosInterface::TX_TYPE_PAY_AUTH) {
//                $data         = $event->getRequestData();
//                $data['abcd'] = '1234';
//                $event->setRequestData($data);
//            }
        }
    }
}
```

Oluşturduğunuz listener'leri `AppServiceProvider`'de register etmeniz gerekiyor:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Mews\Pos\Event\Before3DFormHashCalculatedEvent::class,
            \App\Listeners\Before3DFormHashCalculatedEventListener::class
        );
        
        \Illuminate\Support\Facades\Event::listen(
            \Mews\Pos\Event\RequestDataPreparedEvent::class,
            \App\Listeners\RequestDataPreparedEventListener::class
        );
    }
}
```
