<?php
// Copiar a database.php (ignorado), usando los valores privados existentes.
// No requiere ni ejecuta cambios en la BD.
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: '',
    'user' => getenv('DB_USER') ?: '',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];
