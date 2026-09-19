<?php
declare(strict_types=1);

/**
 * DietaModel — [PENDIENTE] Contiene datos de ejemplo. El algoritmo real
 * de generación (distribución de macronutrientes según el GET del
 * paciente, filtrado por patología, persistencia en tbl_dietas/tbl_menu/
 * tbl_menu_alimento) todavía no está implementado — es la siguiente
 * gran funcionalidad a construir, no un problema de paradigma.
 */
class DietaModel
{
    public function obtenerMenuSemanal(): array
    {
        return [
            'Desayuno' => ['Avena, plátano, huevo', 'Granola, yogur', 'Tostadas, frijoles', 'Avena, manzana', 'Huevo, pan integral'],
            'Almuerzo' => ['Arroz, frijoles, pollo', 'Sopa, tortilla', 'Frijoles, arroz', 'Pollo, ensalada', 'Arroz, verduras'],
            'Cena'     => ['Sopa, pan integral', 'Crema, tortilla', 'Ensalada, queso', 'Sopa, pan', 'Arroz, pollo'],
            'Colación' => ['Manzana, nueces', 'Yogur', 'Fruta', 'Nueces', 'Yogur, fruta'],
        ];
    }

    public function obtenerDias(): array
    {
        return ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
    }

    public function obtenerDistribucionCalorica(): array
    {
        return [
            ['label' => 'Desayuno', 'pct' => 25],
            ['label' => 'Almuerzo', 'pct' => 35],
            ['label' => 'Cena',     'pct' => 25],
            ['label' => 'Colación', 'pct' => 15],
        ];
    }

    public function obtenerAlimentosActuales(): array
    {
        return [
            ['nombre' => 'Frijol negro cocido', 'detalle' => '120 g — 156 kcal'],
            ['nombre' => 'Arroz blanco cocido', 'detalle' => '150 g — 195 kcal', 'editando' => true],
            ['nombre' => 'Pollo a la plancha',  'detalle' => '120 g — 186 kcal'],
            ['nombre' => 'Tortilla de maíz',    'detalle' => '2 unidades — 140 kcal'],
        ];
    }

    public function obtenerCatalogoAlternativas(): array
    {
        return [
            ['nombre' => 'Pasta integral cocida', 'detalle' => '180 g — 198 kcal', 'q' => 'pasta integral'],
            ['nombre' => 'Espagueti de trigo',    'detalle' => '160 g — 210 kcal', 'q' => 'pasta integral espagueti'],
            ['nombre' => 'Papa cocida',           'detalle' => '150 g — 130 kcal', 'q' => 'papa'],
            ['nombre' => 'Camote cocido',         'detalle' => '150 g — 129 kcal', 'q' => 'camote'],
            ['nombre' => 'Quinoa cocida',         'detalle' => '150 g — 180 kcal', 'q' => 'quinoa'],
        ];
    }
}
