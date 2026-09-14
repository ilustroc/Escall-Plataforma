<?php
return [
    'driver'=>env('SESSION_DRIVER','file'),'lifetime'=>(int)env('SESSION_LIFETIME',120),
    'expire_on_close'=>true,'encrypt'=>true,'files'=>storage_path('framework/sessions'),
    'connection'=>null,'table'=>'sessions','store'=>null,'lottery'=>[2,100],
    'cookie'=>env('SESSION_COOKIE','escall_plataforma_session'),'path'=>'/','domain'=>env('SESSION_DOMAIN'),
    'secure'=>env('APP_ENV','production')==='production' || (bool)env('SESSION_SECURE_COOKIE',false),
    'http_only'=>true,'same_site'=>'lax','partitioned'=>false,
];