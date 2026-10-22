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
    private PreferenciaModel $preferencias;

    /** Mapeo día número (tbl_menu.dia_semana, TINYINT 1-7) -> etiqueta en español */
    private const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    /** Tiempos de comida: clave EXACTA que exige el CHECK de tbl_menu => porcentaje del GET */
    private const TIEMPOS = ['Desayuno' => 0.25, 'Almuerzo' => 0.35, 'Cena' => 0.25, 'Colacion' => 0.15];

    /**
     * Categorías de tbl_categorias_alimento permitidas por tiempo de comida, para que el
     * algoritmo no mezcle alimentos que un nutricionista real jamás combinaría (ej. camarón
     * en el desayuno, o embutidos y manteca en una colación). "Carnes" solo aplica a
     * Almuerzo/Cena; el huevo es la única excepción dentro de Carnes permitida en Desayuno.
     */
    public const CATEGORIAS_POR_TIEMPO = [
        'Desayuno' => ['Cereales', 'Lácteos', 'Frutas'],
        'Almuerzo' => ['Cereales', 'Leguminosas', 'Vegetales', 'Carnes'],
        'Cena'     => ['Cereales', 'Leguminosas', 'Vegetales', 'Carnes'],
        'Colacion' => ['Frutas', 'Lácteos', 'Grasas'],
    ];

    /**
     * Ajuste del GET según el objetivo del paciente (tbl_pacientes.objetivo).
     * Pérdida: -20 % (déficit moderado, ~0.5 kg/semana en promedio); ganancia: +10 %.
     * Piso de seguridad en pérdida: 1200 kcal (mujeres) / 1500 kcal (hombres), sin superar nunca el GET original.
     */
    public const AJUSTE_OBJETIVO = ['perdida' => -0.20, 'mantenimiento' => 0.0, 'ganancia' => 0.10];
    public const PISO_KCAL = ['F' => 1200.0, 'M' => 1500.0];

    public const ESTADOS = ['Borrador', 'Activa', 'Finalizada'];

    public function __construct()
    {
        $this->db = Database::conexion();
        $this->alimentos = new AlimentoModel();
        $this->preferencias = new PreferenciaModel();
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

    /**
     * Aplica el objetivo del paciente al GET calculado.
     * @return array{get:float,base:float,objetivo:string,pct:float,piso:bool}
     */
    public function ajustarGetPorObjetivo(int $idPaciente, float $getBase): array
    {
        $stmt = $this->db->prepare("SELECT sexo, objetivo FROM tbl_pacientes WHERE id_paciente = :id");
        $stmt->execute(['id' => $idPaciente]);
        $p = $stmt->fetch() ?: [];
        $objetivo = (string)($p['objetivo'] ?? 'mantenimiento');
        $pct = self::AJUSTE_OBJETIVO[$objetivo] ?? 0.0;
        $sexo = ($p['sexo'] ?? 'M') === 'F' ? 'F' : 'M';

        $get = $getBase * (1 + $pct);
        $piso = false;
        if ($pct < 0) {
            $minimo = min(self::PISO_KCAL[$sexo], $getBase);
            if ($get < $minimo) { $get = $minimo; $piso = true; }
        }
        return ['get' => round($get, 0), 'base' => $getBase, 'objetivo' => $objetivo, 'pct' => $pct, 'piso' => $piso];
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
        $alergenos = $this->obtenerAlergenos($idPaciente);
        if (!Alergenos::vacio($alergenos)) {
            $combinadas['alergenos'] = $alergenos;
        }
        return $combinadas;
    }

    /**
     * Alergias/intolerancias de la evaluación más reciente que las haya registrado
     * (campo de texto libre del cuestionario de hábitos), ya interpretadas por Alergenos.
     * @return array{grupos:string[], terminos:string[]}
     */
    public function obtenerAlergenos(int $idPaciente): array
    {
        $stmt = $this->db->prepare(
            "SELECT TOP 1 h.alergias_alimentarias
             FROM tbl_cuestionario_habitos h
             INNER JOIN tbl_evaluaciones e ON e.id_evaluacion = h.id_evaluacion
             WHERE e.id_paciente = :id AND h.alergias_alimentarias IS NOT NULL
               AND LTRIM(RTRIM(h.alergias_alimentarias)) <> ''
             ORDER BY e.fecha_evaluacion DESC, e.id_evaluacion DESC"
        );
        $stmt->execute(['id' => $idPaciente]);
        $texto = $stmt->fetchColumn();
        return Alergenos::interpretar($texto === false ? null : (string)$texto);
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
    /**
     * Reduce el catálogo a solo las categorías coherentes con el tiempo de comida indicado
     * (ej. nada de "Carnes" en Colación, nada de mariscos/embutidos en Desayuno salvo huevo).
     * Si el filtrado deja muy pocas opciones (riesgo de repetir siempre lo mismo o quedar
     * vacío), se amplía de vuelta al catálogo completo para esa comida en particular.
     */
    /**
     * Ingredientes de cocina que NUNCA deben aparecer como un alimento del menú por sí solos
     * (nadie se "come" aceite o manteca como plato) — se excluyen de la selección en
     * cualquier tiempo de comida, aunque sigan disponibles en el catálogo para otros usos.
     */
    private const INGREDIENTES_SOLO_COCCION = [
        'Aceite vegetal', 'Aceite de oliva', 'Manteca de cerdo', 'Margarina', 'Mayonesa',
    ];

    private function filtrarPorTiempoComida(array $catalogo, string $tiempo): array
    {
        // Primero quita los ingredientes de solo cocción de TODO el catálogo (incluido el
        // respaldo), para que nunca reaparezcan aunque el filtro por categoría se relaje.
        $catalogoComible = array_values(array_filter(
            $catalogo,
            fn($a) => !in_array($a['nombre'], self::INGREDIENTES_SOLO_COCCION, true)
        ));

        $categoriasPermitidas = self::CATEGORIAS_POR_TIEMPO[$tiempo] ?? [];

        $filtrado = array_filter($catalogoComible, function ($alimento) use ($categoriasPermitidas, $tiempo) {
            if (in_array($alimento['categoria'], $categoriasPermitidas, true)) {
                return true;
            }
            // Excepción: el huevo se permite en Desayuno aunque esté categorizado en "Carnes"
            return $tiempo === 'Desayuno' && stripos($alimento['nombre'], 'huevo') !== false;
        });

        return count($filtrado) >= 3 ? array_values($filtrado) : $catalogoComible;
    }

    private function seleccionarAlimentosParaComida(array $catalogo, float $kcalObjetivo, array &$usoConteo, array $favoritos = []): array
    {
        if (empty($catalogo) || $kcalObjetivo <= 0) {
            return [];
        }

        usort($catalogo, function ($a, $b) use ($usoConteo, $favoritos) {
            // Primero, lo que el paciente marcó como "me gusta" (sin importar uso previo)
            $favA = in_array($a['id_alimento'], $favoritos, true) ? 0 : 1;
            $favB = in_array($b['id_alimento'], $favoritos, true) ? 0 : 1;
            if ($favA !== $favB) return $favA <=> $favB;

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

        $ajuste = $this->ajustarGetPorObjetivo($idPaciente, (float)$evaluacion['get_calculado']);
        $get = $ajuste['get'];
        $restricciones = $this->obtenerRestriccionesCombinadas($idPaciente);
        $distribucion = $this->generarDistribucionAutomatica($get, $restricciones);
        $catalogo = $this->alimentos->filtrarPorPatologia($restricciones);

        // Preferencias del paciente: nunca incluir lo que no le gusta, priorizar lo que sí
        $preferenciasPaciente = $this->preferencias->obtenerPreferencias($idPaciente);
        $favoritos = $preferenciasPaciente['me_gusta'];
        $catalogo = array_values(array_filter(
            $catalogo,
            fn($a) => !in_array($a['id_alimento'], $preferenciasPaciente['no_me_gusta'], true)
        ));

        $advertencia = null;
        if (count($catalogo) < 3) {
            $advertencia = 'El catálogo de alimentos compatibles con las patologías de este paciente tiene solo ' .
                           count($catalogo) . ' opción(es). Se recomienda ampliar el catálogo con más alimentos de estas categorías.';
        }

        // Pre-filtra el catálogo una vez por tiempo de comida (no cambia día a día)
        $catalogoPorTiempo = [];
        foreach (array_keys(self::TIEMPOS) as $tiempo) {
            $catalogoPorTiempo[$tiempo] = $this->filtrarPorTiempoComida($catalogo, $tiempo);
        }

        $usoConteo = [];
        $menu = [];
        foreach (self::DIAS as $numDia => $etiquetaDia) {
            foreach (self::TIEMPOS as $tiempo => $pct) {
                $kcalObjetivo = $get * $pct;
                $seleccion = $this->seleccionarAlimentosParaComida($catalogoPorTiempo[$tiempo], $kcalObjetivo, $usoConteo, $favoritos);
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
            'ajuste'        => $ajuste,
            'distribucion'  => $distribucion,
            'catalogoTotal' => count($catalogo),
            'alergenos'     => Alergenos::describir($restricciones['alergenos'] ?? null),
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

            // Una nueva versión reemplaza a los borradores anteriores (el plan Activo no se toca)
            $this->db->prepare(
                "UPDATE tbl_dietas SET estado = 'Finalizada' WHERE id_paciente = :p AND estado = 'Borrador'"
            )->execute(['p' => $idPaciente]);

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

    /**
     * @param bool $soloActiva true => únicamente el plan en estado 'Activa' (lo que ve el paciente);
     *                         false => el más reciente en cualquier estado (lo que gestiona el personal).
     */
    public function obtenerDietaActual(int $idPaciente, bool $soloActiva = false): ?array
    {
        $stmtDieta = $this->db->prepare(
            "SELECT TOP 1 d.id_dieta, d.estado, d.get_objetivo, d.proteinas_meta_g, d.grasas_meta_g,
                    d.carbohidratos_meta_g, d.fecha_creacion, d.fecha_inicio, d.fecha_fin,
                    e.get_calculado AS get_base
             FROM tbl_dietas d
             LEFT JOIN tbl_evaluaciones e ON e.id_evaluacion = d.id_evaluacion
             WHERE d.id_paciente = :id" . ($soloActiva ? " AND d.estado = 'Activa'" : "") . "
             ORDER BY d.fecha_creacion DESC, d.id_dieta DESC"
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
                    'id_menu'     => (int)$fila['id_menu'],
                    'kcal_total'  => (float)$fila['calorias_total'],
                    'prot_total'  => (float)$fila['proteinas_total_g'],
                    'ch_total'    => (float)$fila['carbohidratos_total_g'],
                    'grasa_total' => (float)$fila['grasas_total_g'],
                    'alimentos'   => [],
                ];
            }
            if ($fila['id_alimento']) {
                $menu[$dia][$tiempo]['alimentos'][] = [
                    'id_alimento' => (int)$fila['id_alimento'],
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
            'getBase'      => $dieta['get_base'] !== null ? (float)$dieta['get_base'] : null,
            'metaProteina' => (float)$dieta['proteinas_meta_g'],
            'metaGrasa'    => (float)$dieta['grasas_meta_g'],
            'metaCarbohidratos' => (float)$dieta['carbohidratos_meta_g'],
            'fechaInicio'  => $dieta['fecha_inicio'],
            'fechaFin'     => $dieta['fecha_fin'],
            'menu'         => $menu,
        ];
    }

    /**
     * Cambia el estado de una dieta. Transiciones válidas: Borrador→Activa, Activa→Finalizada,
     * Borrador→Finalizada. Al activar, la dieta Activa anterior del mismo paciente pasa a Finalizada
     * (un paciente tiene como máximo un plan Activo).
     * @throws RuntimeException si la dieta no existe o la transición no es válida
     */
    public function cambiarEstado(int $idDieta, string $nuevo): array
    {
        if (!in_array($nuevo, ['Activa', 'Finalizada'], true)) {
            throw new RuntimeException('Estado no válido.');
        }
        $stmt = $this->db->prepare("SELECT id_dieta, id_paciente, estado FROM tbl_dietas WHERE id_dieta = :id");
        $stmt->execute(['id' => $idDieta]);
        $dieta = $stmt->fetch();
        if (!$dieta) {
            throw new RuntimeException('La dieta indicada no existe.');
        }
        $actual = (string)$dieta['estado'];
        $validas = ['Borrador' => ['Activa', 'Finalizada'], 'Activa' => ['Finalizada']];
        if (!in_array($nuevo, $validas[$actual] ?? [], true)) {
            throw new RuntimeException('No se puede pasar una dieta de “' . $actual . '” a “' . $nuevo . '”.');
        }

        $this->db->beginTransaction();
        try {
            if ($nuevo === 'Activa') {
                $this->db->prepare(
                    "UPDATE tbl_dietas SET estado = 'Finalizada'
                     WHERE id_paciente = :p AND estado = 'Activa' AND id_dieta <> :d"
                )->execute(['p' => (int)$dieta['id_paciente'], 'd' => $idDieta]);
            }
            $this->db->prepare("UPDATE tbl_dietas SET estado = :e WHERE id_dieta = :d")
                ->execute(['e' => $nuevo, 'd' => $idDieta]);
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
        return ['idPaciente' => (int)$dieta['id_paciente'], 'anterior' => $actual];
    }

    public static function nombresDias(): array
    {
        return self::DIAS;
    }

    public static function tiemposComida(): array
    {
        return array_keys(self::TIEMPOS);
    }

    /** Datos del slot (id_menu) necesarios para saber qué categorías son válidas al buscar sustitutos */
    public function obtenerMenuPorId(int $idMenu): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id_menu, id_dieta, dia_semana, tiempo_comida FROM tbl_menu WHERE id_menu = :id"
        );
        $stmt->execute(['id' => $idMenu]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Sustituye un alimento dentro de un slot (tbl_menu_alimento) por otro, preservando
     * las calorías que ese slot ya tenía asignadas (reescala la porción del nuevo alimento
     * para aportar la misma cantidad de calorías que el que se está reemplazando), y
     * recalcula los totales de tbl_menu para ese id_menu.
     */
    public function sustituirAlimento(int $idMenu, int $idAlimentoViejo, int $idAlimentoNuevo): void
    {
        $this->db->beginTransaction();
        try {
            $stmtViejo = $this->db->prepare(
                "SELECT calorias_aportadas, orden FROM tbl_menu_alimento WHERE id_menu = :m AND id_alimento = :a"
            );
            $stmtViejo->execute(['m' => $idMenu, 'a' => $idAlimentoViejo]);
            $filaVieja = $stmtViejo->fetch();
            if (!$filaVieja) {
                throw new RuntimeException('El alimento que intentas sustituir ya no está en este tiempo de comida.');
            }
            $kcalObjetivo = (float)$filaVieja['calorias_aportadas'];

            $nuevo = $this->alimentos->obtenerPorId($idAlimentoNuevo);
            if (!$nuevo) {
                throw new RuntimeException('El alimento seleccionado no existe.');
            }

            $factor = $nuevo['calorias'] > 0 ? $kcalObjetivo / (float)$nuevo['calorias'] : 1.0;
            $nuevaPorcion = round((float)$nuevo['porcion_gramos'] * $factor, 1);
            $nuevasCalorias = round((float)$nuevo['calorias'] * $factor, 1);

            $this->db->prepare("DELETE FROM tbl_menu_alimento WHERE id_menu = :m AND id_alimento = :a")
                      ->execute(['m' => $idMenu, 'a' => $idAlimentoViejo]);

            $this->db->prepare(
                "INSERT INTO tbl_menu_alimento (id_menu, id_alimento, porcion_gramos, calorias_aportadas, orden)
                 VALUES (:m, :a, :porcion, :kcal, :orden)"
            )->execute([
                'm' => $idMenu, 'a' => $idAlimentoNuevo,
                'porcion' => $nuevaPorcion, 'kcal' => $nuevasCalorias, 'orden' => $filaVieja['orden'],
            ]);

            // Recalcula los totales del slot sumando lo que realmente quedó en tbl_menu_alimento
            $stmtTotales = $this->db->prepare(
                "SELECT SUM(ma.calorias_aportadas) AS kcal,
                        SUM(a.proteinas_g * ma.porcion_gramos / a.porcion_gramos) AS prot,
                        SUM(a.carbohidratos_g * ma.porcion_gramos / a.porcion_gramos) AS ch,
                        SUM(a.grasas_g * ma.porcion_gramos / a.porcion_gramos) AS grasa
                 FROM tbl_menu_alimento ma
                 INNER JOIN tbl_alimentos a ON a.id_alimento = ma.id_alimento
                 WHERE ma.id_menu = :id_menu"
            );
            $stmtTotales->execute(['id_menu' => $idMenu]);
            $totales = $stmtTotales->fetch();

            $this->db->prepare(
                "UPDATE tbl_menu SET calorias_total = :kcal, proteinas_total_g = :prot,
                        carbohidratos_total_g = :ch, grasas_total_g = :grasa
                 WHERE id_menu = :id_menu"
            )->execute([
                'kcal' => round((float)$totales['kcal'], 1), 'prot' => round((float)$totales['prot'], 1),
                'ch' => round((float)$totales['ch'], 1), 'grasa' => round((float)$totales['grasa'], 1),
                'id_menu' => $idMenu,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
