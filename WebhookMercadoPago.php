<?php
require_once __DIR__ . '/config/firebase.php';

header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, 'Metodo no permitido');
}

$secret = getenv('MP_WEBHOOK_SECRET');
$accessToken = getenv('MP_ACCESS_TOKEN');

if (!$secret || !$accessToken) {
    error_log('[DIVIDENDO MP] Configuracion incompleta');
    responder(503, 'Servicio no configurado');
}

// Leer el identificador del pago notificado.
$idPago = $_GET['data.id']
    ?? $_GET['data_id']
    ?? null;

if (!is_string($idPago) ||
    !preg_match('/^[0-9]{1,30}$/', $idPago)) {
    responder(400, 'Identificador invalido');
}

// Validar la firma de Mercado Pago.
$firma = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

if (!$firma || !$requestId) {
    responder(401, 'Firma ausente');
}

$partes = [];

foreach (explode(',', $firma) as $parte) {
    $par = explode('=', trim($parte), 2);

    if (count($par) === 2) {
        $partes[$par[0]] = $par[1];
    }
}

$ts = $partes['ts'] ?? '';
$v1 = $partes['v1'] ?? '';

if (
    !ctype_digit($ts) ||
    !preg_match('/^[a-f0-9]{64}$/i', $v1)
) {
    responder(401, 'Firma invalida');
}

$segundos = (int) $ts;

if ($segundos > 100000000000) {
    $segundos = (int) floor($segundos / 1000);
}

// Evitar notificaciones demasiado antiguas.
if (abs(time() - $segundos) > 600) {
    responder(401, 'Notificacion expirada');
}

$manifest = 'id:' . strtolower($idPago) .
    ';request-id:' . $requestId .
    ';ts:' . $ts . ';';

$firmaEsperada = hash_hmac(
    'sha256',
    $manifest,
    $secret
);

if (!hash_equals($firmaEsperada, strtolower($v1))) {
    responder(401, 'Firma no valida');
}

try {
    // Consultar directamente la API.
    $ch = curl_init(
        'https://api.mercadopago.com/v1/payments/' .
        rawurlencode($idPago)
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ]
    ]);

    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($respuesta === false || $codigo !== 200) {
        throw new RuntimeException(
            'No se pudo consultar el pago'
        );
    }

    $pago = json_decode(
        $respuesta,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $referencia = $pago['external_reference'] ?? '';

    if (
        !is_string($referencia) ||
        !preg_match('/^[a-f0-9]{32}$/', $referencia)
    ) {
        responder(200, 'Referencia desconocida');
    }

    $database = obtenerFirebaseDatabase();

    $ref = $database->getReference(
        'pagosPrueba/' . $referencia
    );

    $registro = $ref->getValue();

    if (!is_array($registro)) {
        responder(200, 'Pago no registrado');
    }

    // Validar que el pago corresponde a
    // nuestra preferencia original.
    $preferenciaGuardada =
        (string)($registro['preferenciaId'] ?? '');

    $preferenciaPago =
        (string)($pago['order']['id'] ?? '');

    // No confiar solamente en order.id,
    // porque no siempre es preference_id.
    // Comprobar la referencia, monto y moneda.
    $montoEsperado =
        (int)($registro['montoCentavos'] ?? 0);

    $montoRecibido = (int) round(
        ((float)($pago['transaction_amount'] ?? 0)) * 100
    );

    if (
        $montoEsperado < 100 ||
        $montoEsperado !== $montoRecibido ||
        ($pago['currency_id'] ?? '') !== 'MXN'
    ) {
        error_log('[DIVIDENDO MP] Monto o moneda incorrectos');
        responder(200, 'Pago no coincide');
    }

    // Este primer paso solamente verifica.
    // NO aumenta la recaudacion.
    if (($pago['status'] ?? '') === 'approved') {
        error_log(
            '[DIVIDENDO MP] Pago de prueba verificado: ' .
            $idPago
        );

        responder(200, 'Pago verificado');
    }

    responder(200, 'Pago no aprobado');

} catch (Throwable $e) {
    error_log(
        '[DIVIDENDO MP] Webhook: ' .
        $e->getMessage()
    );

    responder(500, 'Error al verificar pago');
}
