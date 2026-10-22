<?php
declare(strict_types=1);

class GeneradorController
{
    private PacienteModel $pacientes;
    private DietaModel $dietas;
    private AuditoriaModel $auditoria;

    public function __construct()
    {
        $this->auditoria = new AuditoriaModel();
        $this->pacientes = new PacienteModel();
        $this->dietas    = new DietaModel();
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

        $error = '';
        $exito = '';
        if (!empty($_SESSION['flash_exito'])) {
            $exito = (string)$_SESSION['flash_exito'];
            unset($_SESSION['flash_exito']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_estado') {
            Sesion::requiereNutricionista();
            $nuevo = (string)($_POST['estado'] ?? '');
            $idDieta = (int)($_POST['id_dieta'] ?? 0);
            if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } else {
                // Solo se puede cambiar el estado de una dieta de ESTE paciente
                $actual = $this->dietas->obtenerDietaActual($idPaciente);
                if (!$actual || (int)$actual['idDieta'] !== $idDieta) {
                    $error = 'La dieta indicada no corresponde a este paciente o ya hay una versión más reciente.';
                } else {
                    try {
                        $this->dietas->cambiarEstado($idDieta, $nuevo);
                        $this->auditoria->registrar((int)$_SESSION['id_usuario'], $nuevo === 'Activa' ? 'ACTIVAR_DIETA' : 'FINALIZAR_DIETA', 'tbl_dietas', $idDieta);
                        $_SESSION['flash_exito'] = $nuevo === 'Activa'
                            ? 'Plan activado: el paciente ya puede verlo en “Mi plan”.'
                            : 'Plan finalizado: el paciente ya no lo verá en “Mi plan”.';
                        header('Location: generador.php?id=' . $idPaciente);
                        exit;
                    } catch (RuntimeException $e) {
                        $error = $e->getMessage();
                    } catch (PDOException $e) {
                        error_log('Error al cambiar estado de dieta SNWO: ' . $e->getMessage());
                        $error = 'No se pudo cambiar el estado. Intenta de nuevo.';
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generar_dieta') {
            if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } else {
                try {
                    $generado = $this->dietas->generarMenuSemanal($idPaciente);
                    $idNueva = $this->dietas->guardarDieta($idPaciente, $generado);
                    $this->auditoria->registrar((int)$_SESSION['id_usuario'], 'GENERAR_DIETA', 'tbl_dietas', $idNueva);
                    $a = $generado['ajuste'];
                    $msg = 'Dieta generada como Borrador (' . number_format($generado['get'], 0) . ' kcal/día';
                    if ($a['pct'] != 0.0) {
                        $msg .= ', ' . ($a['pct'] < 0 ? '' : '+') . round($a['pct'] * 100) . '% por objetivo de '
                              . ($a['objetivo'] === 'perdida' ? 'pérdida de peso' : 'ganancia muscular');
                    }
                    $msg .= '). Revísala y actívala para que el paciente la vea.';
                    if ($a['piso']) $msg .= ' Se aplicó el mínimo de seguridad de calorías.';
                    if ($generado['alergenos'] !== '') $msg .= ' Alimentos excluidos por alergia/intolerancia: ' . $generado['alergenos'] . '.';
                    if ($generado['advertencia']) $msg .= ' ' . $generado['advertencia'];
                    $_SESSION['flash_exito'] = $msg;
                    header('Location: generador.php?id=' . $idPaciente);
                    exit;
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                } catch (PDOException $e) {
                    error_log('Error al generar dieta SNWO: ' . $e->getMessage());
                    $error = 'Ocurrió un error al guardar la dieta. Intenta de nuevo.';
                }
            }
        }

        $dieta = $this->dietas->obtenerDietaActual($idPaciente);
        $alergenos = $this->dietas->obtenerAlergenos($idPaciente);
        $hayActiva = $dieta && $dieta['estado'] === 'Activa'
            ? true : (bool)$this->dietas->obtenerDietaActual($idPaciente, true);

        return [
            'paciente'   => $paciente,
            'idPaciente' => $idPaciente,
            'dieta'      => $dieta,
            'hayActiva'  => $hayActiva,
            'alergenosTexto'  => Alergenos::describir($alergenos),
            'alergenosConflicto' => $dieta ? Alergenos::conflictos($dieta['menu'], $alergenos) : [],
            'exito'      => $exito,
            'dias'       => DietaModel::nombresDias(),
            'tiempos'    => DietaModel::tiemposComida(),
            'error'      => $error,
            'csrf'       => Sesion::csrfToken(),
        ];
    }
}
