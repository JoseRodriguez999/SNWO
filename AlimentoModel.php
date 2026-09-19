<?php
declare(strict_types=1);

class AlimentoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function listarConCategoria(): array
    {
        $sql = "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                       a.carbohidratos_g, a.grasas_g, c.nombre AS categoria
                FROM tbl_alimentos a
                INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
                WHERE a.activo = 1
                ORDER BY a.nombre";
        return $this->db->query($sql)->fetchAll();
    }

    public function contarTotal(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM tbl_alimentos WHERE activo = 1")->fetchColumn();
    }

    /**
     * FiltrarPorPatologia() — documentado en el Módulo AlimentoModel del proyecto:
     * "Consulta SQL Server para excluir automáticamente los alimentos incompatibles
     * con las condiciones clínicas del paciente (ej. elimina azúcares simples para
     * diabéticos, restringe sodio para hipertensos)".
     *
     * $restricciones viene ya combinado (el valor MÁS estricto de todas las
     * patologías activas del paciente) desde DietaModel::obtenerRestriccionesCombinadas().
     * Como restricciones_json define límites DIARIOS y aquí filtramos por PORCIÓN
     * individual, se reparte el límite diario entre ~6 tiempos de comida (criterio
     * de diseño documentado aquí para trazabilidad ante el comité evaluador).
     */
    public function filtrarPorPatologia(array $restricciones): array
    {
        $sql = "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                       a.carbohidratos_g, a.grasas_g, a.sodio_mg, a.azucar_g, c.nombre AS categoria
                FROM tbl_alimentos a
                INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
                WHERE a.activo = 1";
        $params = [];

        if (isset($restricciones['sodio_max'])) {
            $sql .= " AND (a.sodio_mg IS NULL OR a.sodio_mg <= :sodio_max)";
            $params['sodio_max'] = $restricciones['sodio_max'] / 6;
        }
        if (isset($restricciones['azucar_max'])) {
            $sql .= " AND (a.azucar_g IS NULL OR a.azucar_g <= :azucar_max)";
            $params['azucar_max'] = $restricciones['azucar_max'] / 6;
        }

        $sql .= " ORDER BY a.calorias ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorNombre(string $termino): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                    a.carbohidratos_g, a.grasas_g, c.nombre AS categoria
             FROM tbl_alimentos a
             INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
             WHERE a.activo = 1 AND a.nombre LIKE :termino
             ORDER BY a.nombre"
        );
        $stmt->execute(['termino' => '%' . $termino . '%']);
        return $stmt->fetchAll();
    }
}
