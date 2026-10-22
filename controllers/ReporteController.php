<?php
declare(strict_types=1);

/**
 * ReporteController — Módulo de Reportes (solo Nutricionista).
 * Cada reporte se arma como una estructura genérica
 *   ['titulo','subtitulo','resumen'=>[[etiqueta,valor]],'grafica'=>[[etiqueta,valor]],'bloques'=>[['titulo','columnas','filas']]]
 * que sirve igual para la pantalla, el PDF (ReportePdf) y el CSV.
 */
class ReporteController
{
    public const CATALOGO = [
        'pacientes'   => ['Reporte de pacientes',            'Listado de pacientes con su estado, objetivo, última evaluación y si tienen cuenta de acceso.'],
        'evaluacion'  => ['Evaluación individual',           'Detalle completo de una cita: medidas, circunferencias, bioimpedancia, hábitos y GET.'],
        'progreso'    => ['Progreso del paciente',           'Evolución de peso, IMC y % de grasa de un paciente, con gráfica y cambios entre citas.'],
        'seguimiento' => ['Pacientes sin evaluación reciente', 'Pacientes activos que llevan varios días sin una nueva evaluación.'],
        'dietas'      => ['Dietas generadas por período',    'Planes creados en un rango de fechas, por mes y por estado.'],
    ];

    /** Etiquetas con tilde para columnas conocidas; las demás se generan a partir del nombre de la columna */
    private const ETIQUETAS = [
        'edad_metabolica' => 'Edad metabólica (años)', 'porcentaje_grasa' => '% de grasa', 'porcentaje_agua' => '% de agua corporal',
        'densidad_osea_kg' => 'Densidad ósea', 'grasa_visceral' => 'Grasa visceral (nivel)', 'masa_muscular_kg' => 'Masa muscular',
        'comidas_dia' => 'Comidas al día', 'comidas_por_dia' => 'Comidas al día',
        'agua_litros' => 'Agua al día (L)', 'consumo_agua_litros' => 'Agua al día (L)',
        'porciones_frutas_verduras' => 'Porciones de frutas y verduras', 'horas_sueno' => 'Horas de sueño',
        'consume_alcohol' => 'Consume alcohol', 'frecuencia_alcohol' => 'Frecuencia de alcohol', 'fuma' => 'Fuma',
        'realiza_ejercicio' => 'Realiza ejercicio', 'frecuencia_ejercicio_semana' => 'Ejercicio por semana (veces)',
        'tipo_ejercicio' => 'Tipo de ejercicio', 'nivel_estres' => 'Nivel de estrés',
        'consumo_comida_rapida' => 'Consumo de comida rápida', 'alergias' => 'Alergias / intolerancias',
        'alergias_alimentarias' => 'Alergias / intolerancias', 'observaciones' => 'Observaciones',
    ];

    private ReporteModel $reportes;
    private EvaluacionModel $evaluaciones;
    private ProgresoModel $progreso;
    private AuditoriaModel $auditoria;

