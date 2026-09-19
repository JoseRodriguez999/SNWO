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

        return [
            'stats'         => $this->dashboard->obtenerEstadisticas(),
            'ultimasDietas' => $this->dashboard->obtenerUltimasDietas(),
        ];
    }
}
