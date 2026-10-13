<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

/**
 * PlanDieteticoPdf — Genera el documento PDF del plan dietético semanal (CU-06),
 * usando TCPDF como indica el documento del proyecto (Librería: TCPDF / PHP).
 *
 * Recibe los datos YA calculados (DietaModel::obtenerDietaActual) y solo se encarga
 * de dibujarlos; no consulta la base de datos (capa de presentación documental).
 */
class PlanDieteticoPdf extends TCPDF
{
    private const VERDE = [14, 110, 82];

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('SNWO - Smart Nutrition Web Optimizer');
        $this->SetAuthor('Clínica Smart Nutrition');
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        $this->SetMargins(15, 26, 15);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(12);
        $this->SetAutoPageBreak(true, 20);
        $this->SetFont('helvetica', '', 10);
    }

    // ---------- Encabezado y pie repetidos en cada página ----------

    public function Header()
    {
        $this->SetFillColor(...self::VERDE);
        $this->Rect(0, 0, $this->getPageWidth(), 18, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(15, 5);
        $this->Cell(110, 8, 'CLÍNICA SMART NUTRITION', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 8, 'Plan dietético personalizado', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer()
    {
        $this->SetY(-14);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(130, 8, 'Generado el ' . date('d/m/Y H:i') . '  ·  SNWO - Smart Nutrition Web Optimizer', 0, 0, 'L');
        $this->Cell(0, 8, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    // ---------- Construcción del documento ----------

    /**
     * @param array  $paciente      ['nombre' => ...]
     * @param array  $dieta         resultado de DietaModel::obtenerDietaActual()
     * @param string $nutricionista nombre del profesional que emite el plan
     * @param array  $dias          [1 => 'Lunes', ...]
     * @param array  $tiempos       ['Desayuno', 'Almuerzo', 'Cena', 'Colacion']
     */
    public static function construir(array $paciente, array $dieta, string $nutricionista, array $dias, array $tiempos): self
    {
        $pdf = new self();
        $pdf->SetTitle('Plan dietético — ' . $paciente['nombre']);
        $pdf->SetSubject('Plan dietético semanal');
        $pdf->AddPage();

        $pdf->writeHTML(self::htmlResumen($paciente, $dieta, $nutricionista), true, false, true, false, '');

        foreach ($dias as $numDia => $etiquetaDia) {
            $pdf->writeHTML(self::htmlDia($etiquetaDia, $dieta['menu'][$numDia] ?? [], $tiempos, $dieta['get']), true, false, true, false, '');
        }

        $pdf->writeHTML(self::htmlNotas($nutricionista), true, false, true, false, '');

        return $pdf;
    }

    // ---------- Bloques HTML ----------

    private static function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    private static function etiquetaTiempo(string $tiempo): string
    {
        return $tiempo === 'Colacion' ? 'Colación' : $tiempo;
    }

    private static function fecha(string $fechaSql): string
    {
        return date('d/m/Y', strtotime($fechaSql));
    }

    private static function htmlResumen(array $paciente, array $dieta, string $nutricionista): string
    {
        $get = (float)$dieta['get'];
        $pctProt = $get > 0 ? round($dieta['metaProteina'] * 4 / $get * 100) : 0;
        $pctCh   = $get > 0 ? round($dieta['metaCarbohidratos'] * 4 / $get * 100) : 0;
        $pctGr   = $get > 0 ? round($dieta['metaGrasa'] * 9 / $get * 100) : 0;

        $reparto = ['Desayuno' => 25, 'Almuerzo' => 35, 'Cena' => 25, 'Colacion' => 15];
        $filasReparto = '';
        foreach ($reparto as $tiempo => $pct) {
            $filasReparto .= '<tr>'
                . '<td width="40%">' . self::etiquetaTiempo($tiempo) . '</td>'
                . '<td width="30%" align="right">' . $pct . ' %</td>'
                . '<td width="30%" align="right">' . number_format($get * $pct / 100, 0) . ' kcal</td>'
                . '</tr>';
        }

        $html  = '<h2 style="font-size:16pt;color:#0e6e52;">Plan dietético semanal</h2>';
        $html .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">'
            . '<tr><td width="28%"><b>Paciente:</b></td><td width="72%">' . self::e($paciente['nombre']) . '</td></tr>'
            . '<tr><td><b>Nutricionista:</b></td><td>' . self::e($nutricionista) . '</td></tr>'
            . '<tr><td><b>Vigencia del plan:</b></td><td>' . self::fecha($dieta['fechaInicio']) . ' al ' . self::fecha($dieta['fechaFin']) . '</td></tr>'
            . '<tr><td><b>Estado:</b></td><td>' . self::e($dieta['estado']) . '</td></tr>'
            . '<tr><td><b>GET objetivo:</b></td><td><b>' . number_format($get, 0) . ' kcal/día</b></td></tr>'
            . '</table>';

        $html .= '<br/><table cellpadding="5" cellspacing="0" border="0" width="100%">'
            . '<tr bgcolor="#e7f5ef"><td colspan="3"><b>Metas diarias de macronutrientes</b></td></tr>'
            . '<tr><td width="40%">Proteínas</td><td width="30%" align="right">' . number_format($dieta['metaProteina'], 0) . ' g</td><td width="30%" align="right">' . $pctProt . ' % del GET</td></tr>'
            . '<tr><td>Carbohidratos</td><td align="right">' . number_format($dieta['metaCarbohidratos'], 0) . ' g</td><td align="right">' . $pctCh . ' % del GET</td></tr>'
            . '<tr><td>Grasas</td><td align="right">' . number_format($dieta['metaGrasa'], 0) . ' g</td><td align="right">' . $pctGr . ' % del GET</td></tr>'
            . '</table>';

        $html .= '<br/><table cellpadding="5" cellspacing="0" border="0" width="100%">'
            . '<tr bgcolor="#e7f5ef"><td colspan="3"><b>Distribución calórica por tiempo de comida</b></td></tr>'
            . $filasReparto
            . '</table><br/>';

        return $html;
    }

    private static function htmlDia(string $etiquetaDia, array $menuDia, array $tiempos, float $get): string
    {
        $filas = '';
        $totalDia = 0.0;

        foreach ($tiempos as $tiempo) {
            $celda = $menuDia[$tiempo] ?? null;
            $etiqueta = self::etiquetaTiempo($tiempo);

            if (!$celda || empty($celda['alimentos'])) {
                $filas .= '<tr><td width="22%"><b>' . $etiqueta . '</b></td><td width="46%" colspan="3">—</td></tr>';
                continue;
            }

            $primera = true;
            foreach ($celda['alimentos'] as $a) {
                $filas .= '<tr>'
                    . '<td width="22%">' . ($primera ? '<b>' . $etiqueta . '</b>' : '') . '</td>'
                    . '<td width="46%">' . self::e($a['nombre']) . '</td>'
                    . '<td width="16%" align="right">' . number_format((float)$a['porcion_ajustada_g'], 0) . ' g</td>'
                    . '<td width="16%" align="right">' . number_format((float)$a['calorias_aportadas'], 0) . ' kcal</td>'
                    . '</tr>';
                $primera = false;
            }
            $filas .= '<tr bgcolor="#f3f4f6">'
                . '<td colspan="3" align="right"><i>Total ' . $etiqueta . '</i></td>'
                . '<td width="16%" align="right"><b>' . number_format((float)$celda['kcal_total'], 0) . ' kcal</b></td>'
                . '</tr>';
            $totalDia += (float)$celda['kcal_total'];
        }

        return '<table nobr="true" cellpadding="3" cellspacing="0" border="0" width="100%">'
            . '<tr bgcolor="#0e6e52"><td colspan="4"><font color="#ffffff"><b>' . self::e($etiquetaDia) . '</b></font></td></tr>'
            . '<tr bgcolor="#e7f5ef"><td width="22%"><b>Tiempo</b></td><td width="46%"><b>Alimento</b></td>'
            . '<td width="16%" align="right"><b>Porción</b></td><td width="16%" align="right"><b>Calorías</b></td></tr>'
            . $filas
            . '<tr bgcolor="#e7f5ef"><td colspan="3" align="right"><b>Total del día</b></td>'
            . '<td width="16%" align="right"><b>' . number_format($totalDia, 0) . ' kcal</b></td></tr>'
            . '</table><br/>';
    }

    private static function htmlNotas(string $nutricionista): string
    {
        return '<table nobr="true" cellpadding="5" cellspacing="0" border="0" width="100%">'
            . '<tr bgcolor="#e7f5ef"><td><b>Indicaciones</b></td></tr>'
            . '<tr><td>'
            . '• Las porciones están expresadas en gramos de alimento y corresponden a las calorías indicadas para cada tiempo de comida.<br/>'
            . '• Si no cuenta con algún alimento, solicite a su nutricionista un sustituto equivalente en calorías y de la misma categoría.<br/>'
            . '• Este plan se calculó a partir de su última evaluación antropométrica y debe actualizarse cuando cambien sus medidas.'
            . '</td></tr>'
            . '</table><br/><br/><br/>'
            . '<table nobr="true" cellpadding="2" cellspacing="0" border="0" width="100%"><tr>'
            . '<td width="50%" align="center">______________________________<br/>' . self::e($nutricionista) . '<br/>Nutricionista</td>'
            . '<td width="50%" align="center">______________________________<br/>Paciente</td>'
            . '</tr></table>';
    }
}
