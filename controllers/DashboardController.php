<?php
declare(strict_types=1);

class DashboardController
{
    private DashboardModel $dashboard;

    public function __construct()
    {
        $this->dashboard = new DashboardModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        try {
            $cambios = Sesion::esPersonal() ? (new CambioPacienteModel())->recientes(8) : [];
        } catch (Throwable $e) {
            $cambios = [];
        }

        return [
            'cambios'       => $cambios,
            'stats'         => $this->dashboard->obtenerEstadisticas(),
            'ultimasDietas' => $this->dashboard->obtenerUltimasDietas(),
        ];
    }
}
