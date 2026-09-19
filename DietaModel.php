<?php
declare(strict_types=1);

/**
 * DietaModel — Motor de Cálculo Nutricional (CU-04: Generación de Dieta Automatizada)
 *
 * Implementa, tal como se documenta en el proyecto:
 *   - GenerarDistribucionAutomatica(GET): proteinas_g = GET×0.20/4, carbohidratos_g = GET×0.55/4,
 *     grasas_g = GET×0.25/9 (ajustado si el paciente tiene una patología con carbohidratos_max_pct
 *     o grasas_max_pct más estricto que estos valores por defecto).
 *   - Distribución calórica por tiempo de comida: Desayuno 25%, Almuerzo 35%, Cena 25%, Colación 15%.
 *   - Selección de alimentos: algoritmo Greedy con ajuste de porciones (alternativa elegida en el
 *     documento sobre "Búsqueda Exhaustiva" por ser computacionalmente inviable a esta escala).
 *   - ValidarMacronutrientes(): si la desviación calórica de una comida supera el 1%, se escalan
 *     proporcionalmente los gramos de los alimentos seleccionados para esa comida.
 */
class DietaModel
{
    private PDO $db;
    private AlimentoModel $alimentos;

    /** Mapeo día número (tbl_menu.dia_semana, TINYINT 1-7) -> etiqueta en español */
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /** Tiempos de comida: clave EXACTA que exige el CHECK de tbl_menu => porcentaje del GET */
    private const TIEMPOS = ['Desayuno' => 0.25, 'Almuerzo' => 0.35, 'Cena' => 0.25, 'Colacion' => 0.15];

    public function __construct()
    {
        $this->db = Database::conexion();
        $this->alimentos = new AlimentoModel();
    }

    // ================================================================
    // 1) Datos de entrada: última evaluación y patologías del paciente
    // ================================================================

