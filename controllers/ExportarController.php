<?php
declare(strict_types=1);

class ExportarController
{
    private PacienteModel $pacientes;
    private DietaModel $dietas;
    private AuditoriaModel $auditoria;

    public function __construct()
    {
        $this->pacientes = new PacienteModel();
        $this->dietas    = new DietaModel();
        $this->auditoria = new AuditoriaModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $idPaciente = Sesion::resolverPacienteId();
        $paciente   = $this->pacientes->buscarPorId($idPaciente);
        Sesion::sincronizarPaciente($idPaciente, $paciente);

        if (!$paciente) {
            return ['paciente' => null];
        }

        $dieta = $this->dietas->obtenerDietaActual($idPaciente);
        $error = '';

        // Descarga / vista del último PDF generado (sale del script con exit si todo va bien)
        if (isset($_GET['descargar']) || isset($_GET['ver'])) {
            $error = $this->entregarArchivo($idPaciente, isset($_GET['ver']));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generar_pdf') {
            if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } elseif (!$dieta) {
                $error = 'Este paciente todavía no tiene un plan generado. Genera la dieta primero.';
            } else {
                try {
                    $this->generarPdf($idPaciente, $paciente, $dieta);
                    header('Location: exportar.php?id=' . $idPaciente);
                    exit;
                } catch (Throwable $e) {
                    error_log('Error al generar PDF SNWO: ' . $e->getMessage());
                    $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'EXPORTAR_PDF', 'tbl_dietas', $dieta['idDieta'], 'FALLIDO');
                    $error = 'No se pudo generar el PDF. Verifica que la carpeta storage/pdfs tenga permisos de escritura.';
                }
            }
        }

        $generado = $_SESSION['pdf_generado'][$idPaciente] ?? null;
        if ($generado && !is_file($generado['ruta'])) {
            $generado = null;
        }

        return [
            'paciente'       => $paciente,
            'idPaciente'     => $idPaciente,
            'dieta'          => $dieta,
            'nutricionista'  => (string)($_SESSION['nombre'] ?? 'Nutricionista'),
            'generado'       => $generado,
            'error'          => $error,
            'csrf'           => Sesion::csrfToken(),
        ];
    }

    private function generarPdf(int $idPaciente, array $paciente, array $dieta): void
    {
        $inicio = microtime(true);

        $pdf = PlanDieteticoPdf::construir(
            $paciente,
            $dieta,
            (string)($_SESSION['nombre'] ?? 'Nutricionista'),
            DietaModel::nombresDias(),
            DietaModel::tiemposComida()
        );

        $directorio = __DIR__ . '/../storage/pdfs';
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear la carpeta ' . $directorio);
        }

        $nombreArchivo = 'Plan_Dietetico_' . $this->slug($paciente['nombre']) . '_' . date('Ymd_His') . '.pdf';
        $ruta = $directorio . '/' . $nombreArchivo;
        $pdf->Output($ruta, 'F');

        $_SESSION['pdf_generado'][$idPaciente] = [
            'ruta'   => $ruta,
            'nombre' => $nombreArchivo,
            'tiempo' => round(microtime(true) - $inicio, 2),
            'fecha'  => date('d/m/Y H:i'),
        ];

        $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'EXPORTAR_PDF', 'tbl_dietas', $dieta['idDieta']);
    }

    /** @return string mensaje de error (solo si no hay archivo para entregar); si hay, envía el PDF y termina */
    private function entregarArchivo(int $idPaciente, bool $enNavegador): string
    {
        $info = $_SESSION['pdf_generado'][$idPaciente] ?? null;
        if (!$info || !is_file($info['ruta'])) {
            return 'Todavía no has generado el PDF de este paciente. Dale clic a "Generar PDF" primero.';
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($enNavegador ? 'inline' : 'attachment') . '; filename="' . $info['nombre'] . '"');
        header('Content-Length: ' . filesize($info['ruta']));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($info['ruta']);
        exit;
    }

    /** Nombre de archivo seguro: sin tildes, espacios ni caracteres raros */
    private function slug(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        return trim(preg_replace('/[^A-Za-z0-9]+/', '_', $texto), '_');
    }
}
