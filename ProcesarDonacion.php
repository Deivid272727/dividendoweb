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

$idUsuario = (string) $_SESSION['user_id'];
$idParque = $_POST['parque_id'] ?? '';
$montoTexto = $_POST['monto'] ?? '';

if (
    !is_string($idParque) ||
    $idParque === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $idParque)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}

if (
    !is_string($montoTexto) ||
    !preg_match('/^\d{1,5}(\.\d{1,2})?$/', $montoTexto)
) {
    http_response_code(400);
    exit('Monto inválido.');
}

$montoCentavos = (int) round(((float) $montoTexto) * 100);

if ($montoCentavos < 100 || $montoCentavos > 1000000) {
    http_response_code(400);
    exit('El monto debe estar entre $1 y $10,000.');
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

    $referencia = $database
        ->getReference('donaciones')
        ->push();

    $idDonacion = $referencia->getKey();

    $referencia->set([
        'id' => $idDonacion,
        'parqueId' => $idParque,
        'usuarioId' => $idUsuario,
        'montoCentavos' => $montoCentavos,
        'estado' => 'simulada',
        'fecha' => gmdate('c')
    ]);

   unset($_SESSION['csrf_pago']);

$_SESSION['donacion_exitosa'] = [
    'id' => $idDonacion,
    'parqueId' => $idParque,
    'montoCentavos' => $montoCentavos
];

header(
    'Location: PagoTarj.php?id=' .
    urlencode($idParque) .
    '&resultado=exito',
    true,
    303
);

exit();

} catch (Throwable $e) {
    error_log('Error registrando donación demo: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudo registrar la donación de prueba.');
}
