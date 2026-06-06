<?php
$hostname = isset($_ENV['MYSQLHOST']) ? $_ENV['MYSQLHOST'] : (getenv('MYSQLHOST') ?: 'localhost');
$username = isset($_ENV['MYSQLUSER']) ? $_ENV['MYSQLUSER'] : (getenv('MYSQLUSER') ?: 'root');
$password = isset($_ENV['MYSQLPASSWORD']) ? $_ENV['MYSQLPASSWORD'] : (getenv('MYSQLPASSWORD') ?: '');
$database = isset($_ENV['MYSQLDATABASE']) ? $_ENV['MYSQLDATABASE'] : (getenv('MYSQLDATABASE') ?: 'railway');
$port     = isset($_ENV['MYSQLPORT']) ? $_ENV['MYSQLPORT'] : (getenv('MYSQLPORT') ?: '3306');

$conn = mysqli_connect($hostname, $username, $password, $database, $port);

if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}
?>