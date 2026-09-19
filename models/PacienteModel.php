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
        $sql = "SELECT p.id_paciente, p.nombre, p.email, p.fecha_nacimiento, p.sexo, p.objetivo,
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
}