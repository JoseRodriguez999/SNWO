<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

/**
 * ReportePdf — Dibuja cualquier reporte genérico (título, resumen, gráfica opcional y bloques de tabla)
 * con TCPDF. No consulta la base de datos: recibe el reporte ya armado por ReporteController.
 */
class ReportePdf extends TCPDF
{
    private const VERDE = [14, 110, 82];
    private string $tituloReporte = '';

    public function __construct(string $orientacion)
    {
        parent::__construct($orientacion, 'mm', 'A4', true, 'UTF-8', false);
        $this->SetCreator('SNWO - Smart Nutrition Web Optimizer');
        $this->SetAuthor('Clínica Smart Nutrition');
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);
        $this->SetMargins(15, 26, 15);
        $this->SetHeaderMargin(0);
        $this->SetFooterMargin(12);
        $this->SetAutoPageBreak(true, 20);
        $this->SetFont('helvetica', '', 9);
    }

    public function Header()
    {
        $this->SetFillColor(...self::VERDE);
        $this->Rect(0, 0, $this->getPageWidth(), 18, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(15, 5);
        $this->Cell(110, 8, 'CLÍNICA SMART NUTRITION', 0, 0, 'L');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 8, $this->tituloReporte, 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer()
    {
        $this->SetY(-14);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $ancho = $this->getPageWidth() - 30;
        $this->Cell($ancho - 35, 8, 'Generado el ' . date('d/m/Y H:i') . '  ·  SNWO - Smart Nutrition Web Optimizer', 0, 0, 'L');
        $this->Cell(0, 8, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }

    private static function e(string $t): string
    {
        return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    }

    /** @param array $r reporte: titulo, subtitulo, resumen[[etiqueta,valor]], grafica[[etiqueta,valor]], bloques[[titulo,columnas,filas]] */
    public static function construir(array $r): self
    {
        $anchoTabla = 0;
        foreach ($r['bloques'] as $b) {
            $anchoTabla = max($anchoTabla, count($b['columnas']));
        }
        $pdf = new self($anchoTabla > 5 ? 'L' : 'P');
        $pdf->tituloReporte = (string)$r['titulo'];
        $pdf->SetTitle((string)$r['titulo']);
        $pdf->AddPage();

        $html = '<h2 style="font-size:15pt;color:#0e6e52;">' . self::e((string)$r['titulo']) . '</h2>';
        if (!empty($r['subtitulo'])) {
            $html .= '<p style="color:#555555;">' . self::e((string)$r['subtitulo']) . '</p>';
        }
        if (!empty($r['resumen'])) {
            $html .= '<table cellpadding="4" cellspacing="0" border="0" width="100%"><tr>';
            $n = count($r['resumen']);
            foreach ($r['resumen'] as $par) {
                $html .= '<td width="' . floor(100 / $n) . '%" bgcolor="#e7f5ef" align="center">'
                    . '<span style="font-size:8pt;color:#555555;">' . self::e((string)$par[0]) . '</span><br/>'
                    . '<b style="font-size:12pt;">' . self::e((string)$par[1]) . '</b></td>';
            }
            $html .= '</tr></table><br/>';
        }
        $pdf->writeHTML($html, true, false, true, false, '');

        if (!empty($r['grafica']) && count($r['grafica']) >= 2) {
            $pdf->graficaLinea($r['grafica'], (string)($r['graficaTitulo'] ?? ''));
        }

        foreach ($r['bloques'] as $b) {
            $pdf->writeHTML(self::htmlBloque($b), true, false, true, false, '');
        }
        return $pdf;
    }

    private static function htmlBloque(array $b): string
    {
        $h = '';
        if (!empty($b['titulo'])) {
            $h .= '<h3 style="font-size:11pt;color:#0e6e52;">' . self::e((string)$b['titulo']) . '</h3>';
        }
        if (!$b['filas']) {
            return $h . '<p style="color:#777777;">Sin registros para este reporte.</p><br/>';
        }
        $h .= '<table cellpadding="4" cellspacing="0" border="0.3" width="100%"><thead><tr bgcolor="#0e6e52">';
        foreach ($b['columnas'] as $c) {
            $h .= '<th style="color:#ffffff;"><b>' . self::e((string)$c) . '</b></th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($b['filas'] as $i => $fila) {
            $h .= '<tr' . ($i % 2 ? ' bgcolor="#f5f5f5"' : '') . '>';
            foreach ($fila as $celda) {
                $h .= '<td>' . self::e((string)$celda) . '</td>';
            }
            $h .= '</tr>';
        }
        return $h . '</tbody></table><br/>';
    }

    /** Gráfica de línea simple (etiqueta, valor) dibujada con primitivas de TCPDF */
    private function graficaLinea(array $puntos, string $titulo): void
    {
        $x0 = $this->GetX() + 12;
        $ancho = $this->getPageWidth() - 30 - 14;
        $alto = 55;
        $y0 = $this->GetY() + 8;
        if ($y0 + $alto + 14 > $this->getPageHeight() - 20) {
            $this->AddPage();
            $y0 = $this->GetY() + 8;
        }
        $valores = array_map(fn($p) => (float)$p[1], $puntos);
        $min = min($valores);
        $max = max($valores);
        if ($max - $min < 1.0) {
            $min -= 0.5;
            $max += 0.5;
        }
        $pad = ($max - $min) * 0.1;
        $min -= $pad;
        $max += $pad;

        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(...self::VERDE);
        $this->SetXY(15, $y0 - 7);
        $this->Cell(0, 6, $titulo, 0, 0, 'L');
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->SetDrawColor(220, 220, 220);
        for ($i = 0; $i <= 4; $i++) {
            $y = $y0 + $alto - $alto * $i / 4;
            $this->Line($x0, $y, $x0 + $ancho, $y);
            $this->SetXY(15, $y - 2);
            $this->Cell(11, 4, number_format($min + ($max - $min) * $i / 4, 1), 0, 0, 'R');
        }
        $n = count($puntos);
        $this->SetDrawColor(...self::VERDE);
        $this->SetLineWidth(0.6);
        $prev = null;
        foreach ($puntos as $i => $p) {
            $x = $x0 + $ancho * $i / ($n - 1);
            $y = $y0 + $alto - $alto * (((float)$p[1] - $min) / ($max - $min));
            if ($prev) {
                $this->Line($prev[0], $prev[1], $x, $y);
            }
            $this->SetFillColor(...self::VERDE);
            $this->Circle($x, $y, 0.9, 0, 360, 'F');
            if ($n <= 12 || $i % (int)ceil($n / 10) === 0) {
                $this->SetXY($x - 8, $y0 + $alto + 1);
                $this->Cell(16, 4, (string)$p[0], 0, 0, 'C');
            }
            $prev = [$x, $y];
        }
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('helvetica', '', 9);
        $this->SetY($y0 + $alto + 9);
    }
}
