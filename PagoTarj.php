
<?php
session_start();

// Verificar que el usuario haya iniciado sesión
if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

// Generar token CSRF
if (empty($_SESSION['csrf_pago'])) {
    $_SESSION['csrf_pago'] = bin2hex(random_bytes(32));
}

// Obtener ID del parque
$id_parque = $_GET['id'] ?? '';

if (
    !is_string($id_parque) ||
    $id_parque === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $id_parque)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Finalizar Pago - Dividendo</title>

    <link rel="stylesheet" href="Css/pago.css">
</head>

<body>

<div class="payment-container">
    <div class="payment-card">

        <!-- LADO IZQUIERDO -->
        <div class="left-side">

            <img
                src="https://media1.tenor.com/m/PDVHdZ0XYJoAAAAC/cat-shield.gif"
                alt="Logo"
                class="payment-logo"
            >

            <h2>Resumen de donación</h2>

            <p>Donación de prueba para el parque seleccionado</p>

            <div class="total-section">
                <span>Monto a donar:</span>
                <h1 id="display-monto">$0.00</h1>
            </div>

            <p>
                Esta operación utiliza el entorno
                de pruebas de Mercado Pago.
                No se realizará un cobro real.
            </p>

        </div>

        <!-- LADO DERECHO -->
        <div class="right-side">

            <h2>Realizar donación de prueba</h2>

            <form
                action="CrearPreferencia.php"
                method="POST"
                id="payment-form"
            >

                <!-- ID DEL PARQUE -->
                <input
                    type="hidden"
                    name="parque_id"
                    value="<?= htmlspecialchars(
                        $id_parque,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <!-- TOKEN DE SEGURIDAD -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_pago'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <!-- MONTO -->
                <div class="input-group">

                    <label for="input-monto">
                        Monto de prueba (MXN)
                    </label>

                    <input
                        type="number"
                        name="monto"
                        id="input-monto"
                        min="1"
                        max="10000"
                        step="0.01"
                        placeholder="Ej: 100"
                        required
                    >

                </div>

                <div id="mercadopago-card-container">

                    <h3>Pago seguro con Mercado Pago</h3>

                    <p>
                        Al continuar, serás redirigido
                        al entorno de pruebas de
                        Mercado Pago.
                    </p>

                </div>

                <button
                    type="submit"
                    class="pay-btn"
                >
                    Continuar a Mercado Pago (prueba)
                </button>

            </form>

            <a
                href="DetalleParque.php?id=<?= urlencode($id_parque) ?>"
                class="back-link"
            >
                Cancelar y volver
            </a>

        </div>

    </div>
</div>

<script>
const inputMonto = document.getElementById('input-monto');
const displayMonto = document.getElementById('display-monto');

inputMonto.addEventListener('input', function () {
    const monto = Number(this.value);

    displayMonto.textContent =
        '$' + (
            Number.isFinite(monto) && monto >= 0
                ? monto
                : 0
        ).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
});
</script>

</body>
</html>
