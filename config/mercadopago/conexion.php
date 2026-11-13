
<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;

// Leer credenciales desde las variables de entorno
$accessToken = getenv('MP_ACCESS_TOKEN');

if (!$accessToken) {
    throw new RuntimeException(
        'No se configuró MP_ACCESS_TOKEN.'
    );
}

// Configurar el SDK
MercadoPagoConfig::setAccessToken($accessToken);
