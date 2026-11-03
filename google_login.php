
<?php

session_start();

require_once 'vendor/autoload.php';
require_once 'bd/conexion.php';

$CLIENT_ID = '1088139853006-cdps7llshndaa283unf04bt3nhq5a1pu.apps.googleusercontent.com';

$client = new Google_Client([
    'client_id' => $CLIENT_ID
]);

if (!isset($_POST['credential'])) {
    exit("Token no recibido");
}

$credential = $_POST['credential'];

try {

    $payload = $client->verifyIdToken($credential);

    if (!$payload) {
        exit("No se pudo verificar la cuenta de Google");
    }

    $googleId = $payload['sub'];
    $email = $payload['email'];
    $name = $payload['name'];

    $avatar = isset($payload['picture'])
        ? $payload['picture']
        : 'logo3.png';

    /*
    |--------------------------------------------------------------------------
    | BUSCAR USUARIO POR EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE Email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $user = $resultado->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | SI NO EXISTE, LO CREAMOS
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $stmt = $conn->prepare(
            "INSERT INTO users
            (Name, Email, Password, Avatar, GoogleID, LoginType)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $password = '';
        $loginType = 'google';

        $stmt->bind_param(
            "ssssss",
            $name,
            $email,
            $password,
            $avatar,
            $googleId,
            $loginType
        );

        $stmt->execute();

        $userId = $conn->insert_id;

        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE id = ?"
        );

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $user = $resultado->fetch_assoc();

        $stmt->close();

    } else {

        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR DATOS DE GOOGLE
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "UPDATE users
             SET GoogleID = ?, LoginType = 'google'
             WHERE id = ?"
        );

        $userId = (int)$user['id'];

        $stmt->bind_param("si", $googleId, $userId);
        $stmt->execute();

        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE id = ?"
        );

        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $user = $resultado->fetch_assoc();

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR SESIÓN
    |--------------------------------------------------------------------------
    */

    if (!$user) {
        exit("No se pudo recuperar la información del usuario");
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['Name'];
    $_SESSION['user_email'] = $user['Email'];
    $_SESSION['user_avatar'] = $user['Avatar'];
    $_SESSION['user_rol'] = $user['rol'];

    echo "success";

} catch (Throwable $e) {

    error_log("Error en Google Login: " . $e->getMessage());
    http_response_code(500);
    echo "Error al iniciar sesión con Google";

}
