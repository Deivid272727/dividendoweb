<?php
session_start();
require_once 'bd/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

$buscar = $_GET['buscar'] ?? '';

$stmt = $pdo->prepare("
SELECT id, Name, Email, Avatar
FROM users
WHERE Name LIKE ?
ORDER BY Name ASC
");

$stmt->execute(["%$buscar%"]);
?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<title>Buscar Usuarios</title>

<link rel="stylesheet" href="Css/Profile.css">

<link
href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css"
rel="stylesheet">

<style>

body{
    background:#0f1115;
}

/* CONTENEDOR */
.users-container{
    width:90%;
    max-width:1100px;
    margin:30px auto;
}

/* BUSCADOR */
.search-top{
    width:100%;
    display:flex;
    justify-content:center;
    margin-bottom:30px;
}

.search-top form{
    width:650px;
    display:flex;
    align-items:center;
    gap:10px;
    background:#162338;
    padding:15px 20px;
    border-radius:15px;
    box-shadow:0 0 10px rgba(0,0,0,.3);
}

.search-top i{
    color:white;
    font-size:22px;
}

.search-top input{
    flex:1;
    background:none;
    border:none;
    outline:none;
    color:white;
    font-size:17px;
}

.search-top button{
    background:#00ff66;
    border:none;
    padding:12px 25px;
    border-radius:10px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

.search-top button:hover{
    transform:scale(1.05);
}

/* REGRESAR */
.back-btn{
    display:inline-block;
    margin-bottom:25px;
    background:#162338;
    color:white;
    text-decoration:none;
    padding:14px 20px;
    border-radius:12px;
    transition:.3s;
}

.back-btn:hover{
    background:#223548;
}

/* TARJETAS */
.user-card{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#162338;
    padding:25px;
    border-radius:18px;
    margin-bottom:25px;
    transition:.3s;
    box-shadow:0 0 15px rgba(0,0,0,.3);
}

.user-card:hover{
    transform:translateY(-5px);
    background:#1d314b;
}

.user-left{
    display:flex;
    align-items:center;
    gap:20px;
}

.user-avatar{
    width:95px;
    height:95px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid #00ff66;
}

.user-info h2{
    color:white;
    font-size:38px;
    margin:0;
}

.user-info p{
    color:#cfcfcf;
    margin-top:8px;
    font-size:18px;
}

/* BOTÓN PERFIL */
.profile-btn{
    background:#00ff66;
    color:black;
    text-decoration:none;
    padding:16px 25px;
    border-radius:12px;
    font-weight:bold;
    font-size:18px;
    transition:.3s;
}

.profile-btn:hover{
    transform:scale(1.05);
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
                    <a href="search.php">
                        <i class="bx bx-group icon"></i>
                        <span class="text nav-text">Usuarios</span>
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>



<section class="home">

<div class="users-container">

    <!-- BUSCADOR -->
    <div class="search-top">

        <form action="search.php" method="GET">

            <i class='bx bx-search'></i>

            <input
            type="text"
            name="buscar"
            placeholder="Buscar usuarios..."
            value="<?php echo htmlspecialchars($buscar); ?>">

            <button type="submit">
                Buscar
            </button>

        </form>

    </div>

    <!-- BOTÓN -->
    <a href="Index.php" class="back-btn">
        ← Regresar
    </a>


    <!-- LISTA -->
    <?php while($row = $stmt->fetch()) { ?>

        <div class="user-card">

            <div class="user-left">

                <img
                src="<?php echo !empty($row['Avatar']) ? $row['Avatar'] : 'uploads/default_avatar.png'; ?>"
                class="user-avatar">

                <div class="user-info">

                    <h2>
                        <?php echo htmlspecialchars($row['Name']); ?>
                    </h2>

                    <p>
                        <?php echo htmlspecialchars($row['Email']); ?>
                    </p>

                </div>

            </div>

            <a
            href="user.php?id=<?php echo $row['id']; ?>"
            class="profile-btn">

                Ver Perfil

            </a>

        </div>

    <?php } ?>

</div>

</section>

<script src="ProfileScript.js"></script>

</body>
</html>