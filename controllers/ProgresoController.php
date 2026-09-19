<?php
declare(strict_types=1);

class ProgresoController
{
    private PacienteModel $pacientes;
    private ProgresoModel $progreso;

    public function __construct()
    {
        $this->pacientes = new PacienteModel();
        $this->progreso  = new ProgresoModel();
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

        $historial = $this->progreso->obtenerHistorial($idPaciente);

        $pesoInicial = $historial ? (float)$historial[0]['peso_kg'] : null;
        $pesoActual  = $historial ? (float)$historial[count($historial) - 1]['peso_kg'] : null;
        $imcActual   = $historial ? (float)$historial[count($historial) - 1]['imc'] : null;

        return [
            'paciente'    => $paciente,
            'idPaciente'  => $idPaciente,
            'historial'   => $historial,
            'pesoInicial' => $pesoInicial,
            'pesoActual'  => $pesoActual,
            'reduccion'   => ($pesoInicial !== null && $pesoActual !== null) ? ($pesoActual - $pesoInicial) : null,
            'imcActual'   => $imcActual,
        ];
    }
}
