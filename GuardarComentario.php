<?php
session_start();
require_once "bd/conexion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario_id = $_POST["usuario_id"];
    $autor_id = $_SESSION["user_id"];
    $comentario = trim($_POST["comentario"]);

    if ($comentario != "") {

        $stmt = $pdo->prepare("
            INSERT INTO comentarios (usuario_id, autor_id, comentario)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $usuario_id,
            $autor_id,
            $comentario
        ]);
    }

    header("Location: User.php?id=" . $usuario_id);
    exit();
}