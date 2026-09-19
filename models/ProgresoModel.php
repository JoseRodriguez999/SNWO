<?php
declare(strict_types=1);

class ProgresoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    /** Historial de peso/IMC/% grasa de un paciente, ordenado cronológicamente */
    public function obtenerHistorial(int $idPaciente): array
    {
        $stmt = $this->db->prepare(
            "SELECT fecha_registro, peso_kg, imc, porcentaje_grasa
             FROM tbl_historial_peso
             WHERE id_paciente = :id
             ORDER BY fecha_registro ASC"
        );
        $stmt->execute(['id' => $idPaciente]);
        return $stmt->fetchAll();
    }
}