    public function __construct()
    {
        $this->reportes     = new ReporteModel();
        $this->evaluaciones = new EvaluacionModel();
        $this->progreso     = new ProgresoModel();
        $this->auditoria    = new AuditoriaModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereNutricionista();

        $r        = (string)($_GET['r'] ?? '');
        $formato  = in_array($_GET['formato'] ?? '', ['pdf', 'csv'], true) ? (string)$_GET['formato'] : 'html';
        $datos    = ['catalogo' => self::CATALOGO, 'r' => '', 'reporte' => null, 'filtros' => [], 'pacientes' => [], 'evaluaciones' => [], 'error' => '', 'exportar' => ''];

        if (!isset(self::CATALOGO[$r])) {
            return $datos;
        }

        $f = $this->filtros($r);
        $datos['r'] = $r;
        $datos['filtros'] = $f;

        try {
            if (in_array($r, ['evaluacion', 'progreso'], true)) {
                $datos['pacientes'] = $this->reportes->listaPacientes();
            }

            switch ($r) {
                case 'pacientes':   $datos['reporte'] = $this->rPacientes($f); break;
                case 'seguimiento': $datos['reporte'] = $this->rSeguimiento($f); break;
                case 'dietas':      $datos['reporte'] = $this->rDietas($f); break;
                case 'progreso':    $datos['reporte'] = $f['paciente'] ? $this->rProgreso($f['paciente']) : null; break;
                case 'evaluacion':
                    if ($f['paciente']) {
                        $datos['evaluaciones'] = $this->evaluaciones->listarPorPaciente($f['paciente']);
                        if ($f['e']) {
                            $datos['reporte'] = $this->rEvaluacion($f['paciente'], $f['e']);
                        }
                    }
                    break;
            }
        } catch (Throwable $e) {
            error_log('Reportes SNWO (' . $r . '): ' . $e->getMessage());
            $datos['error'] = 'No se pudo generar el reporte. Intenta de nuevo.';
            return $datos;
        }

        if (($f['paciente'] ?? 0) && in_array($r, ['evaluacion', 'progreso'], true) && !$this->reportes->paciente($f['paciente'])) {
            $datos['error'] = 'No se encontró el paciente indicado.';
            $datos['reporte'] = null;
        }
        if (($f['e'] ?? 0) && $r === 'evaluacion' && !$datos['reporte'] && $datos['error'] === '') {
            $datos['error'] = 'No se encontró esa evaluación para el paciente.';
        }

        if ($datos['reporte'] !== null) {
            $usados = ['pacientes' => ['estado'], 'seguimiento' => ['dias'], 'dietas' => ['desde', 'hasta'],
                       'progreso' => ['paciente'], 'evaluacion' => ['paciente', 'e']][$r];
            $datos['exportar'] = http_build_query(['r' => $r] + array_intersect_key($f, array_flip($usados)));
            if ($formato !== 'html') {
                $this->entregar($datos['reporte'], $formato, $r);   // termina el script
            }
        }
        return $datos;
    }

    // ---------- Filtros (todos validados) ----------

