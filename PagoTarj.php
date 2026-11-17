<?php

require_once __DIR__ . '/config/mercadopago/public_key.php';

$id_parque = $_GET['id'] ?? '';

if (
    !is_string($id_parque) ||
    $id_parque === '' ||
    preg_match('/[.#$\[\]\/\x00-\x1F\x7F]/', $id_parque)
) {
    http_response_code(400);
    exit('ID de parque inválido.');
}
$mpPublicKey = obtenerMercadoPagoPublicKey();

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pago - Dividendo</title>
    <link rel="stylesheet" href="Css/pago.css">

<script src="https://sdk.mercadopago.com/js/v2"></script>

</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="left-side">
                <img src="https://media1.tenor.com/m/PDVHdZ0XYJoAAAAC/cat-shield.gif" alt="Logo" class="payment-logo">
                <h2>Resumen de Compra</h2>
                <p>Propiedad Seleccionada</p>
                <div class="total-section">
                    <span>Monto a donar:</span>
                    <h1 id="display-monto">$0</h1>
                </div>
            </div>

            <div class="right-side">
<form
    id="payment-form"
    method="POST"
    action="ProcesarDonacion.php"
>

                                   <input
                       type="hidden"
                     name="parque_id"
                      value="<?php echo $id_parque; ?>">

                    <div class="input-group">
                        <label>Monto a donar ($)</label>
<input
    type="number"
    name="monto"
    id="input-monto"
    min="1"
    max="10000"
    step="0.01"
    placeholder="Ej: 500"
    required
    oninput="document.getElementById('display-monto').innerText = '$' + this.value"
>                    </div>
<div id="mercadopago-card-container">
    <h3>Pago seguro con Mercado Pago</h3>
    <p>
        Estamos preparando el formulario
        de donaciones de prueba.
    </p>

    <div id="paymentBrick_container"></div>
</div>

<button type="submit" class="pay-btn">
    Registrar donación de prueba
</button>                     <a
                      href="DetalleParque.php?id=<?= urlencode($id_parque) ?>"
                      class="back-link"> Cancelar y volver
                     </a>
                </form>
            </div>
        </div>
    </div>
<script>
    const mpPublicKey = <?php echo json_encode($mpPublicKey); ?>;

    if (mpPublicKey) {
        const mp = new MercadoPago(mpPublicKey, {
            locale: 'es-MX'
        });

        console.log('Mercado Pago inicializado correctamente');

        // Buscar el contenedor del formulario
        const contenedorPago = document.getElementById('paymentBrick_container');

        if (contenedorPago) {
            console.log('Contenedor de Mercado Pago encontrado');
        } else {
            console.error('No se encontrÃ³ el contenedor de pago');
        }

    } else {
        console.error('No se encontrÃ³ la Public Key');
    }
</script>
</body>
</html>
