<?php
/**
 * bootstrap.php — Carga automática de clases (Modelo-Vista-Controlador).
 * Cada archivo "controlador de entrada" (login.php, pacientes.php, evaluacion.php)
 * empieza con require_once __DIR__.'/bootstrap.php' y a partir de ahí puede
 * usar cualquier clase de /core, /models o /controllers sin hacer require manual.
 */
declare(strict_types=1);

spl_autoload_register(function (string $clase): void {
    $carpetas = ['core', 'models', 'controllers'];
    foreach ($carpetas as $carpeta) {
        $ruta = __DIR__ . '/' . $carpeta . '/' . $clase . '.php';
        if (file_exists($ruta)) {
            require_once $ruta;
            return;
        }
    }
});
