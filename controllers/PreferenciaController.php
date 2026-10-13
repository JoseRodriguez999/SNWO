<?php
declare(strict_types=1);

class PreferenciaController
{
    private PacienteModel $pacientes;
    private PreferenciaModel $preferencias;

    public function __construct()
    {
        $this->pacientes    = new PacienteModel();
        $this->preferencias = new PreferenciaModel();
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'marcar_preferencia') {
            if (Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
                $idAlimento = (int)($_POST['id_alimento'] ?? 0);
                $tipo       = (string)($_POST['tipo'] ?? '');

                if ($idAlimento > 0 && $tipo === 'neutral') {
                    $this->preferencias->eliminarPreferencia($idPaciente, $idAlimento);
                } elseif ($idAlimento > 0 && in_array($tipo, ['me_gusta', 'no_me_gusta'], true)) {
                    $this->preferencias->guardarPreferencia($idPaciente, $idAlimento, $tipo);
                }
            }
            header('Location: preferencias.php?id=' . $idPaciente);
            exit;
        }

        return [
            'paciente'  => $paciente,
            'idPaciente' => $idPaciente,
            'catalogo'  => $this->preferencias->obtenerCatalogoConPreferencias($idPaciente),
            'csrf'      => Sesion::csrfToken(),
        ];
    }
}
