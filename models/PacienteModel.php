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
