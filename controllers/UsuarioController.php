<?php
declare(strict_types=1);

class UsuarioController
{
    private UsuarioModel $usuarios;
    private PacienteModel $pacientes;

    // Solo existen dos roles. 'Administrador' queda únicamente como rol heredado (mismos permisos que Nutricionista).
    private const ROLES = ['Nutricionista', 'Paciente'];

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
        $this->pacientes = new PacienteModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        // Nutricionista tiene la gestión de usuarios (un 'Administrador' heredado conserva los mismos permisos)
        $esAdmin    = in_array($_SESSION['rol'] ?? '', ['Administrador', 'Nutricionista'], true);
        $puedeCrear = $esAdmin;
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
            $error = $puedeCrear ? $this->crear($esAdmin) : 'No tienes permiso para crear usuarios.';
            if ($error === '') {
                $_SESSION['flash_exito'] = 'Usuario creado correctamente.';
                header('Location: usuarios.php');
                exit;
            }
        }

        $errorEdicion = '';
        $oldEdit = [];
        if ($accion === 'editar_usuario') {
            $oldEdit = [
                'id_usuario' => (int)($_POST['id_usuario'] ?? 0), 'nombre_usuario' => trim((string)($_POST['nombre_usuario'] ?? '')),
                'email' => trim((string)($_POST['email'] ?? '')), 'rol' => trim((string)($_POST['rol'] ?? '')),
            ];
            $errorEdicion = $esAdmin ? $this->editar() : 'No tienes permiso para editar usuarios.';
            if ($errorEdicion === '') {
                $_SESSION['flash_exito'] = 'Usuario actualizado correctamente.';
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
            'errorEdicion' => $errorEdicion,
            'oldEdit'  => $oldEdit,
            'exito'    => $exito,
            'old'      => $old,
            'esAdmin'  => $esAdmin,
            'puedeCrear' => $puedeCrear,
            'pacientesLibres' => $puedeCrear ? $this->pacientes->listarSinUsuario() : [],
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
    private function crear(bool $esAdmin): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }

        $nombre   = trim((string)($_POST['nombre_usuario'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $rol      = trim((string)($_POST['rol'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirma = (string)($_POST['password2'] ?? '');
        $idPaciente = (int)($_POST['id_paciente'] ?? 0);

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
        if (!$esAdmin && $rol !== 'Paciente') {
            return 'No tienes permiso para crear cuentas de personal.';
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

        // Un usuario Paciente debe quedar vinculado a un paciente que aún no tenga cuenta
        $vinculo = null;
        if ($rol === 'Paciente') {
            $libres = array_map(static fn($p) => (int)$p['id_paciente'], $this->pacientes->listarSinUsuario());
            if (!in_array($idPaciente, $libres, true)) {
                return 'Selecciona el paciente al que pertenece esta cuenta (que no tenga ya un usuario).';
            }
            $vinculo = $idPaciente;
        }

        try {
            $id = $this->usuarios->crear($nombre, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $rol, $vinculo);
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, 'USUARIO_CREADO:' . $rol, 'tbl_usuarios', $id);
            return '';
        } catch (PDOException $e) {
            error_log('Error al crear usuario SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar el usuario. Intenta de nuevo.';
        }
    }

    /** @return string Vacío si se actualizó bien; mensaje de error en caso contrario (Administrador y Nutricionista) */
    private function editar(): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }
        $id = (int)($_POST['id_usuario'] ?? 0);
        $actual = $id > 0 ? $this->usuarios->buscarPorId($id) : null;
        if (!$actual) {
            return 'El usuario ya no existe.';
        }

        $nombre   = trim((string)($_POST['nombre_usuario'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $rol      = trim((string)($_POST['rol'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirma = (string)($_POST['password2'] ?? '');

        if ($nombre === '' || $email === '' || $rol === '') {
            return 'Completa nombre, correo y rol.';
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
        // El rol Paciente está ligado a un paciente concreto: no se cambia desde aquí (ni hacia ni desde Paciente)
        if (($actual['rol'] === 'Paciente') !== ($rol === 'Paciente')) {
            return 'No se puede convertir una cuenta de Paciente en personal (ni al revés). Crea una cuenta nueva.';
        }
        // Un administrador no puede quitarse su propio rol
        if ($id === (int)($_SESSION['id_usuario'] ?? 0) && $rol !== $actual['rol'] && $actual['rol'] !== 'Administrador') {
            return 'No puedes cambiar tu propio rol.';
        }
        if ($this->usuarios->existeEmailOtro($email, $id)) {
            return 'Ya existe otro usuario con ese correo.';
        }

        $nuevoHash = null;
        if ($password !== '' || $confirma !== '') {
            if (strlen($password) < 8)  return 'La nueva contraseña debe tener al menos 8 caracteres.';
            if (strlen($password) > 72) return 'La nueva contraseña no puede superar los 72 caracteres.';
            if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                return 'La nueva contraseña debe combinar letras y números.';
            }
            if ($password !== $confirma) {
                return 'Las contraseñas no coinciden.';
            }
            $nuevoHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        try {
            $this->usuarios->actualizar($id, $nombre, $email, $rol);
            $accion = 'USUARIO_EDITADO';
            if ($nuevoHash !== null) {
                $this->usuarios->actualizarHash($id, $nuevoHash);
                $accion .= ':PASSWORD_RESTABLECIDA';
            }
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, $accion, 'tbl_usuarios', $id);
            return '';
        } catch (PDOException $e) {
            error_log('Error al editar usuario SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar los cambios. Intenta de nuevo.';
        }
    }
}
