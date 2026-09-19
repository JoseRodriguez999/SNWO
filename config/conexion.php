<?php
define('DB_SERVER', 'JOSE-DAVID-99');
define('DB_NAME', 'SNWO');           
define('DB_USER', 'snwo_app');       
define('DB_PASS', '123456');      


function obtenerConexion(): PDO
{
    try {
        $dsn = "sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_NAME . ";TrustServerCertificate=1";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Error de conexión SNWO: ' . $e->getMessage());
        die('Error de conexión a la base de datos. Detalle: ' . $e->getMessage());
    }
}