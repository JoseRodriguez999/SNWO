<?php
declare(strict_types=1);

/**
 * Sesion — Centraliza el manejo de sesión y CSRF que antes estaba
 * duplicado al inicio de cada archivo (login.php, pacientes.php, evaluacion.php).
 */
class Sesion
{
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? '') == 443)
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $esHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** Corta la ejecución y redirige a login.php si no hay sesión activa */
    public static function requiereLogin(): void
    {
        if (empty($_SESSION['id_usuario'])) {
            header('Location: login.php');
            exit;
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Valida el token recibido y siempre renueva uno nuevo para el próximo envío */
    public static function validarCsrf(?string $tokenRecibido): bool
    {
        $valido = $tokenRecibido !== null
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $tokenRecibido);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return $valido;
    }

    // ================================================================
    // Paciente activo ("anclado"): se elige una vez y todas las pestañas
    // (Evaluación, Generador, Personalizar, Exportar, Progreso, Preferencias)
    // trabajan con él sin tener que volver a seleccionarlo.
    // ================================================================

    /** @return array{id:int,nombre:string}|null */
    public static function pacienteActivo(): ?array
    {
        return $_SESSION['paciente_activo'] ?? null;
    }

    /** Id del paciente a usar: el de la URL (?id=) si viene, si no el anclado en sesión */
    public static function resolverPacienteId(): int
    {
        if (isset($_GET['id'])) {
            return (int)$_GET['id'];
        }
        return (int)($_SESSION['paciente_activo']['id'] ?? 0);
    }

    public static function anclarPaciente(int $id, string $nombre): void
    {
        $_SESSION['paciente_activo'] = ['id' => $id, 'nombre' => $nombre];
    }

    public static function soltarPaciente(): void
    {
        unset($_SESSION['paciente_activo']);
    }

    /**
     * Llamar justo después de buscar al paciente: si existe, queda anclado; si el anclado
     * ya no existe (inactivo/borrado) y no se pidió uno explícito por URL, se suelta.
     */
    public static function sincronizarPaciente(int $idPaciente, ?array $paciente): void
    {
        if ($paciente) {
            self::anclarPaciente($idPaciente, (string)$paciente['nombre']);
        } elseif (!isset($_GET['id'])) {
            self::soltarPaciente();
        }
    }
}