    private function filtros(string $r): array
    {
        $fecha = function (string $clave, string $porDefecto): string {
            $v = (string)($_GET[$clave] ?? '');
            $d = DateTime::createFromFormat('Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : $porDefecto;
        };
        $desde = $fecha('desde', date('Y-m-d', strtotime('-30 days')));
        $hasta = $fecha('hasta', date('Y-m-d'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $dias = (int)($_GET['dias'] ?? 30);
        return [
            'estado'   => in_array($_GET['estado'] ?? '', ['activos', 'inactivos'], true) ? (string)$_GET['estado'] : 'todos',
            'paciente' => max(0, (int)($_GET['paciente'] ?? 0)),
            'e'        => max(0, (int)($_GET['e'] ?? 0)),
            'dias'     => in_array($dias, [15, 30, 60, 90], true) ? $dias : 30,
            'desde'    => $desde,
            'hasta'    => $hasta,
        ];
    }

    // ---------- Reportes ----------

    private function rPacientes(array $f): array
    {
        $filas = $this->reportes->pacientes($f['estado']);
        $activos = $conCuenta = 0;
        $tabla = [];
        foreach ($filas as $p) {
            $activos   += (int)$p['activo'] === 1 ? 1 : 0;
            $conCuenta += (int)$p['cuentas'] > 0 ? 1 : 0;
            $tabla[] = [
                (string)$p['nombre'], (string)($p['email'] ?? ''), (string)($p['telefono'] ?? ''),
                (string)($p['objetivo'] ?? ''), (string)($p['nivel_actividad'] ?? ''),
                (int)$p['activo'] === 1 ? 'Activo' : 'Inactivo',
                self::fecha($p['ultima_evaluacion'] ?? null, 'Sin evaluación'),
                (int)$p['cuentas'] > 0 ? 'Sí' : 'No',
            ];
        }
        return [
            'titulo'    => 'Reporte de pacientes',
            'subtitulo' => 'Filtro: ' . ['todos' => 'todos los pacientes', 'activos' => 'solo activos', 'inactivos' => 'solo inactivos'][$f['estado']] . ' · Emitido el ' . date('d/m/Y'),
            'resumen'   => [['Pacientes', (string)count($filas)], ['Activos', (string)$activos], ['Inactivos', (string)(count($filas) - $activos)], ['Con cuenta de acceso', (string)$conCuenta]],
            'bloques'   => [['titulo' => '', 'columnas' => ['Nombre', 'Correo', 'Teléfono', 'Objetivo', 'Actividad', 'Estado', 'Última evaluación', 'Cuenta'], 'filas' => $tabla]],
        ];
    }

    private function rSeguimiento(array $f): array
    {
        $hoy = new DateTimeImmutable('today');
        $pendientes = [];
        $nunca = 0;
        $activos = 0;
        foreach ($this->reportes->activosConUltimaEvaluacion() as $p) {
            $activos++;
            $dias = null;
            if (!empty($p['ultima_evaluacion'])) {
                $dias = (int)$hoy->diff(new DateTimeImmutable(date('Y-m-d', strtotime((string)$p['ultima_evaluacion']))))->days;
                if ($dias < $f['dias']) {
                    continue;
                }
            } else {
                $nunca++;
            }
            $pendientes[] = [$p, $dias];
        }
        usort($pendientes, fn($a, $b) => ($b[1] ?? PHP_INT_MAX) <=> ($a[1] ?? PHP_INT_MAX));

        $tabla = [];
        foreach ($pendientes as [$p, $dias]) {
            $tabla[] = [
                (string)$p['nombre'], (string)($p['telefono'] ?? ''), (string)($p['email'] ?? ''),
                self::fecha($p['ultima_evaluacion'] ?? null, 'Nunca evaluado'),
                $dias === null ? '—' : (string)$dias,
            ];
        }
        return [
            'titulo'    => 'Pacientes sin evaluación reciente',
            'subtitulo' => 'Pacientes activos con ' . $f['dias'] . ' días o más sin una nueva evaluación · Emitido el ' . date('d/m/Y'),
            'resumen'   => [['Pacientes activos', (string)$activos], ['Requieren seguimiento', (string)count($tabla)], ['Nunca evaluados', (string)$nunca]],
            'bloques'   => [['titulo' => '', 'columnas' => ['Paciente', 'Teléfono', 'Correo', 'Última evaluación', 'Días sin evaluar'], 'filas' => $tabla]],
        ];
    }

    private function rDietas(array $f): array
    {
        $filas = $this->reportes->dietas($f['desde'], $f['hasta']);
        $porEstado = [];
        $porMes = [];
        $tabla = [];
        foreach ($filas as $d) {
            $estado = (string)$d['estado'];
            $porEstado[$estado] = ($porEstado[$estado] ?? 0) + 1;
            $mes = date('m/Y', strtotime((string)$d['fecha_creacion']));
            $porMes[$mes] = ($porMes[$mes] ?? 0) + 1;
            $tabla[] = [
                (string)$d['nombre'], self::fecha($d['fecha_creacion'], '—'),
                self::fecha($d['fecha_inicio'], '—') . ' al ' . self::fecha($d['fecha_fin'], '—'),
                $estado, number_format((float)$d['get_objetivo'], 0) . ' kcal',
            ];
        }
        $resumen = [['Dietas generadas', (string)count($filas)]];
        foreach ($porEstado as $est => $n) {
            $resumen[] = [$est, (string)$n];
        }
        $bloques = [];
        if ($porMes) {
            $filasMes = [];
            foreach ($porMes as $m => $n) {
                $filasMes[] = [$m, (string)$n];
            }
            $bloques[] = ['titulo' => 'Dietas por mes', 'columnas' => ['Mes', 'Cantidad'], 'filas' => $filasMes];
        }
        $bloques[] = ['titulo' => 'Detalle', 'columnas' => ['Paciente', 'Creada', 'Vigencia', 'Estado', 'GET objetivo'], 'filas' => $tabla];
        return [
            'titulo'    => 'Dietas generadas por período',
            'subtitulo' => 'Del ' . self::fecha($f['desde'], '') . ' al ' . self::fecha($f['hasta'], '') . ' · Emitido el ' . date('d/m/Y'),
            'resumen'   => $resumen,
            'bloques'   => $bloques,
        ];
    }

    private function rProgreso(int $idPaciente): ?array
    {
        $pac = $this->reportes->paciente($idPaciente);
        if (!$pac) {
            return null;
        }
        $hist = $this->progreso->obtenerHistorial($idPaciente);
        $tabla = [];
        $grafica = [];
        $prev = null;
        foreach ($hist as $h) {
            $peso = (float)$h['peso_kg'];
            $tabla[] = [
                self::fecha($h['fecha_registro'], '—'), number_format($peso, 1),
                $h['imc'] !== null ? number_format((float)$h['imc'], 1) : '—',
                $h['porcentaje_grasa'] !== null ? number_format((float)$h['porcentaje_grasa'], 1) . ' %' : '—',
                $prev === null ? '—' : sprintf('%+.1f', $peso - $prev),
            ];
            $grafica[] = [date('d/m', strtotime((string)$h['fecha_registro'])), $peso];
            $prev = $peso;
        }
        $resumen = [['Citas registradas', (string)count($hist)]];
        if ($hist) {
            $ini = (float)$hist[0]['peso_kg'];
            $act = (float)$hist[count($hist) - 1]['peso_kg'];
            $resumen[] = ['Peso inicial', number_format($ini, 1) . ' kg'];
            $resumen[] = ['Peso actual', number_format($act, 1) . ' kg'];
            $resumen[] = ['Cambio total', sprintf('%+.1f kg', $act - $ini)];
        }
        return [
            'titulo'        => 'Progreso — ' . $pac['nombre'],
            'subtitulo'     => 'Objetivo: ' . ($pac['objetivo'] ?? '—') . ' · Emitido el ' . date('d/m/Y'),
            'resumen'       => $resumen,
            'graficaTitulo' => 'Evolución de peso (kg)',
            'grafica'       => $grafica,
            'bloques'       => [['titulo' => 'Detalle por cita', 'columnas' => ['Fecha', 'Peso (kg)', 'IMC', '% Grasa', 'Cambio vs. cita anterior (kg)'], 'filas' => $tabla]],
        ];
    }

    private function rEvaluacion(int $idPaciente, int $idEvaluacion): ?array
    {
        $pac = $this->reportes->paciente($idPaciente);
        $ev  = $this->evaluaciones->obtenerCompleta($idEvaluacion, $idPaciente);
        if (!$pac || !$ev) {
            return null;
        }
        $e = $ev['evaluacion'];
        $num = fn($v, $u = '') => ($v === null || $v === '') ? '—' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.') . $u;

        $bloques = [[
            'titulo'   => 'Medidas y cálculo',
            'columnas' => ['Dato', 'Valor'],
            'filas'    => [
                ['Peso', $num($e['peso_kg'] ?? null, ' kg')],
                ['Talla', $num($e['talla_cm'] ?? null, ' cm')],
                ['IMC', $num($e['imc'] ?? null)],
                ['Pliegue tricipital', $num($e['pliegue_tricipital'] ?? null, ' mm')],
                ['Pliegue subescapular', $num($e['pliegue_subescapular'] ?? null, ' mm')],
                ['Pliegue abdominal', $num($e['pliegue_abdominal'] ?? null, ' mm')],
                ['% de grasa', $num($e['porcentaje_grasa'] ?? null, ' %')],
                ['Masa magra', $num($e['masa_magra_kg'] ?? null, ' kg')],
                ['Fórmula utilizada', (string)($e['formula_utilizada'] ?? '—')],
                ['GET calculado', $num($e['get_calculado'] ?? null, ' kcal/día')],
            ],
        ]];
        foreach ([['Circunferencias', 'circunferencias'], ['Bioimpedancia', 'bioimpedancia'], ['Hábitos y estilo de vida', 'habitos']] as [$titulo, $clave]) {
            $filas = self::paresDeFila($ev[$clave]);
            if ($filas) {
                $bloques[] = ['titulo' => $titulo, 'columnas' => ['Dato', 'Valor'], 'filas' => $filas];
            }
        }
        if (!empty($e['observaciones'])) {
            $bloques[] = ['titulo' => 'Observaciones clínicas', 'columnas' => ['Detalle'], 'filas' => [[(string)$e['observaciones']]]];
        }
        return [
            'titulo'    => 'Evaluación — ' . $pac['nombre'],
            'subtitulo' => 'Cita del ' . self::fecha($e['fecha_evaluacion'] ?? null, '—') . ' · Objetivo: ' . ($pac['objetivo'] ?? '—'),
            'resumen'   => [['Peso', $num($e['peso_kg'] ?? null, ' kg')], ['IMC', $num($e['imc'] ?? null)], ['% de grasa', $num($e['porcentaje_grasa'] ?? null, ' %')], ['GET', $num($e['get_calculado'] ?? null, ' kcal')]],
            'bloques'   => $bloques,
        ];
    }

    // ---------- Utilidades ----------

    private static function fecha($valor, string $vacio): string
    {
        if ($valor === null || $valor === '' || ($t = strtotime((string)$valor)) === false) {
            return $vacio;
        }
        return date('d/m/Y', $t);
    }

    /** Convierte una fila de tabla (columnas variables) en pares [Etiqueta, Valor] legibles, omitiendo ids y vacíos */
    private static function paresDeFila(?array $fila): array
    {
        if (!$fila) {
            return [];
        }
        $unidades = ['_cm' => ' cm', '_kg' => ' kg', '_mg' => ' mg', '_g' => ' g', '_litros' => ' L', '_anios' => ' años', '_horas' => ' h'];
        $pares = [];
        foreach ($fila as $col => $valor) {
            if (!is_string($col) || $col === 'id' || str_starts_with($col, 'id_') || $valor === null || $valor === '' || str_contains($col, 'fecha')) {
                continue;
            }
            $unidad = '';
            $base = $col;
            foreach ($unidades as $sufijo => $u) {
                if (str_ends_with($col, $sufijo)) {
                    $base = substr($col, 0, -strlen($sufijo));
                    $unidad = $u;
                    break;
                }
            }
            $etiqueta = self::ETIQUETAS[$col] ?? ucfirst(str_replace('_', ' ', $base));
            if (str_starts_with($col, 'porcentaje')) {
                $unidad = ' %';
            }
            if (preg_match('/^(consume|fuma|realiza|tiene)/', $col) || is_bool($valor)) {
                $texto = ((string)$valor === '1' || $valor === true) ? 'Sí' : 'No';
            } elseif (is_numeric($valor)) {
                $texto = rtrim(rtrim(number_format((float)$valor, 2, '.', ''), '0'), '.') . $unidad;
            } else {
                $texto = (string)$valor;
            }
            $pares[] = [$etiqueta, $texto];
        }
        return $pares;
    }

    // ---------- Descarga ----------

    private function entregar(array $reporte, string $formato, string $r): void
    {
        $nombre = 'SNWO_Reporte_' . $r . '_' . date('Ymd_His');
        $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);

        try {
            if ($formato === 'pdf') {
                $pdf = ReportePdf::construir($reporte);
                $this->auditoria->registrar($idUsuario, 'REPORTE_PDF:' . $r);
                $pdf->Output($nombre . '.pdf', 'D');
                exit;
            }

            $this->auditoria->registrar($idUsuario, 'REPORTE_CSV:' . $r);
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $nombre . '.csv"');
            echo self::csv($reporte);
            exit;
        } catch (Throwable $e) {
            error_log('Reportes SNWO exportar: ' . $e->getMessage());
            $this->auditoria->registrar($idUsuario, 'REPORTE_' . strtoupper($formato) . ':' . $r, null, null, 'FALLIDO');
            // si falla antes de enviar la descarga, se muestra el reporte en pantalla
        }
    }

    /** CSV para Excel (UTF-8 con BOM, separador ';'). Evita inyección de fórmulas en celdas que empiezan con = + - @ */
    public static function csv(array $reporte): string
    {
        $celda = function ($v): string {
            $v = (string)$v;
            if ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false && !preg_match('/^[+-]?\d+([.,]\d+)?( ?[%a-zA-Z\/]+)?$/', $v)) {
                $v = "'" . $v;
            }
            return '"' . str_replace('"', '""', $v) . '"';
        };
        $lineas = [$celda($reporte['titulo']), $celda($reporte['subtitulo'] ?? '')];
        foreach ($reporte['resumen'] ?? [] as $par) {
            $lineas[] = $celda($par[0]) . ';' . $celda($par[1]);
        }
        foreach ($reporte['bloques'] as $b) {
            $lineas[] = '';
            if (!empty($b['titulo'])) {
                $lineas[] = $celda($b['titulo']);
            }
            $lineas[] = implode(';', array_map($celda, $b['columnas']));
            foreach ($b['filas'] as $fila) {
                $lineas[] = implode(';', array_map($celda, $fila));
            }
        }
        return "\xEF\xBB\xBF" . implode("\r\n", $lineas) . "\r\n";
    }
}
