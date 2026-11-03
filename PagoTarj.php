<?php

$id_parque = $_GET['id'] ?? 0;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pago - Dividendo</title>
    <link rel="stylesheet" href="Css/pago.css">
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
                <form action="donacion.php" method="POST" id="payment-form">
                    <h3>Detalles de Pago</h3>
                     

                                   <input
                       type="hidden"
                     name="parque_id"
                      value="<?php echo $id_parque; ?>">

                    <div class="input-group">
                        <label>Monto a donar ($)</label>
                        <input type="number" name="monto" id="input-monto" placeholder="Ej: 500" required oninput="document.getElementById('display-monto').innerText = '$' + this.value">
                    </div>

                    <div class="input-group">
                        <label>Nombre en la tarjeta</label>
                        <input type="text" name="name" placeholder="Juan Pérez" required minlength="3" maxlength="40" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ ]+" oninput="this.value=this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑ ]/g,'')">
                    </div>

                    <div class="input-group">
                        <label>Número de tarjeta</label>
                        <input type="text" name="tarjeta" placeholder="0000000000000000" required minlength="16" maxlength="16" pattern="[0-9]{16}" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    </div>

                    <div class="row">
                        <div class="input-group">
                            <label>Expira (Fecha)</label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="input-group">
                            <label>CVV</label>
                            <input type="password" name="cvv" placeholder="123" requiredminlength="3" maxlength="3" pattern="[0-9]{3}" oninput="this.value=this.value.replace(/[^0-9]/g,'')">   
                        </div>
                    </div>

                    <button type="submit" class="pay-btn">Confirmar Pago</button>
                    <a href="Municipios.php" class="back-link">Cancelar y volver</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>