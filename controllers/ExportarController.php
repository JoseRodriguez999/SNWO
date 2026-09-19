<?php
declare(strict_types=1);

class ExportarController
{
    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        return [
            'pacienteNombre' => $_GET['paciente'] ?? 'María López',
        ];
    }
}
