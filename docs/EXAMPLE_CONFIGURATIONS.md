## Konfigurasyon yapısı

Konfigurasyon için gereken parametreler gateway'den gateway'e değişir.
Gateway için zorunlu olan parametre sağlanmadığında hata alırsınız.

Olası konfigurasyonlar:
```php
<?php

return [
    'banks' => [
        'unique_name' => [
            'gateway_class'     => null, // Zorunlu — Mews\Pos\Gateway\* sınıflarından biri

            'credentials'       => [  // Zorunlu — sanal pos hesap bilgileri

                // Tüm gateway'ler:
                // MerchantSafeId (AkbankPos)
                // ClientId (AssecoPos, ToslaPos)
                // ApiKey (IyzicoPos)
                // ShopCode (InterPos)
                // CLIENT_CODE (ParamPos, Param3DHostPos)
                // MerchantId (diğerleri)
                'merchant_id'          => null,

                // Çoğu gateway (AkbankPos, IyzicoPos, PayFlexV4/CPV4, PayTrPos hariç):
                //   AssecoPos: KullaniciAdi;
                //   InterPos: UserCode;
                //   GarantiPos: ProvUserID;
                //   KuveytPos|VakifKatilimPos: UserName;
                //   PayForPos: UserCode;
                //   PosNetPos|PosNetV1Pos: PosNetId;
                //   ToslaPos: ApiUser;
                //   ParamPos|Param3DHostPos: CLIENT_USERNAME
                'user_name'            => null,

                // AssecoPos: KullaniciSifresi;
                // PayFlexV4/CPV4: Password;
                // InterPos: UserPass;
                // GarantiPos: ProvisionPassword;
                // PayForPos: UserPass;
                // PayTrPos: MerchantSalt;
                // ParamPos|Param3DHostPos: CLIENT_PASSWORD
                'user_password'        => null,

                // AkbankPos: TerminalSafeId;
                // GarantiPos|PosNetPos|PosNetV1Pos: TerminalId;
                // KuveytPos|VakifKatilimPos: CustomerId;
                // PayFlexV4/CPV4: TerminalNo;
                // ParamPos|Param3DHostPos: Terminal_ID (opsiyonel)
                'terminal_id'          => null,

                // Gizli/mağaza anahtarı — gateway'e göre farklı isim alır, aynı config anahtarı kullanılır:
                //   AssecoPos: StoreKey;
                //   AkbankPos: SecretKey;
                //   GarantiPos: StoreKey;
                //   InterPos: MerchantPass;
                //   IyzicoPos: SecretKey;
                //   KuveytPos|VakifKatilimPos: Password;
                //   ParamPos|Param3DHostPos: GUID;
                //   PayForPos: MerchantPass;
                //   PayTrPos: MerchantKey;
                //   PosNetPos|PosNetV1Pos: EncKey;
                //   ToslaPos: ApiPass
                'secret_key'           => null,

                // GarantiPos: ProvUserID (iade kullanıcısı)
                'refund_user_name'     => null,

                // GarantiPos: ProvisionPassword (iade kullanıcısı)
                'refund_user_password' => null,

                // PayForPos: MbrId — kurum kodu (varsayılan: PayForPosAccount::MBR_ID_FINANSBANK)
                'mbr_id'               => null,

                // IyzicoPos|KuveytPos|VakifKatilimPos|AkbankPos|PayFlexV4Pos|PayFlexCPV4Pos (opsiyonel)
                'sub_merchant_id'      => null,

                // PayFlexV4Pos|PayFlexCPV4Pos (opsiyonel, varsayılan: MERCHANT_TYPE_STANDARD)
                'merchant_type'        => null,
            ],

            'gateway_endpoints' => [ // Zorunlu
                 'payment_api'     => null, // Zorunlu
                 'gateway_3d'      => null, // 3D ödeme modelleri için gerekli (KuveytPos ve VakifKatilimPos hariç)
                 'gateway_3d_host' => null,
                 'query_api'       => null,
            ],

            'gateway_configs' => [ // opsiyonel
                'test_mode' => false, // varsayılan: false
                'lang'      => \Mews\Pos\PosInterface::LANG_TR, // varsayılan: LANG_TR
                // Hash kontrolü kütüphaneden dolayı başarısız sonuçlanıyorsa bu ayarla devre dışı bırakılabilir.
                // Ancak hash kontrolünün devre dışı bırakılması güvenlik açığı oluşturabilir.
                'disable_3d_hash_check' => false, // varsayılan: false
            ],
        ],
    ],
];
```

Parametrelerin açıklamalarında hangi gateway'de neye karşılık geldiğini yazar.

Örneğin bu parametre açıklamasına göre:
```yaml
                # InterPos: ShopCode
                merchant_id: ~
```
`InterPos`'ta **ShopCode** değeri için `merchant_id` alanı kullanmamız gerekiyor.

