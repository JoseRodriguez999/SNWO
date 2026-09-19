<?php
declare(strict_types=1);

class AlimentoController
{
    private AlimentoModel $alimentos;

    public function __construct()
    {
        $this->alimentos = new AlimentoModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        return [
            'alimentos' => $this->alimentos->listarConCategoria(),
            'total'     => $this->alimentos->contarTotal(),
        ];
    }
}
