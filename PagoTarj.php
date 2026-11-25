<?php
session_start();

require_once __DIR__ . '/config/firebase.php';

if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

if (empty($_SESSION['csrf_pago'])) {
    $_SESSION['csrf_pago'] = bin2hex(random_bytes(32));
}

$id_parque = $_GET['id'] ?? '';

if (
    !is_string($id_parque) ||
    $id_parque === '' ||
    preg_match('~[.#$\[\]/\x00-\x1F\x7F]~', $id_parque)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}

try {
    $database = obtenerFirebaseDatabase();

    $parque = $database
        ->getReference('parques/' . $id_parque)
        ->getValue();

    if (!is_array($parque)) {
        http_response_code(404);
        exit('Parque no encontrado.');
    }

    $nombreParque = $parque['nombre'] ?? 'Parque';

} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo consultar el parque.');
}

// Comprobar si la donación fue exitosa
$donacionExitosa = null;

if (
    ($_GET['resultado'] ?? '') === 'exito' &&
    isset($_SESSION['donacion_exitosa']) &&
    $_SESSION['donacion_exitosa']['parqueId'] === $id_parque
) {
    $donacionExitosa = $_SESSION['donacion_exitosa'];
    unset($_SESSION['donacion_exitosa']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Donaciones - Dividendo</title>

    <link rel="stylesheet" href="Css/pago.css">

    <link rel="stylesheet"
          href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css">
</head>

<body>

<div class="payment-container">

<?php if ($donacionExitosa): ?>

    <!-- PANTALLA DE ÉXITO -->

    <div class="success-card">

        <div class="success-icon">
            <i class="bx bx-check"></i>
        </div>

        <span class="demo-badge">DONACIÓN SIMULADA</span>

        <h1>¡Gracias por tu donación!</h1>

        <p>
            Juntos podemos construir espacios
            más limpios y un futuro más verde.
        </p>

        <div class="success-amount">
            $<?= number_format(
                $donacionExitosa['montoCentavos'] / 100,
                2
            ) ?> MXN
        </div>

        <div class="receipt">

            <div class="receipt-row">
                <span>Parque</span>

                <strong>
                    <?= htmlspecialchars(
                        $nombreParque,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>

            <div class="receipt-row">
                <span>Método</span>
                <strong>Tarjeta de demostración</strong>
            </div>

            <div class="receipt-row">
                <span>Estado</span>
                <strong class="approved">
                    Simulación exitosa
                </strong>
            </div>

        </div>

        <a class="pay-btn"
           href="DetalleParque.php?id=<?= urlencode($id_parque) ?>">
            Volver al parque
        </a>

        <p class="security-note">
            <i class="bx bx-shield-check"></i>
            No se realizó ningún cargo real.
        </p>

    </div>

<?php else: ?>

    <div class="payment-card">

        <!-- LADO IZQUIERDO -->

        <div class="left-side">

            <div class="brand">
                <i class="bx bxs-leaf"></i>
                dividendo<span>.</span>
            </div>

            <span class="demo-badge">
                MODO DEMOSTRACIÓN
            </span>

            <h1>Pequeñas acciones.<br>Grandes cambios.</h1>

            <p class="intro">
                Contribuye a la creación de espacios
                más limpios y sostenibles.
            </p>

            <!-- TARJETA VIRTUAL -->

            <div class="virtual-card">

                <div class="card-top">

                    <span>dividendo.</span>

                    <i class="bx bx-wifi"></i>

                </div>

                <div class="card-chip"></div>

                <div class="card-number">
                    •••• •••• •••• 2026
                </div>

                <div class="card-bottom">

                    <div>
                        <small>TITULAR DEMO</small>
                        <strong>DIVIDENDO DEMO</strong>
                    </div>

                    <div>
                        <small>VENCIMIENTO</small>
                        <strong>12/30</strong>
                    </div>

                    <i class="bx bxs-leaf"></i>

                </div>

            </div>

            <!-- RESUMEN -->

            <div class="summary">

                <h3>Resumen de donación</h3>

                <div class="summary-row">

                    <span>Parque beneficiado</span>

                    <strong>
                        <?= htmlspecialchars(
                            $nombreParque,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </strong>

                </div>

                <div class="summary-row">

                    <span>Método</span>
                    <strong>Tarjeta demo</strong>

                </div>

                <div class="summary-total">

                    <span>Total a donar</span>

                    <h2 id="display-monto">
                        $150.00 MXN
                    </h2>

                </div>

            </div>

        </div>

        <!-- LADO DERECHO -->

        <div class="right-side">

            <h2>Realizar donación</h2>

            <p class="subtitle">
                Tu contribución hace la diferencia.
            </p>

            <form action="ProcesarDonacion.php"
                  method="POST"
                  id="payment-form">

                <input type="hidden"
                       name="parque_id"
                       value="<?= htmlspecialchars(
                           $id_parque,
                           ENT_QUOTES,
                           'UTF-8'
                       ) ?>">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= htmlspecialchars(
                           $_SESSION['csrf_pago'],
                           ENT_QUOTES,
                           'UTF-8'
                       ) ?>">

                <label>Selecciona un monto</label>

                <div class="amount-options">

                    <button type="button"
                            class="amount-btn"
                            data-amount="50">
                        $50
                    </button>

                    <button type="button"
                            class="amount-btn"
                            data-amount="100">
                        $100
                    </button>

                    <button type="button"
                            class="amount-btn active"
                            data-amount="150">
                        $150
                    </button>

                    <button type="button"
                            class="amount-btn"
                            data-amount="250">
                        $250
                    </button>

                </div>

                <div class="input-group">

                    <label for="input-monto">
                        Monto personalizado (MXN)
                    </label>

                    <div class="input-icon">

                        <i class="bx bx-dollar"></i>

                        <input type="number"
                               name="monto"
                               id="input-monto"
                               min="1"
                               max="10000"
                               step="0.01"
                               value="150"
                               required>

                    </div>

                </div>

                <div class="section-divider"></div>

                <h3>Método de pago simulado</h3>

                <div class="payment-method">

                    <i class="bx bx-credit-card"></i>

                    <div>
                        <strong>Tarjeta de demostración</strong>
                        <small>Datos ficticios precargados</small>
                    </div>

                    <i class="bx bxs-check-circle"></i>

                </div>

                <!-- DATOS EXCLUSIVAMENTE FICTICIOS -->

                <div class="input-group">

                    <label>Titular de demostración</label>

                    <input type="text"
                           value="DIVIDENDO DEMO"
                           readonly>

                </div>

                <div class="input-group">

                    <label>Número de tarjeta ficticia</label>

                    <div class="input-icon">

                        <i class="bx bx-credit-card"></i>

                        <input type="text"
                               value="0000 0000 0000 2026"
                               readonly>

                    </div>

                </div>

                <div class="input-row">

                    <div class="input-group">

                        <label>Vencimiento</label>

                        <input type="text"
                               value="12/30"
                               readonly>

                    </div>

                    <div class="input-group">

                        <label>CVV ficticio</label>

                        <input type="text"
                               value="•••"
                               readonly>

                    </div>

                </div>

                <div class="demo-notice">

                    <i class="bx bx-info-circle"></i>

                    <p>
                        Esta es una demostración académica.
                        No introduzcas datos bancarios reales.
                        No se realizará ningún cobro.
                    </p>

                </div>

                <button type="submit"
                        class="pay-btn"
                        id="pay-btn">

                    <i class="bx bx-lock-alt"></i>

                    Confirmar donación simulada

                </button>

            </form>

            <a href="DetalleParque.php?id=<?= urlencode($id_parque) ?>"
               class="back-link">

                <i class="bx bx-arrow-back"></i>
                Cancelar y volver

            </a>

        </div>

    </div>

<?php endif; ?>

</div>

<script>

const inputMonto = document.getElementById('input-monto');
const displayMonto = document.getElementById('display-monto');
const amountButtons = document.querySelectorAll('.amount-btn');
const form = document.getElementById('payment-form');
const payButton = document.getElementById('pay-btn');

if (inputMonto && displayMonto) {

    function actualizarMonto() {

        const monto = Number(inputMonto.value);

        displayMonto.textContent =
            new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(
                Number.isFinite(monto) && monto >= 0
                    ? monto
                    : 0
            ) + ' MXN';

        amountButtons.forEach(button => {

            button.classList.toggle(
                'active',
                Number(button.dataset.amount) === monto
            );

        });

    }

    amountButtons.forEach(button => {

        button.addEventListener('click', () => {

            inputMonto.value = button.dataset.amount;
            actualizarMonto();

        });

    });

    inputMonto.addEventListener('input', actualizarMonto);

    actualizarMonto();

}

if (form && payButton) {

    let enviando = false;

    form.addEventListener('submit', function(event) {

        event.preventDefault();

        if (enviando || !form.reportValidity()) {
            return;
        }

        enviando = true;

        payButton.disabled = true;

        payButton.innerHTML =
            '<span class="spinner"></span>' +
            'Procesando simulación...';

        setTimeout(() => {

            HTMLFormElement.prototype.submit.call(form);

        }, 900);

    });

}

</script>

</body>
</html>
