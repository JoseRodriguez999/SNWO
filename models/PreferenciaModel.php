<?php
declare(strict_types=1);

class PreferenciaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    /** Catálogo completo con la preferencia actual del paciente marcada (o null si es neutral) */
    public function obtenerCatalogoConPreferencias(int $idPaciente): array
    {
        $sql = "SELECT a.id_alimento, a.nombre, c.nombre AS categoria, p.tipo_preferencia
                FROM tbl_alimentos a
                INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
                LEFT JOIN tbl_preferencias_alimento p ON p.id_alimento = a.id_alimento AND p.id_paciente = :id_paciente
                WHERE a.activo = 1
                ORDER BY a.nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_paciente' => $idPaciente]);
        return $stmt->fetchAll();
    }

    /** ['me_gusta' => [ids], 'no_me_gusta' => [ids]] — usado por el generador de dietas */
    public function obtenerPreferencias(int $idPaciente): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_alimento, tipo_preferencia FROM tbl_preferencias_alimento WHERE id_paciente = :id"
        );
        $stmt->execute(['id' => $idPaciente]);

        $resultado = ['me_gusta' => [], 'no_me_gusta' => []];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['tipo_preferencia']][] = (int)$fila['id_alimento'];
        }
        return $resultado;
    }

    /** Crea o actualiza la preferencia (upsert manual, portable a cualquier versión de SQL Server) */
    public function guardarPreferencia(int $idPaciente, int $idAlimento, string $tipo): void
    {
        $stmt = $this->db->prepare(
            "SELECT id_preferencia FROM tbl_preferencias_alimento WHERE id_paciente = :p AND id_alimento = :a"
        );
        $stmt->execute(['p' => $idPaciente, 'a' => $idAlimento]);
        $existente = $stmt->fetchColumn();

        if ($existente) {
            $this->db->prepare("UPDATE tbl_preferencias_alimento SET tipo_preferencia = :tipo, fecha_registro = GETDATE() WHERE id_preferencia = :id")
                      ->execute(['tipo' => $tipo, 'id' => $existente]);
        } else {
            $this->db->prepare("INSERT INTO tbl_preferencias_alimento (id_paciente, id_alimento, tipo_preferencia) VALUES (:p, :a, :tipo)")
                      ->execute(['p' => $idPaciente, 'a' => $idAlimento, 'tipo' => $tipo]);
        }
    }

    public function eliminarPreferencia(int $idPaciente, int $idAlimento): void
    {
        $this->db->prepare("DELETE FROM tbl_preferencias_alimento WHERE id_paciente = :p AND id_alimento = :a")
                  ->execute(['p' => $idPaciente, 'a' => $idAlimento]);
    }
}
