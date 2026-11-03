<?php
session_start();
require_once 'bd/conexion.php';

$municipio = strtolower(
    str_replace(' ', '', $_GET['municipio'] ?? '')
);
$parques = [];
$stmt=$conn->prepare(
    "SELECT * FROM parques
    WHERE LOWER(REPLACE(municipio, ' ', '')) = ? 
    ");
if (!$stmt) {
    die("Error preparando consulta: " . $conn->error);
}

$stmt->bind_param("s", $municipio);
$stmt->execute();

$resultado = $stmt->get_result();
$parques = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>
 <!-- -->
<?php

$municipio = $_GET['municipio'] ??'';

$parques = [];

try {

    $stmt = $conn->prepare("
        SELECT *
        FROM parques
        WHERE LOWER(REPLACE(municipio,' ','')) = ?
    ");

    $stmt->bind_param("s", $municipio);
$stmt->execute();

$resultado = $stmt->get_result();
$parques = $resultado->fetch_all(MYSQLI_ASSOC);

$stmt->close();

} catch(PDOException $e) {

    echo "Error: " . $e->getMessage();

}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="Css/Style.css">
    <link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
    <title>Dividendo la venganza - Inicio</title>

    <style>
        .header-perfil-contenedor {
            position: absolute;
            top: 15px;
            right: 25px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            z-index: 1000;
            font-family: 'Poppins', sans-serif;
        }

        .btn-avatar-disparador {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .btn-avatar-disparador:hover {
            border-color: #00E676;
            box-shadow: 0 0 10px rgba(0, 230, 118, 0.5);
        }

        .cuadro-perfil-flotante {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            width: 290px;
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0px 8px 30px rgba(0, 0, 0, 0.5);
            padding: 25px 20px;
            text-align: center;
            box-sizing: border-box;
            animation: fadeIn 0.2s ease-in-out;
        }

        .cuadro-perfil-flotante.mostrar-menu {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .avatar-grande-cuadro {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 12px;
            border: 3px solid #00E676;
        }

        .texto-saludo-cuadro {
            font-size: 18px;
            font-weight: 600;
            color: #1d1d1d;
            margin: 0;
        }

        .texto-email-cuadro {
            font-size: 13px;
            color: #666666;
            margin: 5px 0 18px 0;
        }

        .btn-accion-editar {
            display: inline-block;
            text-decoration: none;
            background-color: transparent;
            color: #1d1d1d;
            border: 1px solid #cccccc;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            width: 85%;
            box-sizing: border-box;
        }

        .btn-accion-editar:hover {
            background-color: #f1f3f4;
            border-color: #b0b0b0;
        }

        .linea-separadora-cuadro {
            border: 0;
            border-top: 1px solid #e8eaed;
            margin: 20px 0 10px 0;
        }

        .btn-accion-salir {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: #3c4043;
            font-size: 14px;
            font-weight: 500;
            padding: 10px;
            border-radius: 10px;
            transition: background-color 0.2s;
        }

        .btn-accion-salir:hover {
            background-color: #f7f8f9;
            color: #d93025;
        }

        .parques-container{

    display:flex;
    flex-wrap:wrap;
    gap:35px;
    margin-top:35px;

}

.parque-card{

    width:340px;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 15px 35px rgba(0,0,0,.35);
    transition:.35s;

}

.parque-card:hover{

    transform:translateY(-10px);
    box-shadow:0 25px 45px rgba(0,0,0,.45);

}

.parque-img{

    width:100%;
    height:220px;
    overflow:hidden;
    position:relative;

}

.parque-img img{

    width:100%;
    height:100%;
    object-fit:cover;
    transition:.4s;

}

.parque-card:hover .parque-img img{

    transform:scale(1.08);

}

.parque-etiqueta{

    position:absolute;
    bottom:0;
    left:0;
    width:100%;
    padding:12px;
    background:linear-gradient(transparent,rgba(0,0,0,.9));
    color:white;
    font-size:14px;
    font-weight:bold;

}

.parque-body{

    padding:20px;

}

.parque-body h2{

    margin:0;
    color:#222;
    font-size:24px;

}

.parque-body p{

    color:#666;
    margin:15px 0;
    display:flex;
    align-items:center;
    gap:8px;

}

.parque-body a{

    display:inline-block;
    padding:12px 24px;
    background:#00E676;
    color:white;
    text-decoration:none;
    border-radius:30px;
    transition:.3s;
    font-weight:bold;

}

.parque-body a:hover{

    background:#00c864;

}
    </style>
</head>

<body>

<nav class="sidebar close">
    <header>
        <div class="image-text">
            <span class="image">
                <img src="logo3.png" alt="logo">
            </span>

            <div class="text header-text">
                <span class="name">Y esta mafudada?</span>
                <span class="profession">No se bro</span>
            </div>
        </div>

        <i class="bx bx-chevron-right toggle"></i>
    </header>

    <div class="menu-bar">
        <div class="menu">

            <li class="search-box">
                <i class="bx bx-search icon"></i>
                <input type="search" placeholder="Search...">
            </li>

            <ul class="menu-links">

                <li class="nav-link">
                    <a href="Index.php">
                        <i class="bx bx-home-alt icon"></i>
                        <span class="text nav-text">Casitá</span>
                    </a>
                </li>

                <li class="nav-link">

                    <?php if(isset($_SESSION['user_name'])): ?>

                        <a href="Profile.php">
                            <i class="bx bx-user icon"></i>
                            <span class="text nav-text">Perfil</span>
                        </a>

                    <?php else: ?>

                        <a href="Login.php">
                            <i class="bx bx-log-in icon"></i>
                            <span class="text nav-text">Iniciar Sesión</span>
                        </a>

                    <?php endif; ?>

                </li>

                <li class="nav-link">
                    <a href="municipios.php">
                        <i class='bx bxs-tree-alt icon'></i>
                        <span class="text nav-text">Parques</span>
                    </a>
                </li>

               

            </ul>
        </div>

        <div class="bottom-content">

            <?php if(!isset($_SESSION['user_name'])): ?>

             
            <?php endif; ?>

            <li class="mode">
                <div class="moon-sun">
                    <i class="bx bx-moon icon moon"></i>
                    <i class="bx bx-sun icon sun"></i>
                </div>

                <span class="mode-text text">Dark Mode</span>

                <div class="toggle-switch">
                    <span class="switch"></span>
                </div>
            </li>

        </div>
    </div>
</nav>

<section class="home">

    <div class="header-perfil-contenedor">

        <img
            src="<?php echo isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'Default.png'; ?>"
            alt="Perfil"
            class="btn-avatar-disparador"
            id="avatar-disparador-id"
        >

        <div class="cuadro-perfil-flotante" id="cuadro-perfil-id">

            <img
                src="<?php echo isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'Default.png'; ?>"
                alt="Avatar Grande"
                class="avatar-grande-cuadro"
            >

            <h3 class="texto-saludo-cuadro">
                ¡Hola,
                <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Invitado'; ?>!
            </h3>

            <p class="texto-email-cuadro">
                <?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'Sin iniciar sesión'; ?>
            </p>

            <?php if(isset($_SESSION['user_name'])): ?>

                <a href="Profile.php" class="btn-accion-editar">
                    Administrar tu Cuenta
                </a>

            <?php else: ?>

                <a href="Login.php" class="btn-accion-editar">
                    Iniciar Sesión
                </a>

            <?php endif; ?>

            <hr class="linea-separadora-cuadro">

            <?php if(isset($_SESSION['user_name'])): ?>

                <a href="Logout.php" class="btn-accion-salir">
                    <i class='bx bx-log-out'></i> Salir
                </a>

            <?php endif; ?>

        </div>
    </div>

<div class="text">
    Parques de <?php echo ucfirst($municipio); ?>
</div>

<div class="parques-container">

<?php foreach($parques as $parque): ?>

<div class="parque-card">

    <div class="parque-img">

        <img src="imagenes/idk.jpg" alt="">

        <div class="parque-etiqueta">
            🌳 <?php echo ucfirst($parque['municipio']); ?>
        </div>

    </div>

    <div class="parque-body">

        <h2><?php echo $parque['titulo']; ?></h2>

        <p>
            <i class='bx bx-map'></i>
            <?php echo $parque['direccion']; ?>
        </p>

        <a href="DetalleParque.php?id=<?php echo $parque['id']; ?>">
            Ver más
        </a>

    </div>

</div>

<?php endforeach; ?>

</div>
</section>

<script src="script.js"></script>

<script>
    const avatarDisparador = document.getElementById('avatar-disparador-id');
    const cuadroPerfil = document.getElementById('cuadro-perfil-id');

    avatarDisparador.addEventListener('click', (e) => {
        cuadroPerfil.classList.toggle('mostrar-menu');
        e.stopPropagation();
    });

    document.addEventListener('click', (e) => {
        if (!cuadroPerfil.contains(e.target) && e.target !== avatarDisparador) {
            cuadroPerfil.classList.remove('mostrar-menu');
        }
    });
</script>

</body>
</html>