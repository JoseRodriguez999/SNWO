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
            "SELECT id_usuario, nombre_usuario, email, contrasena_hash, rol, activo, id_paciente
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

    /** $incluirSuperAdmin = false oculta la cuenta SuperAdmin (solo ella misma la ve) */
    public function listarTodos(bool $incluirSuperAdmin = false): array
    {
        $sql = "SELECT id_usuario, nombre_usuario, email, rol, activo, ultimo_acceso, id_paciente
                FROM tbl_usuarios"
             . ($incluirSuperAdmin ? "" : " WHERE rol <> 'SuperAdmin'")
             . " ORDER BY nombre_usuario";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Elimina la cuenta de forma definitiva. Los registros de auditoría que ella generó se conservan
     * (se les quita el usuario para no violar la llave foránea). Nunca elimina a un SuperAdmin.
     */
    public function eliminar(int $idUsuario): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE tbl_auditoria SET id_usuario = NULL WHERE id_usuario = :id")->execute(['id' => $idUsuario]);
            $stmt = $this->db->prepare("DELETE FROM tbl_usuarios WHERE id_usuario = :id AND rol <> 'SuperAdmin'");
            $stmt->execute(['id' => $idUsuario]);
            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('El usuario no se pudo eliminar.');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function cambiarEstado(int $idUsuario, bool $activo): void
    {
        $this->db->prepare("UPDATE tbl_usuarios SET activo = :activo WHERE id_usuario = :id")
                  ->execute(['activo' => $activo ? 1 : 0, 'id' => $idUsuario]);
    }

    public function existeEmail(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE LOWER(email) = LOWER(:e)");
        $stmt->execute(['e' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** true si otro usuario (distinto de $idUsuario) ya usa ese correo */
    public function existeEmailOtro(string $email, int $idUsuario): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE LOWER(email) = LOWER(:e) AND id_usuario <> :id");
        $stmt->execute(['e' => $email, 'id' => $idUsuario]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function buscarPorId(int $idUsuario): ?array
    {
        $stmt = $this->db->prepare("SELECT id_usuario, nombre_usuario, email, rol, activo, id_paciente FROM tbl_usuarios WHERE id_usuario = :id");
        $stmt->execute(['id' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /** Actualiza nombre, correo y rol. El vínculo con el paciente no se toca. */
    public function actualizar(int $idUsuario, string $nombre, string $email, string $rol): void
    {
        $this->db->prepare("UPDATE tbl_usuarios SET nombre_usuario = :n, email = :e, rol = :r WHERE id_usuario = :id")
                  ->execute(['n' => $nombre, 'e' => $email, 'r' => $rol, 'id' => $idUsuario]);
    }

    /** Crea un usuario activo con la contraseña ya cifrada (BCRYPT) y devuelve su id. */
    public function crear(string $nombre, string $email, string $hash, string $rol, ?int $idPaciente = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_usuarios (nombre_usuario, email, contrasena_hash, rol, activo, id_paciente)
             OUTPUT INSERTED.id_usuario
             VALUES (:nombre, :email, :hash, :rol, 1, :paciente)"
        );
        $stmt->execute(['nombre' => $nombre, 'email' => $email, 'hash' => $hash, 'rol' => $rol, 'paciente' => $idPaciente]);
        return (int)$stmt->fetchColumn();
    }
}
