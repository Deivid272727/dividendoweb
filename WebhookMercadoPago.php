<?php
require_once __DIR__ . '/config/firebase.php';

use Kreait\Firebase\Database\Transaction;
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, string $mensaje): void
{
    $mensajesDiagnostico = [
        'Pago fuera del entorno de pruebas',
        'Referencia desconocida',
        'Referencia no registrada',
        'Pago no coincide',
        'Preferencia no coincide',
        'Referencia asociada a otro pago',
        'Pago no aprobado',
        'Donacion de prueba verificada'
    ];

    if (in_array($mensaje, $mensajesDiagnostico, true)) {
        error_log('[DIVIDENDO MP] Resultado: ' . $mensaje);
    }

    http_response_code($codigo);
    echo json_encode(['mensaje' => $mensaje]);
    exit;
}

function idFirebaseValido($valor): bool
{
    return is_string($valor)
        && $valor !== ''
        && !preg_match('/[.#$\/\[\]\x00-\x1F\x7F]/', $valor);
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

// Obtener el ID de pago enviado por Mercado Pago.
$idPago = $_GET['data.id']
    ?? $_GET['data_id']
    ?? null;

if (
    !is_string($idPago) ||
    !preg_match('/^[0-9]{1,30}$/', $idPago)
) {
    responder(400, 'Identificador invalido');
}

// Validar firma.
$firma = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';

if ($firma === '' || $requestId === '') {
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

$segundos = (int)$ts;

if ($segundos > 100000000000) {
    $segundos = (int)floor($segundos / 1000);
}

if (abs(time() - $segundos) > 600) {
    responder(401, 'Notificacion expirada');
}

$manifest = 'id:' . strtolower($idPago)
    . ';request-id:' . $requestId
    . ';ts:' . $ts . ';';

$firmaEsperada = hash_hmac(
    'sha256',
    $manifest,
    $secret
);

if (!hash_equals($firmaEsperada, strtolower($v1))) {
    responder(401, 'Firma no valida');
}

try {
    // Consultar el pago directamente con Mercado Pago.
    $ch = curl_init(
        'https://api.mercadopago.com/v1/payments/'
        . rawurlencode($idPago)
    );

    if ($ch === false) {
        throw new RuntimeException('No se pudo iniciar cURL');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ]
    ]);

    $respuesta = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($respuesta === false || $codigoHttp !== 200) {
        throw new RuntimeException(
            'Error consultando Mercado Pago: HTTP ' . $codigoHttp
        );
    }

    $pago = json_decode(
        $respuesta,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($pago)) {
        throw new RuntimeException('Respuesta de pago invalida');
    }

    // Confirmar que el ID consultado coincide.
    if ((string)($pago['id'] ?? '') !== $idPago) {
        throw new RuntimeException('ID de pago no coincide');
    }

    // Exclusivamente operaciones de prueba.
    if (($pago['live_mode'] ?? null) !== false) {
        responder(200, 'Pago fuera del entorno de pruebas');
    }

    $referencia = $pago['external_reference'] ?? '';

    if (
        !is_string($referencia) ||
        !preg_match('/^[a-f0-9]{32}$/', $referencia)
    ) {
        responder(200, 'Referencia desconocida');
    }

    $database = obtenerFirebaseDatabase();

    $pagoRef = $database->getReference(
        'pagosPrueba/' . $referencia
    );

    $registro = $pagoRef->getValue();

    if (!is_array($registro)) {
        responder(200, 'Referencia no registrada');
    }

    // Validar monto y moneda.
    $montoEsperado = (int)($registro['montoCentavos'] ?? 0);

    $montoRecibido = (int)round(
        ((float)($pago['transaction_amount'] ?? 0)) * 100
    );

    if (
        $montoEsperado < 100 ||
        $montoEsperado > 1000000 ||
        $montoEsperado !== $montoRecibido ||
        ($pago['currency_id'] ?? '') !== 'MXN'
    ) {
        error_log('[DIVIDENDO MP] Monto o moneda incorrectos');
        responder(200, 'Pago no coincide');
    }

    // Validar la preferencia cuando el pago la informa.
    $preferenciaGuardada = (string)(
        $registro['preferenciaId'] ?? ''
    );

    if ($preferenciaGuardada === '') {
        throw new RuntimeException('Preferencia no registrada');
    }

    $preferenciaInformada = $pago['preference_id'] ?? null;

    if (
        $preferenciaInformada !== null &&
        (string)$preferenciaInformada !== $preferenciaGuardada
    ) {
        error_log('[DIVIDENDO MP] Preferencia diferente');
        responder(200, 'Preferencia no coincide');
    }

    // No asociar otra operacion a la misma referencia.
    if (
        !empty($registro['pagoId']) &&
        (string)$registro['pagoId'] !== $idPago
    ) {
        responder(200, 'Referencia asociada a otro pago');
    }

    // Solo registrar pagos aprobados.
    if (($pago['status'] ?? '') !== 'approved') {
        responder(200, 'Pago no aprobado');
    }

    $idParque = $registro['parqueId'] ?? null;
    $idUsuario = $registro['usuarioId'] ?? null;

    if (
        !idFirebaseValido($idParque) ||
        !idFirebaseValido($idUsuario)
    ) {
        throw new RuntimeException('Parque o usuario invalido');
    }

    $parque = $database
        ->getReference('parques/' . $idParque)
        ->getValue();

    if (!is_array($parque)) {
        throw new RuntimeException('Parque no encontrado');
    }

    // ID deterministico: una referencia, una donacion.
    $idDonacion = 'mp_' . $referencia;

    $donacionRef = $database->getReference(
        'donaciones/' . $idDonacion
    );

   
    // Registrar la donacion una sola vez.
    // La transaccion comprueba y escribe sobre
    // la misma referencia de Firebase.

    $database->runTransaction(
        function (Transaction $transaction) use (
            $donacionRef,
            $idDonacion,
            $idParque,
            $idUsuario,
            $montoEsperado,
            $idPago,
            $referencia
        ) {
            $existente = $transaction->snapshot($donacionRef)->getValue();

            if ($existente !== null) {
                // Una referencia no puede representar
                // dos pagos diferentes.
                if (
                    !is_array($existente) ||
                    (string)($existente['pagoId'] ?? '') !== $idPago ||
                    (string)($existente['referenciaExterna'] ?? '') !== $referencia ||
                    (string)($existente['parqueId'] ?? '') !== $idParque ||
                    (string)($existente['usuarioId'] ?? '') !== $idUsuario ||
                    (int)($existente['montoCentavos'] ?? 0) !== $montoEsperado ||
                    ($existente['estado'] ?? '') !== 'aprobada_prueba'
                ) {
                    throw new RuntimeException(
                        'Conflicto con una donacion existente'
                    );
                }

                // Ya fue registrada correctamente.
                return;
            }

            $transaction->set($donacionRef, [
                'id' => $idDonacion,
                'parqueId' => $idParque,
                'usuarioId' => $idUsuario,
                'montoCentavos' => $montoEsperado,
                'estado' => 'aprobada_prueba',
                'fecha' => gmdate('c'),
                'pagoId' => $idPago,
                'referenciaExterna' => $referencia
            ]);
        }
    );

    // Actualizar el estado despues de asegurar
    // que la donacion ya existe.
    $pagoRef->update([
        'estado' => 'verificado',
        'pagoId' => $idPago,
        'fechaVerificacion' => gmdate('c')
    ]);

    error_log(
        '[DIVIDENDO MP] Donacion de prueba verificada: ' .
        $idPago
    );

    responder(200, 'Donacion de prueba verificada');

} catch (Throwable $e) {
    error_log(
        '[DIVIDENDO MP] Webhook: ' . $e->getMessage()
    );

    responder(500, 'Error al verificar pago');
}

