<?php
// Datos comerciales extraídos sin cambiar sus valores; la copia real está ignorada.
$local = __DIR__ . '/business.local.php';
return is_file($local) ? require $local : require __DIR__ . '/business.example.php';
