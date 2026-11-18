<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Verificar token CSRF
error_log('DIVIDENDO: CrearPreferencia.php ejecutado');
$token = $_POST['csrf_token'] ?? '';

error_log('=== DIAGNOSTICO CSRF ===');

error_log('Sesion iniciada: ' .
    (session_status() === PHP_SESSION_ACTIVE ? 'SI' : 'NO'));

error_log('Token en sesion: ' .
    (!empty($_SESSION['csrf_pago']) ? 'SI' : 'NO'));

error_log('Token recibido: ' .
    (is_string($token) && $token !== '' ? 'SI' : 'NO'));

error_log('Tokens coinciden: ' .
    (
        is_string($token) &&
        !empty($_SESSION['csrf_pago']) &&
        hash_equals($_SESSION['csrf_pago'], $token)
        ? 'SI' : 'NO'
    ));
if (
    !is_string($token) ||
    empty($_SESSION['csrf_pago']) ||
    !hash_equals($_SESSION['csrf_pago'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

$idParque = $_POST['parque_id'] ?? '';
$montoTexto = $_POST['monto'] ?? '';

if (
    !is_string($idParque) ||
    $idParque === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $idParque)
) {
    http_response_code(400);
    exit('Parque inválido.');
}

if (
    !is_string($montoTexto) ||
    !preg_match('/^\d{1,5}(\.\d{1,2})?$/', $montoTexto)
) {
    http_response_code(400);
    exit('Monto inválido.');
}

$montoCentavos = (int) round((float)$montoTexto * 100);

if ($montoCentavos < 100 || $montoCentavos > 1000000) {
    http_response_code(400);
    exit('Monto fuera de rango.');
}

$accessToken = getenv('MP_ACCESS_TOKEN');
$appUrl = rtrim((string)getenv('APP_URL'), '/');

if (
    !$accessToken ||
    !preg_match('#^https://[^/]+$#', $appUrl)
) {
    http_response_code(500);
    exit('Configuración de Mercado Pago incompleta.');
}

try {
    $database = obtenerFirebaseDatabase();

    $parque = $database
        ->getReference('parques/' . $idParque)
        ->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    $nombreParque = (string)(
        $parque['nombre'] ?? 'Parque'
    );

    // Identificador único de la operación de prueba
    $referenciaExterna = bin2hex(random_bytes(16));

    $datos = [
        'items' => [
            [
                'title' => 'Donación de prueba - ' . $nombreParque,
                'quantity' => 1,
                'currency_id' => 'MXN',
                'unit_price' => $montoCentavos / 100
            ]
        ],
        'external_reference' => $referenciaExterna,
        'back_urls' => [
            'success' => $appUrl . '/ResultadoPago.php',
            'failure' => $appUrl . '/ResultadoPago.php',
            'pending' => $appUrl . '/ResultadoPago.php'
        ],
        'auto_return' => 'approved'
    ];

    // Crear registro pendiente en Firebase
    $database
        ->getReference('pagosPrueba/' . $referenciaExterna)
        ->set([
            'parqueId' => $idParque,
            'usuarioId' => (string)$_SESSION['user_id'],
            'montoCentavos' => $montoCentavos,
            'estado' => 'pendiente',
            'fecha' => gmdate('c')
        ]);

    $ch = curl_init(
        'https://api.mercadopago.com/checkout/preferences'
    );

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $datos,
            JSON_THROW_ON_ERROR
        )
    ]);

    $respuesta = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($respuesta === false) {
        throw new RuntimeException('Error de conexión con Mercado Pago.');
    }

    curl_close($ch);

    $resultado = json_decode(
        $respuesta,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

   if ($codigoHttp < 200 || $codigoHttp >= 300) {

    error_log(
        '[DIVIDENDO MP] HTTP ' . $codigoHttp .
        ' | Respuesta de error: ' .
        substr(
            (string)($resultado['message'] ?? 'Sin mensaje'),
            0,
            300
        )
    );

    throw new RuntimeException(
        'Mercado Pago rechazó la preferencia. HTTP ' .
        $codigoHttp
    );
}

    // Guardar la preferencia creada
    $database
        ->getReference('pagosPrueba/' . $referenciaExterna)
        ->update([
            'preferenciaId' => $resultado['id'] ?? ''
        ]);

    $urlPago = $resultado['sandbox_init_point'] ?? '';

$hostPago = is_string($urlPago)
    ? parse_url($urlPago, PHP_URL_HOST)
    : null;

error_log('[DIVIDENDO MP] Diagnostico URL: ' . json_encode([
    'sandbox_disponible' => !empty($resultado['sandbox_init_point']),
    'init_disponible' => !empty($resultado['init_point']),
    'host_sandbox' => $hostPago ?: 'VACIO',
    'host_init' => parse_url(
        (string)($resultado['init_point'] ?? ''),
        PHP_URL_HOST
    ) ?: 'VACIO'
]));

if (
    !is_string($urlPago) ||
    !filter_var($urlPago, FILTER_VALIDATE_URL) ||
    parse_url($urlPago, PHP_URL_SCHEME) !== 'https' ||
    !in_array(
        $hostPago,
        ['sandbox.mercadopago.com', 'www.mercadopago.com'],
        true
    )
) {
    throw new RuntimeException(
        'URL de prueba no disponible o dominio no permitido.'
    );
}
    header('Location: ' . $urlPago, true, 303);
    exit();

} catch (Throwable $e) {

    error_log(
        '[DIVIDENDO MP] ' .
        get_class($e) . ': ' .
        $e->getMessage()
    );

    http_response_code(500);

    exit(
        'No se pudo iniciar el pago de prueba. ' .
        'Revisa los logs de Render.'
    );
}
