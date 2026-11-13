<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

use Kreait\Firebase\Factory;

// Verificar que el usuario haya iniciado sesión
if (empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Solo permitir solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Profile.php");
    exit();
}

$id_usuario = $_SESSION['user_id'];
$nuevo_nombre = trim($_POST['nuevo_nombre'] ?? '');

// Validar el nombre
if ($nuevo_nombre === '' || mb_strlen($nuevo_nombre) > 50) {
    http_response_code(400);
    exit("El nombre debe contener entre 1 y 50 caracteres.");
}

try {
    // Obtener credenciales de Firebase
    $credenciales = getenv('FIREBASE_CREDENTIALS_JSON');

    if (!$credenciales) {
        throw new RuntimeException(
            'No se encontraron las credenciales de Firebase.'
        );
    }

    $factory = (new Factory())
        ->withServiceAccount(json_decode($credenciales, true))
        ->withDatabaseUri(
            'https://dividendoplus-app-default-rtdb.firebaseio.com/'
        );

    // Conectar con Firebase Authentication
    $firebaseAuth = $factory->createAuth();

    // Actualizar únicamente el nombre en Authentication
    $firebaseAuth->updateUser($id_usuario, [
        'displayName' => $nuevo_nombre
    ]);

    // Conectar con Realtime Database
    $database = $factory->createDatabase();

    // Actualizar únicamente el campo nombre
    $database
        ->getReference('users/' . $id_usuario)
        ->update([
            'nombre' => $nuevo_nombre
        ]);

    // Actualizar la sesión PHP
    $_SESSION['user_name'] = $nuevo_nombre;

    // Regresar al perfil
    header("Location: Profile.php");
    exit();

} catch (Throwable $e) {

    error_log(
        'Error al actualizar perfil: ' . $e->getMessage()
    );

    http_response_code(500);
    exit("No se pudo actualizar el perfil. Intenta nuevamente.");
}
?>
