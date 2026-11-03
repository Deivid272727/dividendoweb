<?php

session_start();

if(!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] != 'admin')
{
    die("Acceso denegado");
}

require_once 'bd/conexion.php';

$id = $_GET['id'];

$sql = "DELETE FROM parques WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: Admin.php");
exit;

?>