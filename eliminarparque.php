<?php
session_start();
require_once __DIR__ . '/config/firebase.php';

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

// Verificar token CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_admin']) ||
    !hash_equals($_SESSION['csrf_admin'], $token)
) {
    http_response_code(403);
    exit('Solicitud no autorizada.');
}

// Validar identificador del parque
$id = $_POST['id'] ?? '';

if (
    !is_string($id) ||
    $id === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $id)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}

try {
    $database = obtenerFirebaseDatabase();

    // Verificar que el usuario es administrador
    $rol = $database
        ->getReference('users/' . $_SESSION['user_id'] . '/rol')
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    // Buscar el parque
    $referencia = $database
        ->getReference('parques/' . $id);

    $parque = $referencia->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    // Eliminar parque de Firebase
    $referencia->remove();

    header('Location: Admin.php?eliminado=1');
    exit();

} catch (Throwable $e) {
    error_log(
        'Error eliminando parque: ' . $e->getMessage()
    );

    http_response_code(500);
    exit('No se pudo eliminar el parque.');
}
