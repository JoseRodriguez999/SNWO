<?php
declare(strict_types=1);

/**
 * CambioPacienteModel — Bitácora de los cambios de alimentos que hace el paciente en su plan.
 * La consulta el nutricionista desde "Personalizar".
 */
class CambioPacienteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function registrar(int $idPaciente, int $idDieta, int $dia, string $tiempo, int $idAnterior, int $idNuevo): void
    {
        $this->db->prepare(
            "INSERT INTO tbl_cambios_paciente
                (id_paciente, id_dieta, dia_semana, tiempo_comida, id_alimento_anterior, id_alimento_nuevo)
             VALUES (:p, :d, :dia, :t, :ant, :nvo)"
        )->execute(['p' => $idPaciente, 'd' => $idDieta, 'dia' => $dia, 't' => $tiempo, 'ant' => $idAnterior, 'nvo' => $idNuevo]);
    }

    /** Últimos cambios del paciente, del más reciente al más antiguo */
    public function listarPorPaciente(int $idPaciente, int $limite = 20): array
    {
        $limite = max(1, min(100, $limite));
        $stmt = $this->db->prepare(
            "SELECT TOP $limite c.fecha, c.dia_semana, c.tiempo_comida,
                    a1.nombre AS alimento_anterior, a2.nombre AS alimento_nuevo
             FROM tbl_cambios_paciente c
             INNER JOIN tbl_alimentos a1 ON a1.id_alimento = c.id_alimento_anterior
             INNER JOIN tbl_alimentos a2 ON a2.id_alimento = c.id_alimento_nuevo
             WHERE c.id_paciente = :p
             ORDER BY c.fecha DESC, c.id_cambio DESC"
        );
        $stmt->execute(['p' => $idPaciente]);
        return $stmt->fetchAll();
    }
}
