<?php

return [
    'banks' => [
        'unique_name' => [
            'gateway_class'     => null, // Zorunlu. Aşağıdaki Mews\Pos\Gateway\* sınıflarından biri:
            // AssecoPos, AkbankPos, GarantiPos, InterPos, IyzicoPos, KuveytPos,
            // Param3DHostPos, ParamPos, PayFlexCPV4Pos, PayFlexV4Pos, PayForPos,
            // PayTrPos, PosNetPos, PosNetV1Pos, ToslaPos, VakifKatilimPos

            'credentials'       => [  // Zorunlu — alan adları gateway'e göre değişir

                // Tüm gateway'ler:
                // MerchantSafeId (AkbankPos)
                // ClientId (AssecoPos, ToslaPos)
                // ApiKey (IyzicoPos)
                // ShopCode (InterPos)
                // MerchantId (diğerleri)
                'merchant_id'          => null,

                // Çoğu gateway (AkbankPos, IyzicoPos, PayFlexV4/CPV4, PayTrPos hariç):
                //   AssecoPos: KullaniciAdi;
                //   InterPos: UserCode;
                //   GarantiPos: ProvUserID;
                //   KuveytPos|VakifKatilimPos: UserName;
                //   PosNetPos|PosNetV1Pos: PosNetId;
                //   ToslaPos: ApiUser;
                //   ParamPos|Param3DHostPos: username
                'user_name'            => null,

                // AssecoPos: KullaniciSifresi;
                // PayFlexV4/CPV4: Password;
                // InterPos: UserPass;
                // GarantiPos: ProvisionPassword;
                // PayForPos: UserPassword;
                // PayTrPos: MerchantSalt;
                // ParamPos|Param3DHostPos: password
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
                //   ParamPos|Param3DHostPos: Guid;
                //   PayForPos: MerchantPass;
                //   PayTrPos: MerchantKey;
                //   PosNetPos|PosNetV1Pos: EncKey;
                //   ToslaPos: ApiPass
                'secret_key'           => null,

                // Yalnızca GarantiPos:
                'refund_user_name'     => null, // ProvUserID (iade kullanıcısı)
                'refund_user_password' => null, // ProvisionPassword (iade kullanıcısı)

                // Yalnızca PayForPos (varsayılan: PayForPosAccount::MBR_ID_FINANSBANK):
                'mbr_id'               => null,

                // IyzicoPos|KuveytPos|VakifKatilimPos|AkbankPos|PayFlexV4Pos|PayFlexCPV4Pos (opsiyonel):
                'sub_merchant_id'      => null,

                // PayFlexV4Pos|PayFlexCPV4Pos (opsiyonel, varsayılan: MERCHANT_TYPE_STANDARD):
                'merchant_type'        => null,
            ],
            'gateway_endpoints' => [ // Zorunlu
                'payment_api'     => null, // Zorunlu
                'gateway_3d'      => null, // 3D ödeme modelleri için gerekli (KuveytPos ve VakifKatilimPos hariç)
                'gateway_3d_host' => null,
                'query_api'       => null,
            ],
            'gateway_configs' => [ // opsiyonel
                'test_mode'             => false, // varsayılan: false
                'lang'                  => \Mews\Pos\PosInterface::LANG_TR, // varsayılan: LANG_TR
                // Hash kontrolü kütüphaneden dolayı başarısız sonuçlanıyorsa bu ayarla devre dışı bırakılabilir.
                // Ancak hash kontrolünün devre dışı bırakılması güvenlik açığı oluşturabilir.
                'disable_3d_hash_check' => false, // varsayılan: false
            ],
        ],
    ],
];
