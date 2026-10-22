<?php
declare(strict_types=1);

/**
 * ReporteModel — Consultas de solo lectura para el módulo de Reportes (solo Nutricionista).
 * Todas las consultas son parametrizadas; ningún valor del usuario se concatena al SQL.
 */
class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    /** Pacientes con su estado, última evaluación y si tienen cuenta de acceso. $estado: todos|activos|inactivos */
    public function pacientes(string $estado = 'todos'): array
    {
        $sql = "SELECT p.id_paciente, p.nombre, p.email, p.telefono, p.objetivo, p.nivel_actividad, p.activo,
                       (SELECT MAX(e.fecha_evaluacion) FROM tbl_evaluaciones e WHERE e.id_paciente = p.id_paciente) AS ultima_evaluacion,
                       (SELECT COUNT(*) FROM tbl_usuarios u WHERE u.id_paciente = p.id_paciente AND u.activo = 1) AS cuentas
                FROM tbl_pacientes p";
        if ($estado === 'activos') {
            $sql .= " WHERE p.activo = 1";
        } elseif ($estado === 'inactivos') {
            $sql .= " WHERE p.activo = 0";
        }
        $sql .= " ORDER BY p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /** Pacientes activos para los selectores: [id_paciente, nombre] */
    public function listaPacientes(): array
    {
        return $this->db->query("SELECT id_paciente, nombre FROM tbl_pacientes WHERE activo = 1 ORDER BY nombre")->fetchAll();
    }

    /** Paciente activo con los datos que se muestran en los encabezados de reporte */
    public function paciente(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_paciente, nombre, fecha_nacimiento, sexo, objetivo, nivel_actividad
             FROM tbl_pacientes WHERE id_paciente = :id AND activo = 1"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Pacientes activos con su última evaluación (null si nunca se evaluaron) */
    public function activosConUltimaEvaluacion(): array
    {
        $sql = "SELECT p.id_paciente, p.nombre, p.email, p.telefono,
                       (SELECT MAX(e.fecha_evaluacion) FROM tbl_evaluaciones e WHERE e.id_paciente = p.id_paciente) AS ultima_evaluacion
                FROM tbl_pacientes p
                WHERE p.activo = 1
                ORDER BY p.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    /** Dietas creadas entre dos fechas (inclusive), la más reciente primero. Fechas 'Y-m-d'. */
    public function dietas(string $desde, string $hasta): array
    {
        $limiteSuperior = date('Y-m-d', strtotime($hasta . ' +1 day'));
        $stmt = $this->db->prepare(
            "SELECT d.id_dieta, p.nombre, d.fecha_creacion, d.fecha_inicio, d.fecha_fin, d.estado, d.get_objetivo
             FROM tbl_dietas d
             INNER JOIN tbl_pacientes p ON p.id_paciente = d.id_paciente
             WHERE d.fecha_creacion >= :desde AND d.fecha_creacion < :hasta
             ORDER BY d.fecha_creacion DESC"
        );
        $stmt->execute(['desde' => $desde, 'hasta' => $limiteSuperior]);
        return $stmt->fetchAll();
    }
}
