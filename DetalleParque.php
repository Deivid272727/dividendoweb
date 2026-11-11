<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

$id = trim((string) ($_GET['id'] ?? ''));

if ($id === '') {
    http_response_code(400);
    exit('No se proporcionó un parque.');
}

try {
    $database = obtenerFirebaseDatabase();

    // Firebase utiliza identificadores de texto.
    $parque = $database
        ->getReference('parques')
        ->getChild($id)
        ->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    $parque['id'] = $id;

} catch (Throwable $e) {
    error_log('Error consultando Firebase: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudo consultar el parque.');
}

// Datos provenientes de Firebase
$nombre = $parque['nombre'] ?? 'Parque sin nombre';
$descripcion = $parque['descripcion'] ?? '';
$ubicacion = $parque['ubicacion'] ?? '';

$meta = (float) ($parque['metaDonaciones'] ?? 0);
$recaudado = (float) ($parque['montoRecaudado'] ?? 0);

// Calcular porcentaje de donaciones
$porcentaje = 0;

if ($meta > 0) {
    $porcentaje = min(
        100,
        max(0, ($recaudado / $meta) * 100)
    );
}
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <link rel="stylesheet" href="Css/Style.css">
        <link href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
        <title><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?> - Detalle</title>

        <style>
            /* Estilos del menú de perfil flotante */
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

            /* --- Estilos de la Información del Parque (CORREGIDO PARA DARK MODE) --- */
            .detalle-parque-contenedor {
                padding: 40px;
                font-family: 'Poppins', sans-serif;
                max-width: 800px;
            }

            /* Forzamos a que todos los textos usen la variable adaptable de tu Style.css */
            .detalle-parque-contenedor h1,
            .detalle-parque-contenedor h3,
            .detalle-parque-contenedor p,
            .detalle-parque-contenedor strong {
                color: var(--text-color) !important;
                transition: var(--tran-03);
            }

            .barra-contenedor {
                width: 100%;
                max-width: 500px;
                height: 25px;
                background-color: #ddd;
                border-radius: 20px;
                overflow: hidden;
                margin-top: 10px;
                margin-bottom: 20px;
            }

            .barra-progreso {
                height: 100%;
                background-color: #00E676;
                transition: 0.5s;
            }

            .paypal-container {
                margin: 20px 0;
            }

            .paypal-btn {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                background-color: #FFC439;
                color: #003087 !important; /* Mantenemos el azul original de PayPal por estética de marca */
                text-decoration: none;
                padding: 12px 24px;
                border-radius: 30px;
                font-weight: bold;
                font-size: 16px;
                transition: background 0.2s;
            }

            .paypal-btn:hover {
                background-color: #E5A93B;
            }

            .paypal-btn img {
                height: 24px;
            }

            .paypal-container{

    display:flex;
    align-items:center;
    gap:15px;
    margin:20px 0;
    flex-wrap:wrap;

}

.donacion-btn{

    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:12px 24px;
    border-radius:30px;
    background:#00E676;
    color:white !important;
    text-decoration:none;
    font-weight:bold;
    transition:.3s;

}

.donacion-btn:hover{

    background:#00c864;
    transform:translateY(-2px);

}

.donacion-btn i{

    font-size:22px;

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
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                                <li class="nav-link">
                                    <a href="Admin.php">
                                        <i class='bx bx-cog icon'></i>
                                        <span class="text nav-text">Panel Admin</span>
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

                    <li class="nav-link">
                        <a href="#">
                            <i class="bx bx-heart icon"></i>
                            <span class="text nav-text">Santa chad</span>
                        </a>
                    </li>

                </ul>
            </div>

            <div class="bottom-content">
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
                    ¡Hola, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Invitado'; ?>!
                </h3>

                <p class="texto-email-cuadro">
                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'Sin iniciar sesión'; ?>
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

        <div class="detalle-parque-contenedor">
            <h1><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></h1>
            
            <p class="direccion">
   <i class='bx bx-map'></i>
    <?php echo htmlspecialchars($ubicacion, ENT_QUOTES, 'UTF-8'); ?>            </p>

            <p class="info-adicional">
                <?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <<p><strong>Meta:</strong> $<?php echo number_format($meta, 2); ?></p>
            <p><strong>Progreso:</strong> <?php echo round($porcentaje); ?>%</p>

            <div class="barra-contenedor">
                <div class="barra-progreso" style="width: <?php echo round($porcentaje); ?>%;"></div>
            </div>

           <div class="paypal-container">
    <p>Las donaciones estarán disponibles próximamente.</p>
</div>

            <?php if(!empty($parque['ubicacion'])): ?>
                <h3>Ubicación</h3>
                <iframe
                    width="100%"
                    height="400"
                    style="border:0;border-radius:12px;margin-bottom: 10px;"
                    loading="lazy"
                    allowfullscreen
                    src="https://www.google.com/maps?q=<?php echo urlencode($parque['ubicacion']); ?>&output=embed">
                </iframe>

                <p>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($parque['ubicacion']); ?>" target="_blank" style="color: #00E676; text-decoration: none; font-weight: 500;">
                        <i class='bx bx-navigation' ></i> Ver en Google Maps
                    </a>
                </p>
            <?php endif; ?>
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