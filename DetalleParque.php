<?php
session_start();
if (empty($_SESSION['csrf_comentarios'])) {
    $_SESSION['csrf_comentarios'] = bin2hex(random_bytes(32));
}
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


$meta = max(0, (float)($parque['metaDonaciones'] ?? 0));

// Consultar las donaciones simuladas
$totalSimuladoCentavos = 0;
$cantidadDonaciones = 0;

try {
    $donaciones = $database
        ->getReference('donaciones')
        ->getValue() ?? [];

    if (is_array($donaciones)) {
        foreach ($donaciones as $donacion) {
            if (!is_array($donacion)) {
                continue;
            }

            // Solo donaciones de este parque
            if ((string)($donacion['parqueId'] ?? '') !== $id) {
                continue;
            }

            // Excluir pagos reales y otros estados
            if (($donacion['estado'] ?? '') !== 'simulada') {
                continue;
            }

            $centavos = (int)($donacion['montoCentavos'] ?? 0);

            if ($centavos <= 0) {
                continue;
            }

            $totalSimuladoCentavos += $centavos;
            $cantidadDonaciones++;
        }
    }
} catch (Throwable $e) {
    error_log('Error consultando donaciones: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo calcular el progreso de prueba.');
}

// Convertir centavos a pesos
$recaudadoSimulado = $totalSimuladoCentavos / 100;

// Calcular porcentaje de la meta
$porcentaje = 0;

if ($meta > 0) {
    $porcentaje = min(
        100,
        max(0, ($recaudadoSimulado / $meta) * 100)
    );
}

$comentariosParque = [];
$esAdmin = false;
$nombresUsuarios = [];

try {
    // Verificar el rol directamente en Firebase
    if (!empty($_SESSION['user_id'])) {
        $rol = $database
            ->getReference('users/' . $_SESSION['user_id'] . '/rol')
            ->getValue();

        $esAdmin = ($rol === 'admin');
    }

    // Consultar comentarios
    $todosComentarios = $database
        ->getReference('comentarios')
        ->getValue() ?? [];

    if (is_array($todosComentarios)) {
        foreach ($todosComentarios as $comentarioId => $comentario) {
            if (!is_array($comentario)) {
                continue;
            }

            // Mostrar solo los comentarios de este parque
            if ((string)($comentario['parqueId'] ?? '') !== $id) {
                continue;
            }

            $usuarioId = (string)($comentario['usuarioId'] ?? '');

            // Obtener el nombre del autor
            if (!isset($nombresUsuarios[$usuarioId])) {
                $nombreUsuario = 'Usuario';

                if (
                    $usuarioId !== '' &&
                    !preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $usuarioId)
                ) {
                    $usuario = $database
                        ->getReference('users/' . $usuarioId)
                        ->getValue();

                    if (is_array($usuario)) {
                        $nombreUsuario = (string)(
                            $usuario['nombre'] ?? 'Usuario'
                        );
                    }
                }

                $nombresUsuarios[$usuarioId] = $nombreUsuario;
            }

            $comentariosParque[] = [
                'id' => (string)$comentarioId,
                'nombre' => $nombresUsuarios[$usuarioId],
                'texto' => (string)($comentario['comentario'] ?? ''),
                'fecha' => (string)($comentario['fecha'] ?? '')
            ];
        }
    }

    usort(
        $comentariosParque,
        fn($a, $b) => strcmp($b['fecha'], $a['fecha'])
    );

} catch (Throwable $e) {
    error_log('Error cargando comentarios: ' . $e->getMessage());
    $comentariosParque = [];
    $errorComentarios = 'No se pudieron cargar los comentarios.';
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
.comentarios-parque {
    margin-top: 40px;
    max-width: 750px;
}

.comentario-tarjeta {
    background: var(--sidebar-color);
    padding: 20px;
    border-radius: 12px;
    margin: 15px 0;
    border: 1px solid rgba(128,128,128,0.2);
    overflow-wrap: anywhere;
}

.comentario-tarjeta h3 {
    margin: 0 0 8px;
}

.comentario-tarjeta small {
    color: var(--text-color);
    opacity: 0.7;
}

.comentario-tarjeta p {
    white-space: normal;
    line-height: 1.6;
}

.eliminar-comentario {
    background: #c62828;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 14px;
    cursor: pointer;
    margin-top: 10px;
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
                        <a href="Parques.php">
                            <i class='bx bxs-tree-alt icon'></i>
                            <span class="text nav-text">Parques</span>
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

            
<p>
    <strong>Meta del parque:</strong>
    $<?= number_format($meta, 2) ?> MXN
</p>

<p>
    <strong>Donaciones:</strong>
    $<?= number_format($recaudadoSimulado, 2) ?> MXN
</p>

<p>
    <strong>Progreso de prueba:</strong>
    <?= round($porcentaje, 1) ?>%
</p>



            <div class="barra-contenedor">
                <div class="barra-progreso" style="width: <?php echo round($porcentaje); ?>%;"></div>
            </div>

<a href="PagoTarj.php?id=<?= urlencode($id) ?>"
   class="donacion-btn">
    <i class='bx bx-donate-heart'></i>
    Donar ahora
</a>



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
            
<section class="comentarios-parque">
    <h2>Comentarios del parque</h2>

    <?php if (!empty($_GET['comentario']) &&
              $_GET['comentario'] === 'ok'): ?>
        <p>¡Tu comentario se publicó correctamente!</p>
    <?php endif; ?>

    <?php if (!empty($errorComentarios)): ?>
        <p><?= htmlspecialchars($errorComentarios, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (empty($comentariosParque)): ?>
        <p>Todavía no hay comentarios. ¡Sé el primero!</p>
    <?php else: ?>

        <?php foreach ($comentariosParque as $comentario): ?>
            <article class="comentario-tarjeta">

                <h3>
                    <?= htmlspecialchars(
                        $comentario['nombre'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </h3>

                <small>
                    <?php
                    try {
                        $fecha = new DateTimeImmutable(
                            $comentario['fecha']
                        );

                        echo htmlspecialchars(
                            $fecha->setTimezone(
                                new DateTimeZone('America/Monterrey')
                            )->format('d/m/Y H:i'),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    } catch (Throwable $e) {
                        echo 'Sin fecha';
                    }
                    ?>
                </small>

                <p>
                    <?= nl2br(htmlspecialchars(
                        $comentario['texto'],
                        ENT_QUOTES,
                        'UTF-8'
                    )) ?>
                </p>

                <?php if ($esAdmin): ?>
                    <form action="EliminarComentario.php" method="POST"
                          onsubmit="return confirm('¿Eliminar este comentario?');">

                        <input type="hidden" name="comentario_id"
                               value="<?= htmlspecialchars($comentario['id'], ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden" name="parque_id"
                               value="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden" name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_comentarios'], ENT_QUOTES, 'UTF-8') ?>">

                        <button type="submit" class="eliminar-comentario">
                            Eliminar comentario
                        </button>
                    </form>
                    <form action="GuardarComentario.php" method="POST">
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>

    <!-- Mantén aquí el formulario para publicar
         comentarios que agregamos anteriormente. -->
</section>

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