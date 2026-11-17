
<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

function escapar($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function dinero(int $centavos): string {
    return '$' . number_format($centavos / 100, 2) . ' MXN';
}

try {
    $database = obtenerFirebaseDatabase();

    // Comprobar que el usuario sea administrador
    $rol = $database
        ->getReference('users/' . $_SESSION['user_id'] . '/rol')
        ->getValue();

    if ($rol !== 'admin') {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    $parques = $database
        ->getReference('parques')
        ->getValue() ?? [];

    $usuarios = $database
        ->getReference('users')
        ->getValue() ?? [];

    $donaciones = $database
        ->getReference('donaciones')
        ->getValue() ?? [];

} catch (Throwable $e) {
    error_log('Error en estadísticas: ' . $e->getMessage());

    http_response_code(500);
    exit('No se pudieron cargar las estadísticas.');
}

// =============================
// ESTADÍSTICAS GENERALES
// =============================

$totalParques = 0;
$totalUsuarios = is_array($usuarios) ? count($usuarios) : 0;
$parquesPorMunicipio = [];
$nombresParques = [];

if (is_array($parques)) {
    foreach ($parques as $id => $parque) {
        if (!is_array($parque)) {
            continue;
        }

        $totalParques++;

        $nombresParques[$id] = (string)(
            $parque['nombre'] ?? 'Parque sin nombre'
        );

        $municipio = trim((string)($parque['municipio'] ?? ''));

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

// =============================
// ESTADÍSTICAS DE DONACIONES
// =============================

$totalDonaciones = 0;
$totalSimuladoCentavos = 0;

$donadores = [];
$parquesBeneficiados = [];
$donacionesPorParque = [];
$movimientos = [];

if (is_array($donaciones)) {
    foreach ($donaciones as $id => $donacion) {
        if (!is_array($donacion)) {
            continue;
        }

        // No mezclar pagos reales con pruebas
        if (($donacion['estado'] ?? '') !== 'simulada') {
            continue;
        }

        $monto = (int)($donacion['montoCentavos'] ?? 0);

        if ($monto <= 0) {
            continue;
        }

        $usuarioId = (string)($donacion['usuarioId'] ?? '');
        $parqueId = (string)($donacion['parqueId'] ?? '');
        $fecha = (string)($donacion['fecha'] ?? '');

        $totalDonaciones++;
        $totalSimuladoCentavos += $monto;

        if ($usuarioId !== '') {
            $donadores[$usuarioId] = true;
        }

        if ($parqueId !== '') {
            $parquesBeneficiados[$parqueId] = true;

            if (!isset($donacionesPorParque[$parqueId])) {
                $donacionesPorParque[$parqueId] = [
                    'nombre' => $nombresParques[$parqueId]
                        ?? 'Parque no disponible',
                    'centavos' => 0,
                    'cantidad' => 0
                ];
            }

            $donacionesPorParque[$parqueId]['centavos'] += $monto;
            $donacionesPorParque[$parqueId]['cantidad']++;
        }

        $movimientos[] = [
            'parque' => $nombresParques[$parqueId]
                ?? 'Parque no disponible',
            'monto' => $monto,
            'fecha' => $fecha,
            'estado' => 'Simulada'
        ];
    }
}

$totalDonadores = count($donadores);
$totalBeneficiados = count($parquesBeneficiados);

// Ordenar parques por mayor monto simulado
uasort(
    $donacionesPorParque,
    fn($a, $b) => $b['centavos'] <=> $a['centavos']
);

// Ordenar movimientos por fecha
usort(
    $movimientos,
    fn($a, $b) => strcmp($b['fecha'], $a['fecha'])
);

// Mostrar solamente los 10 más recientes
$movimientosRecientes = array_slice($movimientos, 0, 10);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Estadísticas | Dividendo</title>

    <link rel="stylesheet" href="Css/estadisticas.css">

    <style>
        .seccion {
            margin-top: 28px;
        }

        .tabla-contenedor {
            overflow-x: auto;
            margin-top: 20px;
        }

        .tabla-donaciones {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-donaciones th,
        .tabla-donaciones td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #46515a;
        }

        .tabla-donaciones th {
            font-weight: bold;
        }

        .estado-demo {
            color: #e0a82e;
            font-weight: bold;
        }

        .nota-demo {
            margin-top: 12px;
            opacity: 0.8;
            font-size: 14px;
        }
    </style>
</head>

<body>

<main class="dashboard">

    <header class="encabezado">
        <div>
            <h1>Estadísticas de Dividendo</h1>
            <p>
                Información de parques, usuarios y
                donaciones simuladas en Firebase.
            </p>
        </div>

        <a href="Admin.php" class="volver">
            ← Regresar al Panel
        </a>
    </header>

    <!-- ESTADÍSTICAS GENERALES -->

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
            <h3>Usuarios registrados</h3>
            <div class="numero">
                <?= $totalUsuarios ?>
            </div>
        </div>

    </div>

    <!-- ESTADÍSTICAS DE DONACIONES -->

    <section class="seccion">
        <h2>Donaciones de prueba</h2>

        <p class="nota-demo">
            Estas cantidades son simuladas.
            No representan dinero recibido.
        </p>

        <div class="tarjetas">

            <div class="tarjeta">
                <h3>Total simulado</h3>
                <div class="numero">
                    <?= dinero($totalSimuladoCentavos) ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Donaciones registradas</h3>
                <div class="numero">
                    <?= $totalDonaciones ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Donadores únicos</h3>
                <div class="numero">
                    <?= $totalDonadores ?>
                </div>
            </div>

            <div class="tarjeta">
                <h3>Parques beneficiados</h3>
                <div class="numero">
                    <?= $totalBeneficiados ?>
                </div>
            </div>

        </div>
    </section>

    <!-- PARQUES POR MUNICIPIO -->

    <section class="seccion">
        <h2>Parques por municipio</h2>

        <?php if (empty($parquesPorMunicipio)): ?>

            <p>No hay parques registrados.</p>

        <?php else: ?>

            <?php foreach ($parquesPorMunicipio as $municipio => $cantidad): ?>

                <div class="municipio">

                    <div class="municipio-info">
                        <strong>
                            <?= escapar($municipio) ?>
                        </strong>

                        <span>
                            <?= $cantidad ?> parques
                        </span>
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

    <!-- DONACIONES POR PARQUE -->

    <section class="seccion">
        <h2>Donaciones simuladas por parque</h2>

        <?php if (empty($donacionesPorParque)): ?>

            <p>Todavía no hay donaciones simuladas.</p>

        <?php else: ?>

            <?php foreach ($donacionesPorParque as $parque): ?>

                <?php
                $porcentaje = $totalSimuladoCentavos > 0
                    ? round(
                        $parque['centavos'] /
                        $totalSimuladoCentavos * 100,
                        2
                    )
                    : 0;
                ?>

                <div class="municipio">

                    <div class="municipio-info">
                        <strong>
                            <?= escapar($parque['nombre']) ?>
                        </strong>

                        <span>
                            <?= dinero($parque['centavos']) ?>
                            (<?= $parque['cantidad'] ?> donaciones)
                        </span>
                    </div>

                    <div class="barra">
                        <div
                            class="progreso"
                            style="width: <?= $porcentaje ?>%;">
                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <!-- MOVIMIENTOS RECIENTES -->

    <section class="seccion">
        <h2>Últimas donaciones simuladas</h2>

        <?php if (empty($movimientosRecientes)): ?>

            <p>No hay movimientos registrados.</p>

        <?php else: ?>

            <div class="tabla-contenedor">
                <table class="tabla-donaciones">

                    <thead>
                        <tr>
                            <th>Parque</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($movimientosRecientes as $movimiento): ?>

                        <tr>
                            <td>
                                <?= escapar($movimiento['parque']) ?>
                            </td>

                            <td>
                                <?= dinero($movimiento['monto']) ?>
                            </td>

                            <td>
                                <?php
                                try {
                                    $fecha = new DateTimeImmutable(
                                        $movimiento['fecha']
                                    );

                                    echo escapar(
                                        $fecha->setTimezone(
                                            new DateTimeZone(
                                                'America/Monterrey'
                                            )
                                        )->format('d/m/Y H:i')
                                    );
                                } catch (Throwable $e) {
                                    echo 'Sin fecha';
                                }
                                ?>
                            </td>

                            <td class="estado-demo">
                                <?= escapar($movimiento['estado']) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>

</main>

</body>
</html>
