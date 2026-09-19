<?php
declare(strict_types=1);

class PersonalizarController
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
            'pacienteNombre'        => $_GET['paciente'] ?? 'María López',
            'alimentosActuales'     => $this->dietas->obtenerAlimentosActuales(),
            'catalogoAlternativas'  => $this->dietas->obtenerCatalogoAlternativas(),
        ];
    }
}
