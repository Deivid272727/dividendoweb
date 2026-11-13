<?php
session_start();

// Eliminar los datos de la sesión PHP
$_SESSION = [];

// Eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión PHP
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrando sesión - Dividendo</title>
</head>
<body>
    <p>Cerrando sesión...</p>

    <script type="module">
        import { auth } from "./firebase-config.js";

        import {
            signOut
        } from "https://www.gstatic.com/firebasejs/12.6.0/firebase-auth.js";

        try {
            await signOut(auth);
        } catch (error) {
            console.error("Error al cerrar Firebase:", error);
        }

        window.location.replace("Login.php");
    </script>
</body>
</html>
