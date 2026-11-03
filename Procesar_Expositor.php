<?php
session_start();
require_once 'bd/conexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Sesión no válida.");
}

$id_usuario = $_SESSION['user_id'];
$numero_cuadro = intval($_POST['numero_cuadro']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['imagen_archivo'])) {
    $archivo = $_FILES['imagen_archivo'];
    
    // Validar que no haya errores de subida
    if ($archivo['error'] === 0) {
        $nombre_original = $archivo['name'];
        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        
        // Creamos un nombre único para evitar que se pisen los archivos
        $nuevo_nombre = "user_" . $id_usuario . "_cuadro_" . $numero_cuadro . "_" . time() . "." . $extension;
        
        $directorio_destino = 'uploads/';
        // Crear la carpeta si no existe en tu servidor
        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }
        
        $ruta_final = $directorio_destino . $nuevo_nombre;
        
        // Movemos el archivo temporal a nuestra carpeta real
        if (move_uploaded_file($archivo['tmp_name'], $ruta_final)) {
            
            // Revisamos si el registro de este cuadro ya existe para el usuario
            $stmt = $pdo->prepare("SELECT id_expositor FROM exposition WHERE id_usuario = ? AND numero_cuadro = ?");
            $stmt->execute([$id_usuario, $numero_cuadro]);
            $existe = $stmt->fetch();
            
            if ($existe) {
                // Si existe, actualizamos la ruta de la imagen vieja por la nueva
                $update = $pdo->prepare("UPDATE exposition  SET imagen = ? WHERE id_usuario = ? AND numero_cuadro = ?");
                $update->execute([$ruta_final, $id_usuario, $numero_cuadro]);
            } else {
                // Si es la primera vez que sube este cuadro, lo insertamos completo
                $insert = $pdo->prepare("INSERT INTO exposition (id_usuario, imagen, numero_cuadro) VALUES (?, ?, ?)");
                $insert->execute([$id_usuario, $ruta_final, $numero_cuadro]);
            }
            
            // Todo salió bien, regresamos a la página de configuración
            header("Location: Profile.php");
            exit();
        } else {
            echo "Error al mover el archivo al servidor.";
        }
    } else {
        echo "Error en la carga del archivo.";
    }
}
?>