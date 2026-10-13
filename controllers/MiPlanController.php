<?php
declare(strict_types=1);

/**
 * MiPlanController — Única pantalla del rol Paciente: ver su plan, cambiar alimentos,
 * marcar lo que le gusta y descargar su PDF.
 *
 * Seguridad: el paciente se toma SIEMPRE de la sesión ($_SESSION['id_paciente'], fijado al
 * iniciar sesión desde tbl_usuarios.id_paciente). Nunca se lee un id de la URL ni del formulario,
 * así que no puede ver ni modificar el plan de otra persona.
 */
class MiPlanController
{
    private PacienteModel $pacientes;
    private DietaModel $dietas;
    private AlimentoModel $alimentos;
    private PreferenciaModel $preferencias;
    private CambioPacienteModel $cambios;
    private AuditoriaModel $auditoria;

    public function __construct()
    {
        $this->pacientes    = new PacienteModel();
        $this->dietas       = new DietaModel();
        $this->alimentos    = new AlimentoModel();
        $this->preferencias = new PreferenciaModel();
        $this->cambios      = new CambioPacienteModel();
        $this->auditoria    = new AuditoriaModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        // Esta pantalla es solo para pacientes; el personal usa el resto del sistema
        if (!Sesion::esPaciente()) {
            header('Location: dashboard.php');
            exit;
        }

        $idPaciente = (int)($_SESSION['id_paciente'] ?? 0);
        if ($idPaciente <= 0) {
            return ['estado' => 'sin_vinculo'];
        }
        $paciente = $this->pacientes->buscarPorId($idPaciente);
        if (!$paciente) {
            return ['estado' => 'no_disponible'];
        }

        $error = '';
        $exito = '';
        $accion = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST['accion'] ?? '') : '';

        if ($accion === 'sustituir_alimento') {
            $error = $this->sustituir($idPaciente);
            if ($error === '') {
                $_SESSION['flash_exito'] = 'Listo, cambiaste el alimento en tu plan.';
                header('Location: mi_plan.php');
                exit;
            }
        } elseif ($accion === 'marcar_preferencia') {
            $this->marcarPreferencia($idPaciente);
            header('Location: mi_plan.php?vista=gustos');
            exit;
        }

        if (isset($_GET['pdf'])) {
            $error = $this->entregarPdf($idPaciente, $paciente);
        }

        if (!empty($_SESSION['flash_exito'])) {
            $exito = (string)$_SESSION['flash_exito'];
            unset($_SESSION['flash_exito']);
        }

        $dieta = $this->dietas->obtenerDietaActual($idPaciente);
        $vista = (($_GET['vista'] ?? '') === 'gustos') ? 'gustos' : 'plan';

        // Modo búsqueda de sustituto
        $editandoMenu = (int)($_GET['editar_menu'] ?? 0);
        $editandoAlimento = (int)($_GET['editar_alimento'] ?? 0);
        $tiempoSlot = null;
        $resultados = [];
        $termino = trim((string)($_GET['buscar'] ?? ''));

        if ($vista === 'plan' && $dieta && $editandoMenu > 0) {
            $menu = $this->dietas->obtenerMenuPorId($editandoMenu);
            if ($menu && (int)$menu['id_dieta'] === (int)$dieta['idDieta']) {
                $tiempoSlot = (string)$menu['tiempo_comida'];
                $resultados = $this->alimentos->buscarSustitutos(
                    $termino,
                    DietaModel::CATEGORIAS_POR_TIEMPO[$tiempoSlot] ?? [],
                    $tiempoSlot === 'Desayuno',
                    $this->dietas->obtenerRestriccionesCombinadas($idPaciente),
                    $idPaciente
                );
            } else {
                $editandoMenu = 0;
            }
        }

