<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/firebase.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Recibir el token enviado desde JavaScript
    $datos = json_decode(file_get_contents('php://input'), true);
    $idToken = $datos['idToken'] ?? '';

    if (empty($idToken)) {
        throw new Exception('No se recibió el token de Firebase.');
    }

    // Utilizar las credenciales del servidor
    $credenciales = getenv('FIREBASE_CREDENTIALS_JSON');

    if (!$credenciales) {
        throw new Exception('No se encontraron las credenciales de Firebase.');
    }

    $factory = (new Factory())
        ->withServiceAccount(json_decode($credenciales, true));

    $firebaseAuth = $factory->createAuth();

    // Verificar que el token sea auténtico
    $verifiedToken = $firebaseAuth->verifyIdToken($idToken);

    // Obtener el UID verificado
    $uid = $verifiedToken->claims()->get('sub');

    // Consultar los datos del usuario
    $usuario = $firebaseAuth->getUser($uid);

    // Evitar reutilizar una sesión anterior
    session_regenerate_id(true);

    // Crear sesión PHP
    $_SESSION['user_id'] = $uid;
    $_SESSION['user_name'] = $usuario->displayName ?? 'Usuario';
    $_SESSION['user_email'] = $usuario->email ?? '';
    $_SESSION['user_avatar'] = $usuario->photoUrl ?? 'logo2.png';


    // Consultar el rol real del usuario en Firebase Realtime Database
$database = obtenerFirebaseDatabase();
    $rolFirebase = $database
        ->getReference('users/' . $uid . '/rol')
        ->getValue();

    // Solo conceder admin si Firebase lo confirma
    $_SESSION['user_rol'] = ($rolFirebase === 'admin')
        ? 'admin'
        : 'user';


    echo json_encode([
        'success' => true,
        'message' => 'Sesión iniciada correctamente'
    ]);

} catch (FailedToVerifyToken $e) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Token de autenticación inválido.'
    ]);

} catch (Throwable $e) {
    error_log('Error de sesión Firebase: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo iniciar la sesión.'
    ]);
}
