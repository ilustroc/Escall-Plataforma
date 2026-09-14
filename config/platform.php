<?php
return [
    'bank_accounts'=>[
        'interbank'=>['label'=>'INTERBANK','value'=>env('BANK_INTERBANK_ACCOUNT','')],
        'bcp'=>['label'=>'BCP','value'=>env('BANK_BCP_ACCOUNT','')],
        'yape'=>['label'=>'YAPE','value'=>env('BANK_YAPE_ACCOUNT','')],
        'bbva'=>['label'=>'BBVA','value'=>env('BANK_BBVA_ACCOUNT','')],
    ],
    'recovery_url'=>env('ACCOUNT_RECOVERY_URL',''),
];