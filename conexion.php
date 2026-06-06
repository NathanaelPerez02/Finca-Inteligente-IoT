<?php

$hostname = getenv('MYSQLHOST');
$username = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port     = getenv('MYSQLPORT');

$conn = mysqli_connect(
    $hostname,
    $username,
    $password,
    $database,
    (int)$port
);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

$conexion = $conn;
?>