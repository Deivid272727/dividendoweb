<?php
session_start();

if(!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 'admin')

{
    die("Acceso denegado.");
}


require_once 'bd/conexion.php';

if(isset($_POST['agregar_parque']))
{
    $titulo = $_POST['titulo'];
    $precio = $_POST['precio'];
    $direccion = $_POST['direccion'];
    $ubicacion = $_POST['ubicacion'];
    $info1 = $_POST['info1'];
    $info2 = $_POST['info2'];
    $municipio = $_POST['municipio'];
    $sql = "INSERT INTO parques
            (titulo, precio, direccion, ubicacion, info1, info2, municipio)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

 $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error preparando parque: " . $conn->error);
    }
    $stmt->bind_param(
        "dssssss",
        $precio,
        $direccion,
        $ubicacion,
        $info1,
        $info2,
        $municipio,
        $titulo
    );

$stmt->execute();
    $stmt->close();

    // Registrar actividad
    $stmtLog = $conn->prepare("
        INSERT INTO logs (user_id, accion, ip)
        VALUES (?, ?, ?)
    ");

    if (!$stmtLog) {
        die("Error preparando registro: " . $conn->error);
    }

    $userId = (int)$_SESSION['user_id'];
    $accion = 'Agregó un parque';
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmtLog->bind_param("iss", $userId, $accion, $ip);
    $stmtLog->execute();
    $stmtLog->close();

    echo "<p>Parque agregado correctamente</p>";
}
?>


<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel de Administración</title>

    <link rel="stylesheet" href="css/admin.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<?php
/* Si ya tienes un navbar o sidebar inclúyelo aquí */
// include 'navbar.php';
?>

<div class="admin-container">

   <div class="header">

    <div>

        <h1>
            <i class="fa-solid fa-user-shield"></i>
            Panel de Administración
        </h1>

        <p>
            Bienvenido
            <span class="usuario">
                <?php echo $_SESSION['user_name']; ?>
            </span>
        </p>

    </div>

   <div class="header-buttons">

    <a href="index.php" class="btn-regresar">

        <i class="fa-solid fa-arrow-left"></i>

        Regresar

    </a>

    <button id="toggle-dark" class="modo-btn">

        <i class="fa-solid fa-moon"></i>

    </button>

</div>

</div>

    </div>

    <div class="card">

        <h2>
            <i class="fa-solid fa-tree"></i>
            Agregar Parque
        </h2>

        <form method="POST">

            <div class="grupo">

                <label>Nombre del parque</label>

                <input
                    type="text"
                    name="titulo"
                    required>

            </div>

            <div class="grupo">

                <label>Municipio</label>

                <select name="municipio">

                    <option value="Monterrey">Monterrey</option>
                    <option value="Guadalupe">Guadalupe</option>
                    <option value="San Nicolás">San Nicolás</option>
                    <option value="Apodaca">Apodaca</option>
                    <option value="Escobedo">Escobedo</option>
                    <option value="Santa Catarina">Santa Catarina</option>
                    <option value="García">García</option>

                </select>

            </div>

            <div class="grupo">

                <label>Meta de donación</label>

                <input
                    type="number"
                    name="precio"
                    required>

            </div>

            <div class="grupo">

                <label>Ubicación Google Maps</label>

                <input
                    type="text"
                    name="ubicacion">

            </div>

            <button
                class="guardar"
                type="submit"
                name="agregar_parque">

                <i class="fa-solid fa-plus"></i>

                Agregar Parque

            </button>

        </form>

    </div>

    <div class="card">

        <h2>

            <i class="fa-solid fa-list"></i>

            Parques registrados

        </h2>

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Parque</th>

                    <th>Municipio</th>

                    <th>Meta</th>

                    <th>Recaudado</th>

                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>

            <?php

              $resultado = $conn->query("SELECT * FROM parques");

            if (!$resultado) {
             die("Error consultando parques: " . $conn->error);
            }

            while ($parque = $resultado->fetch_assoc()) 
            {

            ?>

            <tr>

                <td><?php echo $parque['id']; ?></td>

                <td><?php echo htmlspecialchars($parque['titulo']); ?></td>

                <td><?php echo htmlspecialchars($parque['municipio']); ?></td>

                <td>$<?php echo number_format($parque['precio']); ?></td>

                <td>$<?php echo number_format($parque['recaudado']); ?></td>

                <td>

                    <a
                    class="eliminar"
                    href="eliminarparque.php?id=<?php echo $parque['id']; ?>">

                        <i class="fa-solid fa-trash"></i>

                        Eliminar

                    </a>

                </td>

            </tr>

            <?php
            }
            ?>

            </tbody>

        </table>

    </div>

</div>

<!-- Tu JavaScript del Dark Mode -->
<script src="admin.js"></script>

</body>

</html>