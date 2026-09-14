<?php
return [
    'defaults'=>['guard'=>'web','passwords'=>'users'],
    'guards'=>['web'=>['driver'=>'session','provider'=>'users']],
    'providers'=>['users'=>['driver'=>'legacy-compatible-eloquent','model'=>App\Models\User::class]],
    'legacy_passwords'=>(bool)env('LEGACY_PASSWORDS_ALLOWED', false),
    'password_timeout'=>10800,
];