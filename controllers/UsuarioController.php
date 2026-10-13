<?php
declare(strict_types=1);

class UsuarioController
{
    private UsuarioModel $usuarios;

    private const ROLES = ['Administrador', 'Nutricionista', 'Paciente'];

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $esAdmin = ($_SESSION['rol'] ?? '') === 'Administrador';
        $accion  = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['accion'] ?? '') : '';
        $error   = '';
        $old     = [];

        if ($accion === 'cambiar_estado') {
            if ($esAdmin) {
                $this->cambiarEstado();
            }
            header('Location: usuarios.php');
            exit;
        }

        if ($accion === 'crear_usuario') {
            $old   = $_POST;
            $error = $esAdmin ? $this->crear() : 'Solo un Administrador puede crear usuarios.';
            if ($error === '') {
                $_SESSION['flash_exito'] = 'Usuario creado correctamente.';
                header('Location: usuarios.php');
                exit;
            }
        }

        $exito = '';
        if (!empty($_SESSION['flash_exito'])) {
            $exito = (string)$_SESSION['flash_exito'];
            unset($_SESSION['flash_exito']);
        }

        return [
            'usuarios' => $this->usuarios->listarTodos(),
            'csrf'     => Sesion::csrfToken(),
            'error'    => $error,
            'exito'    => $exito,
            'old'      => $old,
            'esAdmin'  => $esAdmin,
        ];
    }

    private function cambiarEstado(): void
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return; // token inválido: no aplica el cambio, silenciosamente vuelve a la lista
        }
        $id     = (int)($_POST['id_usuario'] ?? 0);
        $activo = (bool)($_POST['activo'] ?? false);
        // Un administrador no puede desactivarse a sí mismo (se quedaría sin acceso)
        if ($id > 0 && !($id === (int)($_SESSION['id_usuario'] ?? 0) && !$activo)) {
            $this->usuarios->cambiarEstado($id, $activo);
        }
    }

    /** @return string Vacío si se creó bien; mensaje de error en caso contrario */
    private function crear(): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }

        $nombre   = trim((string)($_POST['nombre_usuario'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $rol      = trim((string)($_POST['rol'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirma = (string)($_POST['password2'] ?? '');

        if ($nombre === '' || $email === '' || $rol === '' || $password === '') {
            return 'Completa todos los campos obligatorios.';
        }
        if (mb_strlen($nombre) > 100) {
            return 'El nombre no puede superar los 100 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            return 'El correo no tiene un formato válido.';
        }
        if (!in_array($rol, self::ROLES, true)) {
            return 'Rol inválido.';
        }
        if (strlen($password) < 8)  return 'La contraseña debe tener al menos 8 caracteres.';
        if (strlen($password) > 72) return 'La contraseña no puede superar los 72 caracteres.';
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'La contraseña debe combinar letras y números.';
        }
        if ($password !== $confirma) {
            return 'Las contraseñas no coinciden.';
        }
        if ($this->usuarios->existeEmail($email)) {
            return 'Ya existe un usuario con ese correo.';
        }

        try {
            $id = $this->usuarios->crear($nombre, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $rol);
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, 'USUARIO_CREADO:' . $rol, 'tbl_usuarios', $id);
            return '';
        } catch (PDOException $e) {
            error_log('Error al crear usuario SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar el usuario. Intenta de nuevo.';
        }
    }
}
