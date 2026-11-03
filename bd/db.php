<?php
$host = "sql109.infinityfree.com";
$user = "if0_42072088";
$password = "KX4HspU0Ydq5jf";
$dbname = "if0_42072088_usuarios";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>