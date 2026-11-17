<?php
session_start();

require_once __DIR__ . '/config/firebase.php';
require_once __DIR__ . '/config/censura.php';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Protección CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['csrf_comentarios']) ||
    !hash_equals($_SESSION['csrf_comentarios'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

$idParque = $_POST['parque_id'] ?? '';
$comentario = $_POST['comentario'] ?? '';

if (
    !is_string($idParque) ||
    $idParque === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $idParque)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}

if (!is_string($comentario)) {
    http_response_code(400);
    exit('Comentario inválido.');
}

$comentario = trim($comentario);

if (
    $comentario === '' ||
    mb_strlen($comentario, 'UTF-8') > 500
) {
    http_response_code(400);
    exit('El comentario debe tener entre 1 y 500 caracteres.');
}

// Aplicar censura antes de guardar
$comentario = censurarComentario($comentario);

try {
    $database = obtenerFirebaseDatabase();

    $parque = $database
        ->getReference('parques/' . $idParque)
        ->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    $usuarioId = (string)$_SESSION['user_id'];

    $usuario = $database
        ->getReference('users/' . $usuarioId)
        ->getValue();

    if (!is_array($usuario)) {
        http_response_code(403);
        exit('Usuario no encontrado.');
    }

    $referencia = $database
        ->getReference('comentarios')
        ->push();

    $referencia->set([
        'id' => $referencia->getKey(),
        'parqueId' => $idParque,
        'usuarioId' => $usuarioId,
        'comentario' => $comentario,
        'fecha' => gmdate('c')
    ]);

    header(
        'Location: DetalleParque.php?id=' .
        urlencode($idParque) .
        '&comentario=ok'
    );
    exit();

} catch (Throwable $e) {
    error_log(
        'Error guardando comentario: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudo guardar el comentario.');
}