> **Not:** `secret_key` alanı gateway'e göre farklı anlamlar taşır:
> - `IyzicoPos`: SecretKey
> - `PayTrPos`: MerchantKey (`user_password` = MerchantSalt, `merchant_id` = MerchantId)
> - `ParamPos` / `Param3DHostPos`: GUID
> - Diğerleri: StoreKey / EncKey / Password / ApiPass (ilgili banka belgelerine bakın)


## Örnek Konfigürasyonlar

```php
return [
    'banks' => [
        'asseco_payten'         => [
            'gateway_class'     => \Mews\Pos\Gateway\AssecoPos::class,
            'credentials'       => [
                'merchant_id'   => '700XXXXXXX',  // ClientId
                'user_name'     => 'ISXXXXXXX',   // KullaniciAdi
                'user_password' => 'ISXXXXXXX',   // KullaniciSifresi
                'secret_key'    => 'TRPXXXXXXX',  // StoreKey
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://entegrasyon.asseco-see.com.tr/fim/api',
                'gateway_3d'      => 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate',
                'gateway_3d_host' => 'https://sanalpos.sanalakpos.com.tr/fim/est3Dgate',
            ],
        ],

        'yapikredi'             => [
            'gateway_class'     => \Mews\Pos\Gateway\PosNetPos::class,
            'credentials'       => [
                'merchant_id' => '670XXXXXXX', // Üye İşyeri Numarası
                'terminal_id' => '673XXXXXXX', // Üye İşyeri Terminal Numarası
                'user_name'   => '27XXXXXXX',  // Üye İşyeri POSNET Numarası
                'secret_key'  => '10,43,43,45,65,56,76,08', // EncKey
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://setmpos.ykb.com/PosnetWebService/XML',
                'gateway_3d'  => 'https://setmpos.ykb.com/3DSWebService/YKBPaymentService',
            ],
        ],

        'albaraka'              => [
            'gateway_class'     => \Mews\Pos\Gateway\PosNetV1Pos::class,
            'credentials'       => [
                'merchant_id' => '670XXXXXXX',      // 10 haneli üye işyeri numarası
                'terminal_id' => 'XXXXXXXX',         // 8 haneli terminal numarası
                'user_name'   => '10100628XXXXXXX', // 16 haneli EPOS numarası
                'secret_key'  => '10,43,43,45,65,56,76,08',
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://epostest.albarakaturk.com.tr/ALBMerchantService/MerchantJSONAPI.svc',
                'gateway_3d'  => 'https://epostest.albarakaturk.com.tr/ALBSecurePaymentUI/SecureProcess/SecureVerification.aspx',
            ],
        ],

        'payfor_finansbank'     => [
            'gateway_class'     => \Mews\Pos\Gateway\PayForPos::class,
            'credentials'       => [
                'merchant_id'   => '08530000XXXXXXXX',  // Üye İşyeri Numarası.
                'user_name'     => 'QNB_API_XXXXXXXX',  // UserCode: Otorizasyon sistemi kullanıcı kodu.
                'user_password' => 'XXXXXXXX',           // Otorizasyon sistemi kullanıcı şifresi.
                'secret_key'    => 'XXXXXXXX',           // MerchantPass: 3D Secure şifresidir.
                'mbr_id'        => \Mews\Pos\Model\Account\PayForPosAccount::MBR_ID_FINANSBANK, // veya MBR_ID_ZIRAAT_KATILIM (Kurum Kodu)
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://vpostest.qnb.com.tr/Gateway/XMLGate.aspx',
                'gateway_3d'      => 'https://vpostest.qnb.com.tr/Gateway/Default.aspx',
                'gateway_3d_host' => 'https://vpostest.qnb.com.tr/Gateway/3DHost.aspx',
            ],
        ],

        'payfor_ziraat_katilim' => [
            'gateway_class'     => \Mews\Pos\Gateway\PayForPos::class,
            'credentials'       => [
                'merchant_id'   => '08530000XXXXXXXX',           // Üye İşyeri Numarası.
                'user_name'     => 'ZIRAAT_KATILIM_API_XXXXXXXX', // UserCode: Otorizasyon sistemi kullanıcı kodu.
                'user_password' => 'XXXXXXXX',                    // Otorizasyon sistemi kullanıcı şifresi.
                'secret_key'    => 'XXXXXXXX',                    // MerchantPass: 3D Secure şifresidir.
                'mbr_id'        => \Mews\Pos\Model\Account\PayForPosAccount::MBR_ID_ZIRAAT_KATILIM,
            ],
            'gateway_configs'   => [
                'disable_3d_hash_check' => true,
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://payfortestziraatkatilim.cordisnetwork.com/Mpi/XMLGate.aspx',
                'gateway_3d'      => 'https://payfortestziraatkatilim.cordisnetwork.com/Mpi/Default.aspx',
                'gateway_3d_host' => 'https://payfortestziraatkatilim.cordisnetwork.com/Mpi/3DHost.aspx',
            ],
        ],

        'garanti'               => [
            'gateway_class'     => \Mews\Pos\Gateway\GarantiPos::class,
            'credentials'       => [
                'merchant_id'          => '70XXXXXXXX',    // MerchantID
                'user_name'            => 'XXXXXXXX',      // ProvUserID
                'user_password'        => '123XXXXXXXX',   // ProvisionPassword
                'terminal_id'          => '306XXXXXXXX',
                'secret_key'           => '123XXXXXXXX',   // StoreKey
                'refund_user_name'     => 'PROXXXXXXXX',   // ProvUserID
                'refund_user_password' => '123qXXXXXXXX',  // ProvisionPassword
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://sanalposprovtest.garantibbva.com.tr/VPServlet',
                'gateway_3d'  => 'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine',
            ],
            'gateway_configs'   => [
                'test_mode' => false, // Test ortamı için true yapılması gerekir.
            ],
        ],

        'interpos_denizbank'    => [
            'gateway_class'     => \Mews\Pos\Gateway\InterPos::class,
            'credentials'       => [
                'merchant_id'   => 'InterXXXXXXXX', // ShopCode
                'user_name'     => '31XXXXXXXX',     // UserCode
                'user_password' => '3XXXXXXXX',      // UserPass
                'secret_key'    => 'gXXXXXXXX',      // MerchantPass
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://test.inter-vpos.com.tr/mpi/Default.aspx',
                'gateway_3d'      => 'https://test.inter-vpos.com.tr/mpi/Default.aspx',
                'gateway_3d_host' => 'https://test.inter-vpos.com.tr/mpi/3DHost.aspx',
            ],
        ],

        'kuveytpos'             => [
            'gateway_class'     => \Mews\Pos\Gateway\KuveytPos::class,
            'credentials'       => [
                'merchant_id' => '4XXXXXXXX',    // MerchantId
                'terminal_id' => '40XXXXXXXX',   // CustomerId / MüşteriNo
                'user_name'   => 'apiXXXXXXXX',  // UserName (APİ kullanıcısı)
                'secret_key'  => 'ApiXXXXXXXX',  // StoreKey (APİ kullanıcısının şifresi)
            ],
            'gateway_configs'   => [
                'test_mode' => true,
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home',
                'query_api'   => 'https://boatest.kuveytturk.com.tr/BOA.Integration.WCFService/BOA.Integration.VirtualPos/VirtualPosService.svc/Basic',
            ],
        ],

        'vakifkatilim'          => [
            'gateway_class'     => \Mews\Pos\Gateway\VakifKatilimPos::class,
            'credentials'       => [
                'merchant_id' => '1XXXXXXXX',    // MerchantId: Üye işyerinin Kuveyt Türk SanalPos servisinde kayıtlı özel numarasıdır.
                'terminal_id' => '1XXXXXXXX',    // CustomerId: Üye işyerinin Kuveyt Türk'te yer SanalPos için kullanılabilecek hesaba ait müşteri numarasıdır.
                'user_name'   => 'APIXXXXXXXX',  // UserName: https://kurumsal.kuveytturk.com.tr adresine login olarak kullanıcı işlemleri sayfasında APİ rolünde kullanıcı oluşturulmalıdır.
                'secret_key'  => 'XXXXXXXX',     // Password: Oluşturulan APİ kullanıcısının şifre bilgisidir.
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://boa.vakifkatilim.com.tr/VirtualPOS.Gateway/Home',
                'gateway_3d_host' => 'https://boa.vakifkatilim.com.tr/VirtualPOS.Gateway/CommonPaymentPage/CommonPaymentPage',
            ],
        ],

        'payflexv4_ziraat'      => [
            'gateway_class'     => \Mews\Pos\Gateway\PayFlexV4Pos::class,
            'credentials'       => [
                'merchant_id'   => '000000000XXXXXXXX', // HostMerchantId: Üye işyeri numarası
                'terminal_id'   => 'VPXXXXXXXX',        // HostTerminalNo: İşlemin hangi terminal üzerinden gönderileceği bilgisi
                'user_password' => '3XXXXXXXX',         // Password: Üye işyeri şifresi
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://preprod.payflex.com.tr/Ziraatbank/VposWeb/v3/Vposreq.aspx',
                'gateway_3d'  => 'https://preprod.payflex.com.tr/ZiraatBank/MpiWeb/MPI_Enrollment.aspx',
                'query_api'   => 'https://sanalpos.ziraatbank.com.tr/v4/UIWebService/Search.aspx',
            ],
        ],

        'payflexcpv4_vakifbank' => [
            'gateway_class'     => \Mews\Pos\Gateway\PayFlexCPV4Pos::class,
            'credentials'       => [
                'merchant_id'   => '0001000XXXXXXXX', // HostMerchantId: Üye işyeri numarası
                'terminal_id'   => 'VPXXXXXXXX',      // HostTerminalNo: İşlemin hangi terminal üzerinden gönderileceği bilgisi
                'user_password' => 'XXXXXXXX',        // Password: Üye işyeri şifresi
            ],
            'gateway_endpoints' => [
                // gateway_3d artık gerekli değil; payment_api kısaltılmış URL
                'payment_api' => 'https://cptest.vakifbank.com.tr/CommonPayment/api',
            ],
        ],

        'akbankpos'             => [
            'gateway_class'     => \Mews\Pos\Gateway\AkbankPos::class,
            'credentials'       => [
                'merchant_id' => '20230904XXXXXXXXXXXXXXXXXXXXXXXX', // merchantSafeId: 32 karakter üye İş Yeri numarası
                'terminal_id' => '20230904XXXXXXXXXXXXXXXXXXXXXXXX', // terminalSafeId: 32 karakter
                'secret_key'  => 'XXXXXXXX',                         // secretKey
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://apipre.akbank.com/api/v1/payment/virtualpos',
                'gateway_3d'      => 'https://virtualpospaymentgatewaypre.akbank.com/securepay',
                'gateway_3d_host' => 'https://virtualpospaymentgatewaypre.akbank.com/payhosting',
            ],
        ],

        'toslapos'              => [
            'gateway_class'     => \Mews\Pos\Gateway\ToslaPos::class,
            'credentials'       => [
                'merchant_id' => '100XXXXXXXX', // clientId
                'user_name'   => 'POS_ENTXXXXXXXX', // apiUser
                'secret_key'  => 'POS_ENTXXXXXXXX', // apiPass
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://prepentegrasyon.tosla.com/api/Payment',
                'gateway_3d'      => 'https://prepentegrasyon.tosla.com/api/Payment/ProcessCardForm',
                'gateway_3d_host' => 'https://prepentegrasyon.tosla.com/api/Payment/threeDSecure',
            ],
        ],

        'parampos'              => [
            'gateway_class'     => \Mews\Pos\Gateway\ParamPos::class,
            'credentials'       => [
                'merchant_id'   => '12345',            // CLIENT_CODE
                'user_name'     => 'TestUser',          // CLIENT_USERNAME Kullanıcı adı
                'user_password' => 'TestPassword',      // CLIENT_PASSWORD Şifre
                'secret_key'    => 'kjsdfk-lkjdf-kjshdf-kjhfdsk-jfhshfsdfdsjf', // GUID Üye İşyeri ait anahtarı
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://test-dmz.param.com.tr/turkpos.ws/service_turkpos_test.asmx',
            ],
        ],

        // 3DHost ödemeleri için ayrı gateway
        'parampos_3d_host'      => [
            'gateway_class'     => \Mews\Pos\Gateway\Param3DHostPos::class,
            'credentials'       => [
                'merchant_id'   => '12345',            // CLIENT_CODE
                'user_name'     => 'TestUser',          // CLIENT_USERNAME Kullanıcı adı
                'user_password' => 'TestPassword',      // CLIENT_PASSWORD Şifre
                'secret_key'    => 'kjsdfk-lkjdf-kjshdf-kjhfdsk-jfhshfsdfdsjf', // GUID Üye İşyeri ait anahtarı
            ],
            'gateway_endpoints' => [
                'payment_api'     => 'https://test-pos.param.com.tr/to.ws/Service_Odeme.asmx',
                'gateway_3d_host' => 'https://test-pos.param.com.tr/default.aspx',
            ],
        ],

        'iyzico'                => [
            'gateway_class'     => \Mews\Pos\Gateway\IyzicoPos::class,
            'credentials'       => [
                'merchant_id' => 'sandbox-api-key',    // ApiKey
                'secret_key'  => 'sandbox-secret-key', // SecretKey
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://sandbox-api.iyzipay.com',
            ],
        ],

        'paytr'                 => [
            'gateway_class'     => \Mews\Pos\Gateway\PayTrPos::class,
            'credentials'       => [
                'merchant_id'   => 'XXXXXXXX',         // merchant_id
                'user_password' => 'XXXXXXXX',         // merchant_salt
                'secret_key'    => 'XXXXXXXX',         // merchant_key
            ],
            'gateway_endpoints' => [
                'payment_api' => 'https://www.paytr.com/odeme/api',
            ],
        ],
    ],
];
```
