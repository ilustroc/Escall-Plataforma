<?php
return [
    'enforce_csrf'=>(bool)env('SECURITY_ENFORCE_CSRF',false), // Sólo afecta al bypass de PHPUnit; producción siempre valida.
    'login'=>['max_attempts'=>5,'decay_seconds'=>900,'ip_max_attempts'=>30],
    'headers'=>['hsts_max_age'=>31536000],
];