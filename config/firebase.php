
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

function obtenerFirebaseDatabase()
{
    $credenciales = getenv('FIREBASE_CREDENTIALS_JSON');

    if (!$credenciales) {
        throw new RuntimeException(
            'No se encontró la variable FIREBASE_CREDENTIALS_JSON.'
        );
    }

    $factory = (new Factory())
        ->withServiceAccount(json_decode($credenciales, true))
        ->withDatabaseUri(
            'https://dividendoplus-app-default-rtdb.firebaseio.com/'
        );

    return $factory->createDatabase();
}