    public function obtenerEvaluacionMasReciente(int $idPaciente): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT TOP 1 id_evaluacion, get_calculado, formula_utilizada, fecha_evaluacion
             FROM tbl_evaluaciones WHERE id_paciente = :id ORDER BY fecha_evaluacion DESC"
        );
        $stmt->execute(['id' => $idPaciente]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function obtenerNombresPatologias(int $idPaciente): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.nombre FROM tbl_paciente_patologia pp
             INNER JOIN tbl_patologias p ON p.id_patologia = pp.id_patologia
             WHERE pp.id_paciente = :id AND p.activa = 1"
        );
        $stmt->execute(['id' => $idPaciente]);
        return array_column($stmt->fetchAll(), 'nombre');
    }

    /** Combina las restricciones JSON de todas las patologías activas, quedándose con el valor MÁS estricto (mínimo) de cada clave */
    public function obtenerRestriccionesCombinadas(int $idPaciente): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.restricciones_json FROM tbl_paciente_patologia pp
             INNER JOIN tbl_patologias p ON p.id_patologia = pp.id_patologia
             WHERE pp.id_paciente = :id AND p.activa = 1 AND p.restricciones_json IS NOT NULL"
        );
        $stmt->execute(['id' => $idPaciente]);

        $combinadas = [];
        foreach ($stmt->fetchAll() as $fila) {
            $json = json_decode($fila['restricciones_json'], true);
            if (!is_array($json)) continue;
            foreach ($json as $clave => $valor) {
                $combinadas[$clave] = isset($combinadas[$clave]) ? min($combinadas[$clave], $valor) : $valor;
            }
        }
        return $combinadas;
    }

    // ================================================================
    // 2) GenerarDistribucionAutomatica()
    // ================================================================

    public function generarDistribucionAutomatica(float $get, array $restricciones = []): array
    {
        $pctProt = 0.20;
        $pctCarb = 0.55;
        $pctGrasa = 0.25;

        // Si una patología exige un tope de carbohidratos más estricto que el 55% por defecto
        // (ej. Diabetes tipo 2: carbohidratos_max_pct = 45), se reduce y el excedente se
        // reparte a proteína/grasa — criterio de ajuste documentado aquí para el comité.
        if (isset($restricciones['carbohidratos_max_pct'])) {
            $limite = $restricciones['carbohidratos_max_pct'] / 100;
            if ($limite < $pctCarb) {
                $diferencia = $pctCarb - $limite;
                $pctCarb = $limite;
                $pctProt += $diferencia * 0.6;
                $pctGrasa += $diferencia * 0.4;
            }
        }
        // Si exige tope de grasas (ej. Dislipidemia: grasas_max_pct = 25), el excedente vuelve a carbohidratos
        if (isset($restricciones['grasas_max_pct'])) {
            $limite = $restricciones['grasas_max_pct'] / 100;
            if ($limite < $pctGrasa) {
                $diferencia = $pctGrasa - $limite;
                $pctGrasa = $limite;
                $pctCarb += $diferencia;
            }
        }

        return [
            'pct_proteinas'      => round($pctProt * 100, 1),
            'pct_carbohidratos'  => round($pctCarb * 100, 1),
            'pct_grasas'         => round($pctGrasa * 100, 1),
            'proteinas_g'        => round(($get * $pctProt) / 4, 1),
            'carbohidratos_g'    => round(($get * $pctCarb) / 4, 1),
            'grasas_g'           => round(($get * $pctGrasa) / 9, 1),
        ];
    }

    // ================================================================
    // 3) Selección de alimentos (Greedy + ajuste de porciones)
    // ================================================================

    /**
     * Selecciona alimentos del catálogo disponible hasta acercarse al objetivo calórico
     * de una comida, priorizando los menos usados en la semana (variedad), y luego
     * escala proporcionalmente las porciones para que la desviación quede en 0%
     * (ValidarMacronutrientes: "si supera el umbral, PHP ajusta las porciones automáticamente").
     */
    private function seleccionarAlimentosParaComida(array $catalogo, float $kcalObjetivo, array &$usoConteo): array
    {
        if (empty($catalogo) || $kcalObjetivo <= 0) {
            return [];
        }

        usort($catalogo, function ($a, $b) use ($usoConteo) {
            $usoA = $usoConteo[$a['id_alimento']] ?? 0;
            $usoB = $usoConteo[$b['id_alimento']] ?? 0;
            if ($usoA !== $usoB) return $usoA <=> $usoB;
            return $a['calorias'] <=> $b['calorias'];
        });

        $seleccion = [];
        $acumulado = 0.0;
        foreach ($catalogo as $alimento) {
            if ($acumulado >= $kcalObjetivo * 0.95 || count($seleccion) >= 4) {
                break;
            }
            $seleccion[] = $alimento;
            $acumulado += (float)$alimento['calorias'];
            $usoConteo[$alimento['id_alimento']] = ($usoConteo[$alimento['id_alimento']] ?? 0) + 1;
        }

        if ($acumulado > 0) {
            $factor = $kcalObjetivo / $acumulado;
            foreach ($seleccion as &$a) {
                $a['porcion_ajustada_g']  = round((float)$a['porcion_gramos'] * $factor, 1);
                $a['calorias_aportadas']  = round((float)$a['calorias'] * $factor, 1);
                $a['proteinas_aportadas'] = round((float)$a['proteinas_g'] * $factor, 1);
                $a['carbohidratos_aportados'] = round((float)$a['carbohidratos_g'] * $factor, 1);
                $a['grasas_aportadas']    = round((float)$a['grasas_g'] * $factor, 1);
            }
            unset($a);
        }

        return $seleccion;
    }

    // ================================================================
    // 4) Generación completa del menú semanal (en memoria, sin guardar aún)
    // ================================================================

    /**
     * @throws RuntimeException si el paciente no tiene evaluación registrada (precondición del CU-04)
     */
    public function generarMenuSemanal(int $idPaciente): array
    {
        $evaluacion = $this->obtenerEvaluacionMasReciente($idPaciente);
        if (!$evaluacion) {
            throw new RuntimeException('El paciente no tiene una evaluación antropométrica registrada. Regístrala antes de generar la dieta.');
        }

        $get = (float)$evaluacion['get_calculado'];
        $restricciones = $this->obtenerRestriccionesCombinadas($idPaciente);
        $distribucion = $this->generarDistribucionAutomatica($get, $restricciones);
        $catalogo = $this->alimentos->filtrarPorPatologia($restricciones);

        $advertencia = null;
        if (count($catalogo) < 3) {
            $advertencia = 'El catálogo de alimentos compatibles con las patologías de este paciente tiene solo ' .
                           count($catalogo) . ' opción(es). Se recomienda ampliar el catálogo con más alimentos de estas categorías.';
        }

        $usoConteo = [];
        $menu = [];
        foreach (self::DIAS as $numDia => $etiquetaDia) {
            foreach (self::TIEMPOS as $tiempo => $pct) {
                $kcalObjetivo = $get * $pct;
                $seleccion = $this->seleccionarAlimentosParaComida($catalogo, $kcalObjetivo, $usoConteo);
                $menu[$numDia][$tiempo] = [
                    'alimentos'    => $seleccion,
                    'kcal_total'   => round(array_sum(array_column($seleccion, 'calorias_aportadas')), 1),
                    'prot_total'   => round(array_sum(array_column($seleccion, 'proteinas_aportadas')), 1),
                    'ch_total'     => round(array_sum(array_column($seleccion, 'carbohidratos_aportados')), 1),
                    'grasa_total'  => round(array_sum(array_column($seleccion, 'grasas_aportadas')), 1),
                ];
            }
        }

        return [
            'idEvaluacion'  => (int)$evaluacion['id_evaluacion'],
            'get'           => $get,
            'distribucion'  => $distribucion,
            'catalogoTotal' => count($catalogo),
            'advertencia'   => $advertencia,
            'menu'          => $menu,
        ];
    }

    // ================================================================
    // 5) Persistencia: guarda el menú generado en tbl_dietas/tbl_menu/tbl_menu_alimento
    // ================================================================

    public function guardarDieta(int $idPaciente, array $generado): int
    {
        $this->db->beginTransaction();
        try {
            $fechaInicio = (new DateTime())->format('Y-m-d');
            $fechaFin    = (new DateTime())->modify('+6 days')->format('Y-m-d');

            $stmtDieta = $this->db->prepare(
                "INSERT INTO tbl_dietas
                    (id_paciente, id_evaluacion, fecha_inicio, fecha_fin, estado,
                     get_objetivo, proteinas_meta_g, grasas_meta_g, carbohidratos_meta_g)
                 OUTPUT INSERTED.id_dieta
                 VALUES
                    (:id_paciente, :id_evaluacion, :inicio, :fin, 'Borrador',
                     :get, :prot, :grasa, :ch)"
            );
            $stmtDieta->execute([
                'id_paciente'   => $idPaciente,
                'id_evaluacion' => $generado['idEvaluacion'],
                'inicio'        => $fechaInicio,
                'fin'           => $fechaFin,
                'get'           => $generado['get'],
                'prot'          => $generado['distribucion']['proteinas_g'],
                'grasa'         => $generado['distribucion']['grasas_g'],
                'ch'            => $generado['distribucion']['carbohidratos_g'],
            ]);
            $idDieta = (int)$stmtDieta->fetchColumn();

            $stmtMenu = $this->db->prepare(
                "INSERT INTO tbl_menu
                    (id_dieta, dia_semana, tiempo_comida, calorias_total, proteinas_total_g, grasas_total_g, carbohidratos_total_g)
                 OUTPUT INSERTED.id_menu
                 VALUES (:id_dieta, :dia, :tiempo, :kcal, :prot, :grasa, :ch)"
            );
            $stmtMenuAlimento = $this->db->prepare(
                "INSERT INTO tbl_menu_alimento (id_menu, id_alimento, porcion_gramos, calorias_aportadas, orden)
                 VALUES (:id_menu, :id_alimento, :porcion, :kcal, :orden)"
            );

            foreach ($generado['menu'] as $numDia => $tiempos) {
                foreach ($tiempos as $tiempo => $datos) {
                    $stmtMenu->execute([
                        'id_dieta' => $idDieta, 'dia' => $numDia, 'tiempo' => $tiempo,
                        'kcal' => $datos['kcal_total'], 'prot' => $datos['prot_total'],
                        'grasa' => $datos['grasa_total'], 'ch' => $datos['ch_total'],
                    ]);
                    $idMenu = (int)$stmtMenu->fetchColumn();

                    $orden = 1;
                    foreach ($datos['alimentos'] as $alimento) {
                        $stmtMenuAlimento->execute([
                            'id_menu'     => $idMenu,
                            'id_alimento' => $alimento['id_alimento'],
                            'porcion'     => $alimento['porcion_ajustada_g'],
                            'kcal'        => $alimento['calorias_aportadas'],
                            'orden'       => $orden++,
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $idDieta;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ================================================================
    // 6) Lectura de la dieta ya guardada (para mostrarla sin regenerar)
    // ================================================================

    public function obtenerDietaActual(int $idPaciente): ?array
    {
        $stmtDieta = $this->db->prepare(
            "SELECT TOP 1 id_dieta, estado, get_objetivo, proteinas_meta_g, grasas_meta_g,
                    carbohidratos_meta_g, fecha_creacion, fecha_inicio, fecha_fin
             FROM tbl_dietas WHERE id_paciente = :id ORDER BY fecha_creacion DESC"
        );
        $stmtDieta->execute(['id' => $idPaciente]);
        $dieta = $stmtDieta->fetch();
        if (!$dieta) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT m.id_menu, m.dia_semana, m.tiempo_comida, m.calorias_total,
                    m.proteinas_total_g, m.grasas_total_g, m.carbohidratos_total_g,
                    a.id_alimento, a.nombre AS alimento_nombre, ma.porcion_gramos, ma.calorias_aportadas
             FROM tbl_menu m
             LEFT JOIN tbl_menu_alimento ma ON ma.id_menu = m.id_menu
             LEFT JOIN tbl_alimentos a ON a.id_alimento = ma.id_alimento
             WHERE m.id_dieta = :id_dieta
             ORDER BY m.dia_semana, m.tiempo_comida, ma.orden"
        );
        $stmt->execute(['id_dieta' => $dieta['id_dieta']]);

        $menu = [];
        foreach ($stmt->fetchAll() as $fila) {
            $dia = (int)$fila['dia_semana'];
            $tiempo = $fila['tiempo_comida'];
            if (!isset($menu[$dia][$tiempo])) {
                $menu[$dia][$tiempo] = [
                    'kcal_total'  => (float)$fila['calorias_total'],
                    'prot_total'  => (float)$fila['proteinas_total_g'],
                    'ch_total'    => (float)$fila['carbohidratos_total_g'],
                    'grasa_total' => (float)$fila['grasas_total_g'],
                    'alimentos'   => [],
                ];
            }
            if ($fila['id_alimento']) {
                $menu[$dia][$tiempo]['alimentos'][] = [
                    'nombre'  => $fila['alimento_nombre'],
                    'porcion_ajustada_g' => (float)$fila['porcion_gramos'],
                    'calorias_aportadas' => (float)$fila['calorias_aportadas'],
                ];
            }
        }

        return [
            'idDieta'      => (int)$dieta['id_dieta'],
            'estado'       => $dieta['estado'],
            'get'          => (float)$dieta['get_objetivo'],
            'metaProteina' => (float)$dieta['proteinas_meta_g'],
            'metaGrasa'    => (float)$dieta['grasas_meta_g'],
            'metaCarbohidratos' => (float)$dieta['carbohidratos_meta_g'],
            'fechaInicio'  => $dieta['fecha_inicio'],
            'fechaFin'     => $dieta['fecha_fin'],
            'menu'         => $menu,
        ];
    }

    public static function nombresDias(): array
    {
        return self::DIAS;
    }

    public static function tiemposComida(): array
    {
        return array_keys(self::TIEMPOS);
    }
}
