<?php

session_start();

include("bd/conexion.php");

$Email = $_POST['Email'];

$Password = $_POST['Password'];

/* BUSCAR USUARIO */

$sql = "SELECT * FROM users WHERE Email = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$Email]);

$user = $stmt->fetch();

/* VALIDAR */

if($user && password_verify($Password, $user['Password'])){

    $_SESSION['Name'] = $user['Name'];

    $_SESSION['user_name'] = $user['Name'];

    $_SESSION['user_email'] = $user['Email'];

    $_SESSION['user_avatar'] = $user['Avatar'];

    header("Location: Index.php");

    exit();

}else{

    include("Login.php");

    echo "
    <h1 class='bad'>
    Credenciales incorrectas de prro :v
    </h1>
    ";
}
?>