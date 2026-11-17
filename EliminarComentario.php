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

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_comentarios']) ||
    !hash_equals($_SESSION['csrf_comentarios'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

$idComentario = $_POST['comentario_id'] ?? '';
$idParque = $_POST['parque_id'] ?? '';

foreach ([$idComentario, $idParque] as $valor) {
    if (
        !is_string($valor) ||
        $valor === '' ||
        preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $valor)
    ) {
        http_response_code(400);
        exit('Identificador inválido.');
    }
}

try {
    $database = obtenerFirebaseDatabase();

    // Verificar permisos desde Firebase
    $rol = $database
        ->getReference('users/' . $_SESSION['user_id'] . '/rol')
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    $referencia = $database
        ->getReference('comentarios/' . $idComentario);

    $comentario = $referencia->getValue();

    if (
        !is_array($comentario) ||
        (string)($comentario['parqueId'] ?? '') !== $idParque
    ) {
        http_response_code(404);
        exit('Comentario no encontrado.');
    }

    $referencia->remove();

    header(
        'Location: DetalleParque.php?id=' .
        urlencode($idParque) .
        '&eliminado=1'
    );
    exit();

} catch (Throwable $e) {
    error_log('Error eliminando comentario: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudo eliminar el comentario.');
}
