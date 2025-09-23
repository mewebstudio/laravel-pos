<?php

return [
    'banks' => [
        'unique_name' => [
            'gateway_class'     => null, // Required
            'lang'              => 'tr',
            'credentials'       => [  // Required, sanal pos hesap bilgileri
                'payment_model'        => null, // Required

                // EstPos|ToslaPos: ClientId;
                // PosNet|GarantiPos|PayForPos|KuveytPos|VakifKatilimPos|PayFlexV4Pos|PayFlexCPV4Pos: MerchantId;
                // InterPos: ShopCode;
                // AkbankPos: MerchantSafeId;
                'merchant_id'          => null,
                'sub_merchant_id'      => null,

                // EstPos: KullanıcıAdı;
                // PosNet: PosNetId;
                // PayForPos|InterPos: UserCode;
                // GarantiPos: ProvUserID;
                // KuveytPos|VakifKatilimPos: UserName;
                // ToslaPos: ApiUser
                'user_name'            => null,

                // PosNet|GarantiPos|PayFlexV4Pos|PayFlexCPV4Pos: TerminalId
                // KuveytPos|VakifKatilimPos: CustomerId;
                // AkbankPos: TerminalSafeId
                'terminal_id'          => null,

                // PayFlexV4Pos|PayFlexCPV4Pos: Password;
                // EstPos: KullaniciSifresi;
                // PayForPos|InterPos: UserPass;
                // GarantiPos: ProvisionPassword;
                'user_password'        => null,

                // EstPos|GarantiPos: StoreKey;
                // PosNet: EncKey;
                // PayForPos|InterPos: MerchantPass;
                // KuveytPos: Password;
                'enc_key'              => null,

                // GarantiPos: ProvUserID;
                'refund_user_name'     => null,

                // GarantiPos: ProvisionPassword
                'refund_user_password' => null,

                // PayForPos only
                'mbr_id'               => null,
            ],
            'gateway_endpoints' => [ // Required
                 'payment_api'     => null, // Required
                 'gateway_3d'      => null, // Required
                 'gateway_3d_host' => null,
                 'query_api'       => null,
            ],
            'test_mode'         => false,
        ],
    ],
];
