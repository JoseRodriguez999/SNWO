<?php
declare(strict_types=1);

/**
 * DashboardModel — [PENDIENTE] Los datos siguen siendo de ejemplo porque
 * el motor de generación de dietas (DietaModel real, con el algoritmo de
 * distribución de macronutrientes) todavía no está construido. Cuando
 * exista, este modelo consultará tbl_dietas y tbl_pacientes directamente.
 */
class DashboardModel
{
    public function obtenerEstadisticas(): array
    {
        return [
            ['label' => 'Pacientes activos',  'value' => '24',     'destacado' => true],
            ['label' => 'Dietas esta semana', 'value' => '8',      'destacado' => false],
            ['label' => 'Tiempo promedio',    'value' => '17 min', 'destacado' => true],
            ['label' => 'PDFs generados',     'value' => '12',     'destacado' => false],
        ];
    }

    public function obtenerUltimasDietas(): array
    {
        return [
            ['nombre' => 'María López',    'detalle' => 'Plan Oct–Nov · 1,842 kcal/día', 'estado' => 'Activa',     'color' => 'green'],
            ['nombre' => 'Carlos Mendoza', 'detalle' => 'Plan Oct–Nov · 2,107 kcal/día', 'estado' => 'Borrador',   'color' => 'amber'],
            ['nombre' => 'Ana Pérez',      'detalle' => 'Plan Sep–Oct · 1,650 kcal/día', 'estado' => 'Finalizada','color' => 'gray'],
        ];
    }
}
