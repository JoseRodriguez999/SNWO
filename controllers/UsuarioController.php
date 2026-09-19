<?php
declare(strict_types=1);

class UsuarioController
{
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
            $this->cambiarEstado();
            header('Location: usuarios.php');
            exit;
        }

        return [
            'usuarios' => $this->usuarios->listarTodos(),
            'csrf'     => Sesion::csrfToken(),
        ];
    }

    private function cambiarEstado(): void
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return; // token inválido: no aplica el cambio, silenciosamente vuelve a la lista
        }
        $id     = (int)($_POST['id_usuario'] ?? 0);
        $activo = (bool)($_POST['activo'] ?? false);
        if ($id > 0) {
            $this->usuarios->cambiarEstado($id, $activo);
        }
    }
}
