<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Cargar variables locales desde .env
$dotenv = Dotenv\Dotenv::createImmutable(
    dirname(__DIR__, 2)
);

$dotenv->safeLoad();

function obtenerMercadoPagoPublicKey(): string
{
    return $_ENV['MP_PUBLIC_KEY']
        ?? getenv('MP_PUBLIC_KEY')
        ?: '';
}
