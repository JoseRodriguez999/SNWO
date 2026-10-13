<?php
declare(strict_types=1);

class HistorialController
{
    private PacienteModel $pacientes;
    private EvaluacionModel $evaluaciones;

    public function __construct()
    {
        $this->pacientes    = new PacienteModel();
        $this->evaluaciones = new EvaluacionModel();
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

        // Más reciente primero. Cada fila se compara contra la cita anterior (la siguiente del arreglo).
        $lista = $this->evaluaciones->listarPorPaciente($idPaciente);
        $n = count($lista);

        foreach ($lista as $i => &$fila) {
            $ant = $lista[$i + 1] ?? null;
            $fila['d_peso']    = $ant ? (float)$fila['peso_kg'] - (float)$ant['peso_kg'] : null;
            $fila['d_imc']     = $ant ? (float)$fila['imc'] - (float)$ant['imc'] : null;
            $fila['d_grasa']   = ($ant && $fila['porcentaje_grasa'] !== null && $ant['porcentaje_grasa'] !== null)
                                    ? (float)$fila['porcentaje_grasa'] - (float)$ant['porcentaje_grasa'] : null;
            $fila['d_cintura'] = ($ant && $fila['cintura_cm'] !== null && $ant['cintura_cm'] !== null)
                                    ? (float)$fila['cintura_cm'] - (float)$ant['cintura_cm'] : null;
        }
        unset($fila);

        $primera = $n ? $lista[$n - 1] : null;
        $ultima  = $n ? $lista[0] : null;

        return [
            'paciente'    => $paciente,
            'idPaciente'  => $idPaciente,
            'evaluaciones' => $lista,
            'total'       => $n,
            'primera'     => $primera,
            'ultima'      => $ultima,
            'cambioTotal' => ($primera && $ultima && $n > 1) ? (float)$ultima['peso_kg'] - (float)$primera['peso_kg'] : null,
        ];
    }
}
