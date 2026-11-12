
<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

try {
    $database = obtenerFirebaseDatabase();

    // Verificar el rol directamente en Firebase
    $rol = $database
        ->getReference('users/' . $_SESSION['user_id'] . '/rol')
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    // Obtener parques
    $parques = $database
        ->getReference('parques')
        ->getValue() ?? [];

    // Obtener usuarios
    $usuarios = $database
        ->getReference('users')
        ->getValue() ?? [];

} catch (Throwable $e) {
    error_log('Error en estadísticas: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudieron cargar las estadísticas.');
}

// Calcular estadísticas
$totalParques = 0;
$totalUsuarios = is_array($usuarios) ? count($usuarios) : 0;
$parquesPorMunicipio = [];

if (is_array($parques)) {
    foreach ($parques as $parque) {
        if (!is_array($parque)) {
            continue;
        }

        $totalParques++;

        $municipio = trim((string) ($parque['municipio'] ?? ''));

        if ($municipio === '') {
            $municipio = 'Sin municipio';
        }

        $parquesPorMunicipio[$municipio] =
            ($parquesPorMunicipio[$municipio] ?? 0) + 1;
    }
}

$totalMunicipios = count(
    array_filter(
        array_keys($parquesPorMunicipio),
        fn($municipio) => $municipio !== 'Sin municipio'
    )
);

arsort($parquesPorMunicipio);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Estadísticas | Dividendo</title>

    <link rel="stylesheet" href="Css/estadisticas.css">
</head>

<body>

<main class="dashboard">

   
<main class="dashboard">

    <header class="encabezado">
        <div>
            <h1>Estadísticas de Dividendo</h1>
            <p>
                Información general de parques y usuarios
                registrados en Firebase.
            </p>
        </div>

        <a href="Admin.php" class="volver">
            ← Regresar al Panel
        </a>
    </header>


    <div class="tarjetas">

        <div class="tarjeta">
            <h3>Parques registrados</h3>
            <div class="numero">
                <?= $totalParques ?>
            </div>
        </div>

        <div class="tarjeta">
            <h3>Municipios con parques</h3>
            <div class="numero">
                <?= $totalMunicipios ?>
            </div>
        </div>

        <div class="tarjeta">
            <h3>Usuarios registrados en la base</h3>
            <div class="numero">
                <?= $totalUsuarios ?>
            </div>
        </div>

    </div>

    <section class="seccion">

        <h2>Parques por municipio</h2>

        <?php if (empty($parquesPorMunicipio)): ?>

            <p>No hay parques registrados.</p>

        <?php else: ?>

            <?php foreach ($parquesPorMunicipio as $municipio => $cantidad): ?>

    <div class="municipio">

        <div class="municipio-info">
            <strong>
                <?= htmlspecialchars(
                    $municipio,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

            <span><?= $cantidad ?> parques</span>
        </div>

        <div class="barra">
            <div
                class="progreso"
                style="width: <?= $totalParques > 0
                    ? round($cantidad / $totalParques * 100, 2)
                    : 0 ?>%;">
            </div>
        </div>

    </div>

<?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>
<script src="admin.js"></script>
</body>
</html>
