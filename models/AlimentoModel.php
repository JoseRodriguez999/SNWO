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
        $sql = "SELECT a.id_alimento, a.id_categoria, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                       a.carbohidratos_g, a.grasas_g, a.fibra_g, a.sodio_mg, a.azucar_g, c.nombre AS categoria
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
        return Alergenos::filtrar($stmt->fetchAll(), $restricciones['alergenos'] ?? null);
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

    public function obtenerPorId(int $idAlimento): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                    a.carbohidratos_g, a.grasas_g, c.nombre AS categoria
             FROM tbl_alimentos a
             INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
             WHERE a.id_alimento = :id"
        );
        $stmt->execute(['id' => $idAlimento]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Busca sustitutos para Personalizar: coincide con el término de búsqueda y
     * pertenece a una categoría válida para ese tiempo de comida (mismas reglas
     * que usa el generador automático, para no romper la coherencia del plan).
     */
    public function buscarCompatibles(string $termino, array $categoriasPermitidas, bool $permitirHuevo = false, ?array $alergenos = null): array
    {
        if (empty($categoriasPermitidas)) {
            return Alergenos::filtrar($this->buscarPorNombre($termino), $alergenos);
        }

        $marcadores = [];
        $params = ['termino' => '%' . $termino . '%'];
        foreach ($categoriasPermitidas as $i => $cat) {
            $clave = "cat$i";
            $marcadores[] = ":$clave";
            $params[$clave] = $cat;
        }

        $sql = "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, a.proteinas_g,
                       a.carbohidratos_g, a.grasas_g, c.nombre AS categoria
                FROM tbl_alimentos a
                INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
                WHERE a.activo = 1 AND a.nombre LIKE :termino
                  AND (c.nombre IN (" . implode(',', $marcadores) . ")"
                  . ($permitirHuevo ? " OR a.nombre LIKE '%huevo%'" : "") . ")
                ORDER BY a.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return Alergenos::filtrar($stmt->fetchAll(), $alergenos);
    }

    /** Categorías para el selector del formulario "Nuevo alimento" */
    public function listarCategorias(): array
    {
        return $this->db->query("SELECT id_categoria, nombre FROM tbl_categorias_alimento ORDER BY nombre")->fetchAll();
    }

    /** true si ya existe un alimento con ese nombre (se puede excluir el que se está editando) */
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbl_alimentos WHERE LOWER(nombre) = LOWER(:n) AND id_alimento <> :id");
        $stmt->execute(['n' => $nombre, 'id' => $excluirId ?? 0]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Actualiza los datos del alimento y recalcula su densidad energética (kcal por 100 g) */
    public function actualizar(int $id, array $d): void
    {
        $densidad = $d['porcion_gramos'] > 0 ? round($d['calorias'] / $d['porcion_gramos'] * 100, 2) : null;
        $this->db->prepare(
            "UPDATE tbl_alimentos
             SET id_categoria = :cat, nombre = :nombre, porcion_gramos = :porcion, calorias = :kcal,
                 proteinas_g = :prot, grasas_g = :gras, carbohidratos_g = :carb,
                 fibra_g = :fibra, sodio_mg = :sodio, azucar_g = :azucar, densidad_energetica = :dens
             WHERE id_alimento = :id"
        )->execute([
            'cat' => $d['id_categoria'], 'nombre' => $d['nombre'], 'porcion' => $d['porcion_gramos'],
            'kcal' => $d['calorias'], 'prot' => $d['proteinas_g'], 'gras' => $d['grasas_g'],
            'carb' => $d['carbohidratos_g'], 'fibra' => $d['fibra_g'], 'sodio' => $d['sodio_mg'],
            'azucar' => $d['azucar_g'], 'dens' => $densidad, 'id' => $id,
        ]);
    }

    /** Inserta el alimento y devuelve su id. La densidad energética (kcal por 100 g) se calcula aquí. */
    public function crear(array $d): int
    {
        $densidad = $d['porcion_gramos'] > 0 ? round($d['calorias'] / $d['porcion_gramos'] * 100, 2) : null;
        $stmt = $this->db->prepare(
            "INSERT INTO tbl_alimentos
                (id_categoria, nombre, porcion_gramos, calorias, proteinas_g, grasas_g, carbohidratos_g,
                 fibra_g, sodio_mg, azucar_g, densidad_energetica, activo)
             OUTPUT INSERTED.id_alimento
             VALUES
                (:cat, :nombre, :porcion, :kcal, :prot, :gras, :carb, :fibra, :sodio, :azucar, :dens, 1)"
        );
        $stmt->execute([
            'cat' => $d['id_categoria'], 'nombre' => $d['nombre'], 'porcion' => $d['porcion_gramos'],
            'kcal' => $d['calorias'], 'prot' => $d['proteinas_g'], 'gras' => $d['grasas_g'],
            'carb' => $d['carbohidratos_g'], 'fibra' => $d['fibra_g'], 'sodio' => $d['sodio_mg'],
            'azucar' => $d['azucar_g'], 'dens' => $densidad,
        ]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Sustitutos permitidos para un paciente en un tiempo de comida: misma categoría que usa el
     * generador, respetando las restricciones clínicas (sodio/azúcar por porción) y excluyendo
     * los alimentos que el paciente marcó como "no me gusta". Los que le gustan salen primero.
     * Con $soloId solo comprueba si ese alimento concreto es un sustituto válido.
     */
    public function buscarSustitutos(string $termino, array $categorias, bool $permitirHuevo, array $restricciones, int $idPaciente, ?int $soloId = null): array
    {
        $params = ['pac' => $idPaciente, 'termino' => '%' . $termino . '%'];
        $sql = "SELECT a.id_alimento, a.nombre, a.porcion_gramos, a.calorias, c.nombre AS categoria, p.tipo_preferencia
                FROM tbl_alimentos a
                INNER JOIN tbl_categorias_alimento c ON c.id_categoria = a.id_categoria
                LEFT JOIN tbl_preferencias_alimento p ON p.id_alimento = a.id_alimento AND p.id_paciente = :pac
                WHERE a.activo = 1 AND a.nombre LIKE :termino
                  AND (p.tipo_preferencia IS NULL OR p.tipo_preferencia <> 'no_me_gusta')";

        if (!empty($categorias)) {
            $marcadores = [];
            foreach (array_values($categorias) as $i => $cat) {
                $marcadores[] = ":cat$i";
                $params["cat$i"] = $cat;
            }
            $sql .= " AND (c.nombre IN (" . implode(',', $marcadores) . ")"
                  . ($permitirHuevo ? " OR a.nombre LIKE '%huevo%'" : "") . ")";
        }
        if (isset($restricciones['sodio_max'])) {
            $sql .= " AND (a.sodio_mg IS NULL OR a.sodio_mg <= :sodio_max)";
            $params['sodio_max'] = $restricciones['sodio_max'] / 6;
        }
        if (isset($restricciones['azucar_max'])) {
            $sql .= " AND (a.azucar_g IS NULL OR a.azucar_g <= :azucar_max)";
            $params['azucar_max'] = $restricciones['azucar_max'] / 6;
        }
        if ($soloId !== null) {
            $sql .= " AND a.id_alimento = :solo";
            $params['solo'] = $soloId;
        }
        $sql .= " ORDER BY CASE WHEN p.tipo_preferencia = 'me_gusta' THEN 0 ELSE 1 END, a.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return Alergenos::filtrar($stmt->fetchAll(), $restricciones['alergenos'] ?? null);
    }
}
