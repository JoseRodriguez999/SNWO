<?php
declare(strict_types=1);

class PacienteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function listarActivosConUltimaEvaluacion(): array
    {
        $sql = "SELECT p.id_paciente, p.nombre, p.email, p.fecha_nacimiento, p.sexo, p.objetivo, p.telefono, p.nivel_actividad,
                       (SELECT MAX(e.fecha_evaluacion) FROM tbl_evaluaciones e WHERE e.id_paciente = p.id_paciente) AS ultima_evaluacion
                FROM tbl_pacientes p
                WHERE p.activo = 1
                ORDER BY p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_paciente, nombre, fecha_nacimiento, sexo, nivel_actividad, objetivo
             FROM tbl_pacientes WHERE id_paciente = :id AND activo = 1"
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function existeEmail(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_pacientes WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function crear(array $datos): void
    {
        $sql = "INSERT INTO tbl_pacientes
                    (nombre, fecha_nacimiento, sexo, telefono, email, objetivo, nivel_actividad)
                VALUES
                    (:nombre, :fecha_nacimiento, :sexo, :telefono, :email, :objetivo, :nivel_actividad)";
        $this->db->prepare($sql)->execute($datos);
    }

    /** Pacientes activos que todavía no tienen una cuenta de acceso (para crear usuarios con rol Paciente) */
    public function listarSinUsuario(): array
    {
        return $this->db->query(
            "SELECT p.id_paciente, p.nombre, p.email
             FROM tbl_pacientes p
             WHERE p.activo = 1
               AND NOT EXISTS (SELECT 1 FROM tbl_usuarios u WHERE u.id_paciente = p.id_paciente)
             ORDER BY p.nombre"
        )->fetchAll();
    }

    /** true si OTRO paciente (distinto de $id) ya usa ese correo */
    public function existeEmailOtro(string $email, int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_pacientes WHERE email = :email AND id_paciente <> :id");
        $stmt->execute(['email' => $email, 'id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Elimina un paciente y TODO lo asociado a él (evaluaciones, medidas, historial de peso, dietas y menús,
     * preferencias, cambios, patologías y su cuenta de acceso). Es definitivo; solo lo usa el SuperAdmin.
     * Todo ocurre en una transacción: si algo falla no se borra nada. Los registros de auditoría se conservan.
     */
    public function eliminarDefinitivo(int $id): void
    {
        $dietas = "SELECT id_dieta FROM tbl_dietas WHERE id_paciente = :id";
        $evals  = "SELECT id_evaluacion FROM tbl_evaluaciones WHERE id_paciente = :id";
        $pasos = [
            "DELETE FROM tbl_cambios_paciente WHERE id_paciente = :id",
            "DELETE FROM tbl_menu_alimento WHERE id_menu IN (SELECT id_menu FROM tbl_menu WHERE id_dieta IN ($dietas))",
            "DELETE FROM tbl_menu WHERE id_dieta IN ($dietas)",
            "DELETE FROM tbl_dietas WHERE id_paciente = :id",
            "DELETE FROM tbl_circunferencias WHERE id_evaluacion IN ($evals)",
            "DELETE FROM tbl_bioimpedancia WHERE id_evaluacion IN ($evals)",
            "DELETE FROM tbl_cuestionario_habitos WHERE id_evaluacion IN ($evals)",
            "DELETE FROM tbl_evaluaciones WHERE id_paciente = :id",
            "DELETE FROM tbl_historial_peso WHERE id_paciente = :id",
            "DELETE FROM tbl_preferencias_alimento WHERE id_paciente = :id",
            "DELETE FROM tbl_paciente_patologia WHERE id_paciente = :id",
            "UPDATE tbl_auditoria SET id_usuario = NULL WHERE id_usuario IN (SELECT id_usuario FROM tbl_usuarios WHERE id_paciente = :id AND rol = 'Paciente')",
            "DELETE FROM tbl_usuarios WHERE id_paciente = :id AND rol = 'Paciente'",
            "DELETE FROM tbl_pacientes WHERE id_paciente = :id",
        ];
        $this->db->beginTransaction();
        try {
            foreach ($pasos as $sql) {
                $this->db->prepare($sql)->execute(['id' => $id]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->prepare(
            "UPDATE tbl_pacientes
             SET nombre = :nombre, fecha_nacimiento = :fecha_nacimiento, sexo = :sexo, telefono = :telefono,
                 email = :email, objetivo = :objetivo, nivel_actividad = :nivel_actividad
             WHERE id_paciente = :id AND activo = 1"
        )->execute($d + ['id' => $id]);
    }
}
