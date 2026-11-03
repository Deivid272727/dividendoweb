<?php

include(__DIR__ . "/bd/conexion.php");

if(!isset($_GET['token'])){
    die("Token no encontrado");
}

$token = $_GET['token'];

$sql = "SELECT * FROM users WHERE token = ?";

$stmt = $pdo->prepare($sql);

$stmt->execute([$token]);

$user = $stmt->fetch();

if(!$user){
    die("Token inválido");
}

/* CAMBIAR CONTRASEÑA */

if(isset($_POST['password'])){

    $password = $_POST['password'];

    $passwordHash = password_hash(
    $password,
    PASSWORD_BCRYPT
);

    $update = $pdo->prepare(
        "UPDATE users
        SET Password = ?,
        token = ''
        WHERE token = ?"
    );

   $update->execute([$passwordHash, $token]);

    echo "
    <script>

    alert('Contraseña actualizada');

    window.location='Login.php';

    </script>
    ";
}
?>

<!DOCTYPE html>

<html lang='es'>

<head>

<meta charset='UTF-8'>

<title>Restablecer contraseña</title>

<style>

body{
    font-family: Arial;
    background: #f5f5f5;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.box{
    width: 350px;
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0px 8px 25px rgba(0,0,0,0.2);
}

input{
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border-radius: 10px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

button{
    width: 100%;
    padding: 12px;
    margin-top: 20px;
    border: none;
    border-radius: 10px;
    background: #00E676;
    color: white;
    cursor: pointer;
}

button:hover{
    background: #00c853;
}

</style>

</head>

<body>

<div class="box">

<h2>Nueva contraseña</h2>

<form method="POST">

<input
type="password"
name="password"
placeholder="Nueva contraseña"
required>

<button type="submit">
Cambiar contraseña
</button>

</form>

</div>

</body>

</html>