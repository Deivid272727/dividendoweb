<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

include(__DIR__ . "/bd/conexion.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if(isset($_POST['email'])){

    $email = $_POST['email'];

    $sql = "SELECT * FROM users WHERE Email = ?";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if($user){

        $token = bin2hex(random_bytes(50));

        $update = $pdo->prepare(
            "UPDATE users
            SET token = ?
            WHERE Email = ?"
        );

        $update->execute([$token, $email]);

        $link =
        "http://localhost/xampp/Prueba_1/reset.php?token=$token";

        $mail = new PHPMailer(true);

        try{

            $mail->isSMTP();

            $mail->Host = 'smtp.gmail.com';

            $mail->SMTPAuth = true;

            $mail->Username =
            'diviendo.lareunion@gmail.com';

            $mail->Password =
            'twze sgts dvyk eykt';

            $mail->SMTPSecure = 'tls';

            $mail->Port = 587;

            $mail->setFrom(
                'diviendo.lareunion@gmail.com',
                'Dividendo Support'
            );

            $mail->addAddress($email);

            $mail->isHTML(true);

            $mail->Subject =
            'Restablece tu Password';

            $mail->Body = "

            <div style='
            font-family:Arial,sans-serif;
            max-width:600px;
            margin:auto;
            padding:30px;
            background:#181818;
            color:white;
            border-radius:15px;
            '>

            <h1 style='
            color:#00E676;
            margin-bottom:20px;
            '>
            Hola
            </h1>

            <p style='
            font-size:16px;
            color:#f5f5f5;
            line-height:1.6;
            '>

            Recibimos una solicitud para
            restablecer la password
            de tu cuenta.

            </p>

            <p style='
            font-size:16px;
            color:#f5f5f5;
            line-height:1.6;
            '>

            Presiona el boton de abajo
            para continuar:

            </p>

            <br>

            <a href='$link'
            style='
            background:#00E676;
            color:white;
            padding:14px 22px;
            text-decoration:none;
            border-radius:10px;
            display:inline-block;
            font-weight:bold;
            font-size:16px;
            '>

            Cambiar Password

            </a>

            <br><br>

            <p style='
            color:#cfcfcf;
            font-size:14px;
            line-height:1.5;
            '>

            Si no solicitaste este cambio,
            puedes ignorar este mensaje.

            </p>

            <div style='
            margin-top:25px;
            border-top:1px solid #333;
            padding-top:15px;
            '>

            <p style='
            font-size:11px;
            color:#aaaaaa;
            line-height:1.6;
            text-align:justify;
            '>

            <b>
            PROTECCION DE DATOS PERSONALES
            </b>

            <br><br>

            La informacion contenida en este correo
            es confidencial y esta destinada
            unicamente al usuario receptor.

            Los datos personales utilizados
            en este proceso seran tratados
            conforme a las politicas
            de privacidad del sistema.

            Queda prohibida la divulgacion,
            distribucion o reproduccion total
            o parcial de este mensaje
            sin autorizacion previa.

            Este correo fue generado
            automaticamente por el sistema
            de recuperacion de cuentas
            de Dividendo.

            </p>

            </div>

            <br><br>

            <div style='text-align:center;'>

            <img
            src='https://i.pinimg.com/736x/11/a5/90/11a5900fe006dbad1a8fca65e2dab6ec.jpg'
            width='120'
            style='border-radius:10px;'
            >

            </div>

            </div>
            ";

            $mail->AltBody =
            "Usa este enlace para cambiar tu Password: $link";

            $mail->send();

           echo "
<script>

alert('Correo enviado correctamente');

window.location='Index.php';

</script>
";

        }catch(Exception $e){

            echo "
            <script>

            alert('Error al enviar correo');

            </script>
            ";

        }

    }else{

        echo "
        <script>

        alert('Correo no encontrado');

        </script>
        ";

    }
}
?>

<html lang='es'>

<head>

<meta charset='UTF-8'>

<title>
Recuperador
</title>

<style>

body{

    margin:0;
    padding:0;
    background:#111;
    font-family:Arial;
    color:white;
}

.home{

    width:100%;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.recover-box form{

    width:350px;
    background:#1e1e1e;
    padding:30px;
    border-radius:20px;
    text-align:center;
    box-shadow:0px 8px 25px rgba(0,0,0,0.4);
}

.recover-box h2{

    color:#00E676;
}

.recover-box p{

    color:#ccc;
}

.recover-box input{

    width:100%;
    padding:12px;
    margin-top:10px;
    margin-bottom:20px;
    border:none;
    border-radius:10px;
    background:#2b2b2b;
    color:white;
    box-sizing:border-box;
}

.recover-box button{

    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#00E676;
    color:white;
    font-size:15px;
    cursor:pointer;
}

</style>

</head>

<body>

<section class='home'>

<div class='recover-box'>

<form method='POST'>

<h2>
¿Olvidaste tu Password?
</h2>

<p>
Ingresa tu correo electrónico
para recuperar tu cuenta
</p>

<input
type='email'
name='email'
placeholder='Correo electrónico'
required
>

<button type='submit'>

Recuperar Password
</button>


</form>

</div>

</section>

</body>

</html>