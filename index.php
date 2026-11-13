<?php
session_start();
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
        /*================ HERO =================*/

.hero{
    width:100%;
    height:100vh;
    background:url("https://images.unsplash.com/photo-1506744038136-46273834b3fb?q=80&w=1920&auto=format&fit=crop") center center/cover no-repeat;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
}

.hero::before{
    content:"";
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.45);
}

.hero-box{
    position:relative;
    z-index:2;
    width:700px;
    padding:45px;
    background:rgba(0,0,0,.55);
    border-radius:30px;
    backdrop-filter:blur(10px);
    text-align:center;
    border:1px solid rgba(0,230,118,.25);
}

.hero-box h1{
    color:#fff;
    font-size:65px;
    margin-bottom:15px;
}

.hero-box p{
    color:#f5f5f5;
    font-size:22px;
}

/*================ ODS =================*/

.ods{
    background: #f4f4f4;;
    color:black;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:90px;
    padding:120px 80px;
    flex-wrap:wrap;
}

.ods img{
    width:360px;
    border-radius:20px;
}

.ods-info{
    max-width:650px;
}

.ods-info h2{
    font-size:55px;
    margin-bottom:25px;
}

.ods-info p{
    font-size:22px;
    color:#000;
    line-height:1.8;
}

.ods-info a{
    display:inline-block;
    margin-top:35px;
    padding:15px 35px;
    border-radius:15px;
    background:#00E676;
    color:white;
    text-decoration:none;
    font-weight:bold;
    transition:.3s;
}

.ods-info a:hover{
    transform:translateY(-5px);
}

/*================ CARDS =================*/

.cards{
    background:#f3f3f3;
    padding:90px;
}

.cards-grid{
    display:flex;
    justify-content:center;
    gap:40px;
    flex-wrap:wrap;
}

.card{
    width:340px;
    background:white;
    border-radius:25px;
    overflow:hidden;
    box-shadow:0 15px 35px rgba(0,0,0,.12);
    transition:.35s;
}

.card:hover{
    transform:translateY(-10px);
}

.card img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.card h3{
    padding:25px;
    text-align:center;
    color:#222;
}

.btn-ver{
    text-align:center;
    margin-top:45px;
}

.btn-ver a{
    background:#00E676;
    color:white;
    padding:18px 45px;
    border-radius:15px;
    text-decoration:none;
    font-weight:bold;
}

/*========== MODO OSCURO ==========*/

body.dark .cards{
    background:#0f0f0f;
}

body.dark .card{
    background:#1d1d1d;
    color:white;
    box-shadow:0 10px 30px rgba(0,0,0,.5);
}

body.dark .card h3{
    color:white;
}

body.dark .ods{
    background:#0b0b0b;
}

body.dark .ods-info h2{
    color:#fff;
}

body.dark .ods-info p{
    color:#d0d0d0;
}

body.dark .hero-box{
    background:rgba(0,0,0,.7);
}

body.dark .btn-ver a,
body.dark .ods-info a{
    background:#00E676;
    color:white;
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
                <span class="name">Opciones</span>
                <span class="profession">¿Que queremos hacer?</span>
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
                    <a href="index.php">
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
                        <?php if(isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin'): ?>

<li class="nav-link">
    <a href="Admin.php">
        <i class='bx bx-cog icon'></i>
        <span class="text nav-text">Administración</span>
    </a>
</li>

<?php endif; ?>

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

    <div class="text">Casitaaa</div>

    <section class="hero">

    <div class="hero-box">

        <h1>Dividendo</h1>

        <p>
            Innovando en el manejo de residuos para reducir la contaminación
        </p>

    </div>

</section>

<section class="ods">

    <img src="https://i.pinimg.com/1200x/fa/56/1e/fa561e8451f2a6452bbc68c8248bf49e.jpg">

    <div class="ods-info">

        <h2>ODS 13. Acción por el Clima</h2>

        <p>

            El cambio climático es uno de los mayores problemas actuales.

            En Dividendo buscamos soluciones para reducir la contaminación mediante el manejo adecuado de residuos y el uso de tecnologías sostenibles.

            <br><br>

            Promovemos una cultura ecológica donde todos participen en el cuidado del planeta.

        </p>

        <a href="https://www.pactomundial.org/ods/13-accion-por-el-clima/">

            Más sobre la ODS

        </a>

    </div>

</section>

<section class="cards">

    <div class="cards-grid">

        <div class="card">

     <img src="https://i.pinimg.com/736x/1a/f9/b5/1af9b54462604ff45909e12cb1685544.jpg">

            <h3>No hay planeta B</h3>

        </div>

        <div class="card">

          <img src="https://i.pinimg.com/736x/7c/5a/c2/7c5ac280149e7c65251a08f16b8a18cf.jpg">

            <h3>Cuidemos el planeta</h3>

        </div>

        <div class="card">

       <img src="https://i.pinimg.com/1200x/34/66/ac/3466aca4eb7b589fdcc163b0ed920b62.jpg">

            <h3>Actúa ahora</h3>

        </div>

    </div>

    <div class="btn-ver">

        <a href="Municipios.php">

            Ver Todos

        </a>

    </div>

</section>

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