<?php
declare(strict_types=1);

/**
 * Database — Punto único de acceso a la conexión PDO.
 * Reutiliza la función obtenerConexion() de config/conexion.php (ya probada
 * y funcionando) en vez de duplicar la lógica de conexión aquí.
 */
class Database
{
    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        if (self::$conexion === null) {
            require_once __DIR__ . '/../config/conexion.php';
            self::$conexion = obtenerConexion();
        }
        return self::$conexion;
    }
}
