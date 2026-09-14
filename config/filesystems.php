<?php
return [
    'default'=>'local',
    'disks'=>[
        'local'=>['driver'=>'local','root'=>storage_path('app/private'),'throw'=>true],
        'templates'=>['driver'=>'local','root'=>storage_path('app/templates'),'throw'=>true],
    ],
    'links'=>[],
];