<?php
session_start();
require_once 'bd/conexion.php'; 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3; 
}

$id_usuario = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nuevo_nombre']) && isset($_POST['nuevo_email'])) {
    
    $nuevo_nombre = trim($_POST['nuevo_nombre']);
    $nuevo_email = trim($_POST['nuevo_email']);

    if (!empty($nuevo_nombre) && !empty($nuevo_email)) {
        
        // Actualiza las columnas 'Name' y 'Email' de tu tabla 'users'
        $stmt = $pdo->prepare("UPDATE users SET Name = ?, Email = ? WHERE id = ?");
        $stmt->execute([$nuevo_nombre, $nuevo_email, $id_usuario]);

        // Seteamos las sesiones globales para refrescar la vista en caliente
        $_SESSION['user_name'] = $nuevo_nombre;
        $_SESSION['user_email'] = $nuevo_email;

        header("Location: Profile.php");
        exit();
    } else {
        echo "Los campos de texto no pueden ir vacíos.";
    }
} else {
    header("Location: Profile.php");
    exit();
}
?>