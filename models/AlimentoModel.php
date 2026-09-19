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
}
