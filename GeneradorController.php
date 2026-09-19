<?php
declare(strict_types=1);

class GeneradorController
{
    private PacienteModel $pacientes;
    private DietaModel $dietas;

    public function __construct()
    {
        $this->pacientes = new PacienteModel();
        $this->dietas    = new DietaModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $idPaciente = (int)($_GET['id'] ?? 0);
        $paciente   = $this->pacientes->buscarPorId($idPaciente);

        if (!$paciente) {
            return ['paciente' => null];
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'generar_dieta') {
            if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            } else {
                try {
                    $generado = $this->dietas->generarMenuSemanal($idPaciente);
                    $this->dietas->guardarDieta($idPaciente, $generado);
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

        return [
            'paciente'   => $paciente,
            'idPaciente' => $idPaciente,
            'dieta'      => $this->dietas->obtenerDietaActual($idPaciente),
            'dias'       => DietaModel::nombresDias(),
            'tiempos'    => DietaModel::tiemposComida(),
            'error'      => $error,
            'csrf'       => Sesion::csrfToken(),
        ];
    }
}
