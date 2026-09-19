<?php
declare(strict_types=1);

class UsuarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_usuario, nombre_usuario, email, contrasena_hash, rol, activo
             FROM tbl_usuarios WHERE email = :email"
        );
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function actualizarUltimoAcceso(int $idUsuario): void
    {
        $this->db->prepare("UPDATE tbl_usuarios SET ultimo_acceso = GETDATE() WHERE id_usuario = :id")
                  ->execute(['id' => $idUsuario]);
    }

    public function actualizarHash(int $idUsuario, string $nuevoHash): void
    {
        $this->db->prepare("UPDATE tbl_usuarios SET contrasena_hash = :hash WHERE id_usuario = :id")
                  ->execute(['hash' => $nuevoHash, 'id' => $idUsuario]);
    }

    public function listarTodos(): array
    {
        $sql = "SELECT id_usuario, nombre_usuario, email, rol, activo, ultimo_acceso
                FROM tbl_usuarios
                ORDER BY nombre_usuario";
        return $this->db->query($sql)->fetchAll();
    }

    public function cambiarEstado(int $idUsuario, bool $activo): void
    {
        $this->db->prepare("UPDATE tbl_usuarios SET activo = :activo WHERE id_usuario = :id")
                  ->execute(['activo' => $activo ? 1 : 0, 'id' => $idUsuario]);
    }
}
