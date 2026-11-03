<?php
session_start();
require_once 'bd/conexion.php'; 

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 3; 
}

$id_usuario = $_SESSION['user_id'];
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_perfil'])) {
    $archivo = $_FILES['archivo_perfil'];

    if ($archivo['error'] === 0) {
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        
        $nuevo_nombre = "user_" . $id_usuario . "_" . $accion . "_" . time() . "." . $extension;
        $directorio_destino = 'uploads/';
        
        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }

        $ruta_final = $directorio_destino . $nuevo_nombre;

        if (move_uploaded_file($archivo['tmp_name'], $ruta_final)) {
            
            if ($accion === 'avatar') {
                // Guarda en la columna 'Avatar' de la tabla 'users'
                $stmt = $pdo->prepare("UPDATE users SET Avatar = ? WHERE id = ?");
                $stmt->execute([$ruta_final, $id_usuario]);
                $_SESSION['user_avatar'] = $ruta_final;

            } elseif ($accion === 'fondo') {
                // Guarda en la columna 'fondo' de la tabla 'users'
                $stmt = $pdo->prepare("UPDATE users SET fondo = ? WHERE id = ?");
                $stmt->execute([$ruta_final, $id_usuario]);
                $_SESSION['user_fondo'] = $ruta_final;
            }

            header("Location: Profile.php");
            exit();
        } else {
            echo "Error al mover el archivo a la carpeta uploads.";
        }
    } else {
        echo "Error de carga de imagen. Código: " . $archivo['error'];
    }
}

header("Location: Profile.php");
exit();
?>