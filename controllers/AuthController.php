<?php
declare(strict_types=1);

class AuthController
{
    private UsuarioModel $usuarios;
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEGUNDOS = 300; // 5 minutos

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    /** Devuelve los datos que la vista necesita: ['error' => string, 'csrf' => string] */
    public function manejar(): array
    {
        Sesion::iniciar();

        if (!empty($_SESSION['id_usuario'])) {
            header('Location: dashboard.php');
            exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfOk = Sesion::validarCsrf($_POST['csrf_token'] ?? null);

            $bloqueado = false;
            if (!empty($_SESSION['intentos']) && $_SESSION['intentos'] >= self::MAX_INTENTOS) {
                $restante = self::BLOQUEO_SEGUNDOS - (time() - $_SESSION['ultimo_intento']);
                if ($restante > 0) {
                    $bloqueado = true;
                    $error = 'Demasiados intentos fallidos. Intenta de nuevo en ' . ceil($restante / 60) . ' minuto(s).';
                } else {
                    $_SESSION['intentos'] = 0;
                }
            }

            if (!$csrfOk) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } elseif (!$bloqueado) {
                $error = $this->intentarLogin();
            }
        }

        return ['error' => $error, 'csrf' => Sesion::csrfToken()];
    }

    private function intentarLogin(): string
    {
        $correo   = trim((string)($_POST['correo'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($correo === '' || $password === '') {
            return 'Ingresa tu correo y contraseña.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'El formato del correo no es válido.';
        }

        $usuario = $this->usuarios->buscarPorEmail($correo);

        // Mensaje genérico: nunca reveles si falló el correo o la contraseña
        if ($usuario && (int)$usuario['activo'] === 1 && password_verify($password, $usuario['contrasena_hash'])) {
            session_regenerate_id(true);

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre_usuario'];
            $_SESSION['rol']        = $usuario['rol'];
            $_SESSION['intentos']   = 0;

            $this->usuarios->actualizarUltimoAcceso((int)$usuario['id_usuario']);

            if (password_needs_rehash($usuario['contrasena_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $this->usuarios->actualizarHash(
                    (int)$usuario['id_usuario'],
                    password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
                );
            }

            header('Location: dashboard.php');
            exit;
        }

        $_SESSION['intentos']       = ($_SESSION['intentos'] ?? 0) + 1;
        $_SESSION['ultimo_intento'] = time();
        return 'Correo o contraseña incorrectos.';
    }

    public function logout(): void
    {
        Sesion::iniciar();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }

        session_destroy();
        header('Location: login.php');
        exit;
    }
}