        return [
            'estado'     => 'ok',
            'paciente'   => $paciente,
            'dieta'      => $dieta,
            'dias'       => DietaModel::nombresDias(),
            'tiempos'    => DietaModel::tiemposComida(),
            'vista'      => $vista,
            'error'      => $error,
            'exito'      => $exito,
            'csrf'       => Sesion::csrfToken(),
            'editandoMenu'     => $editandoMenu,
            'editandoAlimento' => $editandoAlimento,
            'tiempoSlot'       => $tiempoSlot,
            'resultados'       => $resultados,
            'termino'          => $termino,
            'catalogo'   => $vista === 'gustos' ? $this->preferencias->obtenerCatalogoConPreferencias($idPaciente) : [],
        ];
    }

    /** @return string '' si se cambió; mensaje de error si no */
    private function sustituir(int $idPaciente): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }
        $idMenu  = (int)($_POST['id_menu'] ?? 0);
        $viejo   = (int)($_POST['id_alimento_viejo'] ?? 0);
        $nuevo   = (int)($_POST['id_alimento_nuevo'] ?? 0);

        // 1) El tiempo de comida debe ser de SU plan actual
        $dieta = $this->dietas->obtenerDietaActual($idPaciente);
        $menu  = $this->dietas->obtenerMenuPorId($idMenu);
        if (!$dieta || !$menu || (int)$menu['id_dieta'] !== (int)$dieta['idDieta']) {
            $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'PACIENTE_CAMBIO_DENEGADO', 'tbl_menu', $idMenu ?: null, 'FALLIDO');
            return 'No puedes modificar ese tiempo de comida.';
        }
        if ($nuevo <= 0 || $viejo <= 0 || $nuevo === $viejo) {
            return 'Selecciona un alimento distinto para el cambio.';
        }

        // 2) El sustituto debe ser permitido para él (categoría del tiempo, restricciones, no "no me gusta")
        $tiempo = (string)$menu['tiempo_comida'];
        $valido = $this->alimentos->buscarSustitutos(
            '', DietaModel::CATEGORIAS_POR_TIEMPO[$tiempo] ?? [], $tiempo === 'Desayuno',
            $this->dietas->obtenerRestriccionesCombinadas($idPaciente), $idPaciente, $nuevo
        );
        if (empty($valido)) {
            $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'PACIENTE_CAMBIO_DENEGADO', 'tbl_menu', $idMenu, 'FALLIDO');
            return 'Ese alimento no está permitido para este tiempo de comida.';
        }

        try {
            $this->dietas->sustituirAlimento($idMenu, $viejo, $nuevo);
            $this->cambios->registrar($idPaciente, (int)$dieta['idDieta'], (int)$menu['dia_semana'], $tiempo, $viejo, $nuevo);
            $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'PACIENTE_CAMBIO_ALIMENTO', 'tbl_menu', $idMenu);
            return '';
        } catch (RuntimeException $e) {
            return $e->getMessage();
        } catch (Throwable $e) {
            error_log('Error al sustituir alimento (paciente) SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar el cambio. Intenta de nuevo.';
        }
    }

    private function marcarPreferencia(int $idPaciente): void
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return;
        }
        $idAlimento = (int)($_POST['id_alimento'] ?? 0);
        $tipo       = (string)($_POST['tipo'] ?? '');
        if ($idAlimento <= 0 || !$this->alimentos->obtenerPorId($idAlimento)) {
            return;
        }
        if ($tipo === 'neutral') {
            $this->preferencias->eliminarPreferencia($idPaciente, $idAlimento);
        } elseif (in_array($tipo, ['me_gusta', 'no_me_gusta'], true)) {
            $this->preferencias->guardarPreferencia($idPaciente, $idAlimento, $tipo);
        } else {
            return;
        }
        $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'PACIENTE_PREFERENCIA', 'tbl_preferencias_alimento', $idAlimento);
    }

    /** Envía el PDF del plan al navegador (sin guardarlo en el servidor). Devuelve un error solo si no se puede. */
    private function entregarPdf(int $idPaciente, array $paciente): string
    {
        $dieta = $this->dietas->obtenerDietaActual($idPaciente);
        if (!$dieta) {
            return 'Todavía no tienes un plan. Tu nutricionista lo generará pronto.';
        }
        try {
            $pdf = PlanDieteticoPdf::construir(
                $paciente, $dieta, 'Equipo de nutrición SNWO', DietaModel::nombresDias(), DietaModel::tiemposComida()
            );
            $nombre = 'Mi_Plan_' . $this->slug((string)$paciente['nombre']) . '_' . date('Ymd') . '.pdf';
            $contenido = $pdf->Output($nombre, 'S');
            $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'EXPORTAR_PDF', 'tbl_dietas', (int)$dieta['idDieta']);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nombre . '"');
            header('Content-Length: ' . strlen($contenido));
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $contenido;
            exit;
        } catch (Throwable $e) {
            error_log('Error al generar PDF (paciente) SNWO: ' . $e->getMessage());
            return 'No se pudo generar el PDF. Intenta de nuevo.';
        }
    }

    private function slug(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        return trim(preg_replace('/[^A-Za-z0-9]+/', '_', $texto), '_');
    }
}
