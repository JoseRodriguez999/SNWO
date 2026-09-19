<?php
declare(strict_types=1);

class GeneradorController
{
    private DietaModel $dietas;

    public function __construct()
    {
        $this->dietas = new DietaModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        return [
            'pacienteNombre' => $_GET['paciente'] ?? 'María López',
            'menu'           => $this->dietas->obtenerMenuSemanal(),
            'dias'           => $this->dietas->obtenerDias(),
            'distribucion'   => $this->dietas->obtenerDistribucionCalorica(),
        ];
    }
}
