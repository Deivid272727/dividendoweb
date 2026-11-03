<?php
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $tarjeta = $_POST['tarjeta'];
    $date = $_POST['date'];
    $cvv = $_POST['cvv'];
    $monto = floatval($_POST['monto']);
    $parque_id = intval($_POST['parque_id']);

    // Guardar la donación
    $stmt = $conn->prepare("
        INSERT INTO donacion
        (Name, Tarjeta, Date, CVV, monto, parque_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        die("Error en INSERT: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssdi",
        $name,
        $tarjeta,
        $date,
        $cvv,
        $monto,
        $parque_id
    );

    if ($stmt->execute()) {

        // Actualizar el dinero recaudado del parque
        $stmt2 = $conn->prepare("
            UPDATE parques
            SET recaudado = recaudado + ?
            WHERE id = ?
        ");

        if (!$stmt2) {
            die("Error en UPDATE: " . $conn->error);
        }

        $stmt2->bind_param("di", $monto, $parque_id);
        $stmt2->execute();

        header("Location: DetalleParque.php?id=".$parque_id."&donado=1");
        exit();

    } else {

        die("Error al guardar la donación: " . $stmt->error);

    }

}
?>