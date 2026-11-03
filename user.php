<?php
session_start();
require_once 'bd/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: Index.php");
    exit();
}

$id_usuario = $_GET['id'];

/* USUARIO */
$stmtUser = $pdo->prepare("
SELECT Name, Email, Avatar, fondo
FROM users
WHERE id = ?
");

$stmtUser->execute([$id_usuario]);

$usuario = $stmtUser->fetch();

if (!$usuario) {
    header("Location: Index.php");
    exit();
}

/* AVATAR */
$avatar = !empty($usuario['Avatar'])
? $usuario['Avatar']
: 'uploads/default_avatar.png';

/* FONDO */
$fondo = !empty($usuario['fondo'])
? $usuario['fondo']
: 'uploads/default_banner.png';


/* GALERÍA */
$cuadro1 = "uploads/default_game.png";
$cuadro2 = "uploads/default_game.png";

$stmtExpo = $pdo->prepare("
SELECT numero_cuadro, imagen
FROM exposition
WHERE id_usuario = ?
");

$stmtExpo->execute([$id_usuario]);

$imagenes = $stmtExpo->fetchAll();

foreach ($imagenes as $img) {

    if ($img['numero_cuadro'] == 1) {
        $cuadro1 = $img['imagen'];
    }

    if ($img['numero_cuadro'] == 2) {
        $cuadro2 = $img['imagen'];
    }
}

/* COMENTARIOS */

$stmtComentarios = $pdo->prepare("
SELECT
comentarios.*,
users.id AS autor,
users.Name,
users.Avatar

FROM comentarios

INNER JOIN users
ON comentarios.autor_id = users.id

WHERE comentarios.usuario_id = ?

ORDER BY comentarios.fecha DESC
");

$stmtComentarios->execute([$id_usuario]);


?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<title>Perfil</title>

<link rel="stylesheet" href="Css/Profile.css">

<link
href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css"
rel="stylesheet">

<style>

body{
    background:#0f1115;
}

/* CONTENEDOR */
.profile-container{
    width:90%;
    max-width:1000px;
    margin:20px auto;
}

/* HEADER */
.profile-header{

    background-image:url('<?php echo $fondo; ?>');
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    min-height:220px;

    border-radius:15px;

    padding:35px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 0 15px rgba(0,0,0,.4);

    position:relative;
}

/* INFO */
.profile-info-wrapper{
    display:flex;
    align-items:center;
    gap:20px;
}

/* AVATAR */
.profile-avatar img{
    width:120px;
    height:120px;
    border-radius:10px;
    object-fit:cover;
    border:3px solid #00ff66;
}

/* TEXTOS */
.profile-user-details h2{
    color:white;
    font-size:25px;
    font-weight:bold;
    margin:0;
}

.profile-user-details p{
    color:#ddd;
    font-size:22px;
    margin-top:10px;
}

/* BOTÓN */
.back-btn{
    background:#00ff66;
    color:black;
    text-decoration:none;
    padding:15px 22px;
    border-radius:12px;
    font-weight:bold;
    transition:.3s;

    position:relative;
    z-index:999;

    display:inline-block;
    cursor:pointer;
}

.back-btn:hover{
    transform:scale(1.05);
}

/* GALERÍA */
.showcase-section{
    margin-top:30px;
    background:#1b1d22;
    padding:20px;
    border-radius:15px;
}

.showcase-title{
    color:white;
    font-size:35px;
    margin-bottom:20px;
    font-weight:bold;
}

.showcase-grid{
    display:grid;
    grid-template-columns:repeat(2, 1fr);
    gap:20px;
    align-items:start;
}

.showcase-item img{
    width:100%;
    object-fit:cover;
    border-radius:10px;
    display:block;
}

</style>

</head>

<body>

<!-- SIDEBAR -->
<nav class="sidebar close">

    <header>

        <div class="image-text">

            <span class="image">
                <img src="logo3.png" alt="logo">
            </span>

            <div class="text header-text">
                <span class="name">Dividendo</span>
                <span class="profession">Beta</span>
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
                    <a href="Profile.php">
                        <i class="bx bx-user icon"></i>
                        <span class="text nav-text">Perfil</span>
                    </a>
                </li>

                <li class="nav-link">
                    <a href="Users.php">
                        <i class="bx bx-group icon"></i>
                        <span class="text nav-text">Usuarios</span>
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>



<section class="home">

<div class="profile-container">

    <!-- PERFIL -->
    <div class="profile-header">

        <div class="profile-info-wrapper">

            <!-- AVATAR -->
            <div class="profile-avatar">

                <img
                src="<?php echo $avatar; ?>">

            </div>

            <!-- DATOS -->
            <div class="profile-user-details">

                <h2>
                    <?php echo htmlspecialchars($usuario['Name']); ?>
                </h2>

                <p>
                    <?php echo htmlspecialchars($usuario['Email']); ?>
                </p>

            </div>

        </div>

        <!-- BOTÓN -->
        <a
        href="Index.php"
        class="back-btn">

            Regresar

        </a>

    </div>



    <!-- GALERÍA -->
    <div class="showcase-section">

        <div class="showcase-title">
            Mi galeria
        </div>

        <div class="showcase-grid">

            <!-- IMG 1 -->
            <div class="showcase-item">

                <img
                src="<?php echo $cuadro1; ?>">

            </div>

            <!-- IMG 2 -->
            <div class="showcase-item">

                <img
                src="<?php echo $cuadro2; ?>">

            </div>

        </div>

    </div>

    <!-- COMENTARIOS -->
<div class="showcase-section">

    <div class="showcase-title">
        Comentarios
    </div>

    <!-- FORMULARIO -->
    <form action="GuardarComentario.php" method="POST">

        <input
            type="hidden"
            name="usuario_id"
            value="<?php echo $id_usuario; ?>">

        <textarea
            name="comentario"
            placeholder="Escribe un comentario..."
            required
            style="
                width:100%;
                height:90px;
                resize:none;
                padding:15px;
                border-radius:10px;
                margin-bottom:15px;
                font-size:15px;
            "
        ></textarea>

        <button
            style="
                background:#00ff66;
                border:none;
                padding:12px 25px;
                border-radius:10px;
                font-weight:bold;
                cursor:pointer;
            ">
            Publicar
        </button>

    </form>

    <br>

   <?php while($c = $stmtComentarios->fetch()): ?>

    <div style="
        display:flex;
        gap:15px;
        background:#2b2d31;
        padding:15px;
        border-radius:10px;
        margin-bottom:15px;
    ">

        <img
            src="<?php echo $c['Avatar']; ?>"
            style="
                width:60px;
                height:60px;
                border-radius:50%;
                object-fit:cover;
            "
        >

        <div>

           <h3 style="margin:0;">
    <a
      href="User.php?id=<?php echo $c['autor']; ?>"
        style="color:#00ff66;text-decoration:none;"
    >
        <?php echo htmlspecialchars($c['Name']); ?>
    </a>
</h3>
            <small style="color:#aaa;">
                <?php echo $c['fecha']; ?>
            </small>

            <p style="color:white;margin-top:8px;">
                <?php echo nl2br(htmlspecialchars($c['comentario'])); ?>
            </p>

        </div>

    </div>

    <?php endwhile; ?>

</div>

</div>

</section>

<script src="ProfileScript.js"></script>

</body>
</html>