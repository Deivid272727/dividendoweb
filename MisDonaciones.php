
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

$idUsuario = (string)$_SESSION['user_id'];
$donaciones = [];
$totalCentavos = 0;
$error = '';

try {
    $database = obtenerFirebaseDatabase();

    // Consultar donaciones del usuario autenticado
    $datos = $database
        ->getReference('donaciones')
        ->orderByChild('usuarioId')
        ->equalTo($idUsuario)
        ->getValue() ?? [];

    if (!is_array($datos)) {
        $datos = [];
    }

    $nombresParques = [];

    foreach ($datos as $idDonacion => $donacion) {
        if (!is_array($donacion)) {
            continue;
        }

        $parqueId = (string)($donacion['parqueId'] ?? '');
        $centavos = (int)($donacion['montoCentavos'] ?? 0);

        if ($centavos < 0) {
            continue;
        }

        // Solo sumar donaciones simuladas
        if (($donacion['estado'] ?? '') === 'simulada') {
            $totalCentavos += $centavos;
        }

        if (!isset($nombresParques[$parqueId])) {
            $nombre = 'Parque no disponible';

            if (
                $parqueId !== '' &&
                !preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $parqueId)
            ) {
                $parque = $database
                    ->getReference('parques/' . $parqueId)
                    ->getValue();

                if (is_array($parque)) {
                    $nombre = (string)(
                        $parque['nombre'] ?? 'Parque sin nombre'
                    );
                }
            }

            $nombresParques[$parqueId] = $nombre;
        }

        $donaciones[] = [
            'parque' => $nombresParques[$parqueId],
            'centavos' => $centavos,
            'estado' => (string)($donacion['estado'] ?? ''),
            'fecha' => (string)($donacion['fecha'] ?? '')
        ];
    }

    // Ordenar de la más reciente a la más antigua
    usort($donaciones, function ($a, $b) {
        return strcmp($b['fecha'], $a['fecha']);
    });

} catch (Throwable $e) {
    error_log('Error consultando donaciones: ' . $e->getMessage());
    $error = 'No se pudo cargar el historial de donaciones.';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis donaciones | Dividendo</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 16px;
            background: #101820;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            max-width: 950px;
            margin: auto;
        }

        h1 {
            color: #00e676;
        }

        .resumen {
            background: #1d2935;
            padding: 28px;
            border-radius: 16px;
            margin: 25px 0;
        }

        .total {
            font-size: 36px;
            font-weight: bold;
            color: #00e676;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1d2935;
            border-radius: 12px;
            overflow: hidden;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #344250;
        }

        th {
            background: #253746;
        }

        .simulada {
            color: #ffd166;
        }

        .volver {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 22px;
            border-radius: 10px;
            background: #00c864;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="contenedor">

    <h1>Mis donaciones 🌳</h1>
    <p>Consulta tus aportaciones registradas en Dividendo.</p>

    <?php if ($error !== ''): ?>
        <p><?= escapar($error) ?></p>
    <?php else: ?>

        <div class="resumen">
            <h2>Total de donaciones simuladas</h2>

            <div class="total">
                $<?= number_format($totalCentavos / 100, 2) ?> MXN
            </div>

            <p>
                <?= count($donaciones) ?>
                movimientos registrados
            </p>
        </div>

        <?php if (empty($donaciones)): ?>

            <p>Aún no tienes donaciones registradas.</p>

        <?php else: ?>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Parque</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($donaciones as $donacion): ?>
                        <tr>
                            <td><?= escapar($donacion['parque']) ?></td>

                            <td>
                                $<?= number_format(
                                    $donacion['centavos'] / 100, 2
                                ) ?>
                            </td>

                            <td>
                                <?php
                                try {
                                    $fecha = new DateTimeImmutable(
                                        $donacion['fecha']
                                    );

                                    echo escapar(
                                        $fecha
                                            ->setTimezone(
                                                new DateTimeZone('America/Monterrey')
                                            )
                                            ->format('d/m/Y H:i')
                                    );
                                } catch (Throwable $e) {
                                    echo 'Sin fecha';
                                }
                                ?>
                            </td>

                            <td class="simulada">
                                <?= escapar(
                                    ucfirst($donacion['estado'])
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <a href="Profile.php" class="volver">
        Volver a mi perfil
    </a>

</div>

</body>
</html>
