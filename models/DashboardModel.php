<?php
declare(strict_types=1);

/**
 * DashboardModel — Estadísticas reales del sistema (pacientes, dietas, PDFs).
 */
class DashboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    private function contar(string $sql): int
    {
        try {
            return (int)$this->db->query($sql)->fetchColumn();
        } catch (PDOException $e) {
            error_log('Dashboard SNWO: ' . $e->getMessage());
            return 0;
        }
    }

    public function obtenerEstadisticas(): array
    {
        $pacientes = $this->contar("SELECT COUNT(*) FROM tbl_pacientes WHERE activo = 1");
        $dietasSem = $this->contar("SELECT COUNT(*) FROM tbl_dietas WHERE fecha_creacion >= DATEADD(day, -7, GETDATE())");
        $cuentas   = $this->contar("SELECT COUNT(*) FROM tbl_usuarios WHERE rol = 'Paciente' AND activo = 1");
        $pdfs      = $this->contar("SELECT COUNT(*) FROM tbl_auditoria WHERE accion = 'EXPORTAR_PDF'");

        return [
            ['label' => 'Pacientes activos',      'value' => (string)$pacientes, 'destacado' => true],
            ['label' => 'Dietas esta semana',     'value' => (string)$dietasSem, 'destacado' => false],
            ['label' => 'Pacientes con cuenta',   'value' => (string)$cuentas,   'destacado' => true],
            ['label' => 'PDFs generados',         'value' => (string)$pdfs,      'destacado' => false],
        ];
    }

    public function obtenerUltimasDietas(): array
    {
        $colores = ['Activa' => 'green', 'Borrador' => 'amber', 'Finalizada' => 'gray'];
        try {
            $filas = $this->db->query(
                "SELECT TOP 5 p.nombre, d.estado, d.get_objetivo, d.fecha_inicio, d.fecha_fin
                 FROM tbl_dietas d
                 INNER JOIN tbl_pacientes p ON p.id_paciente = d.id_paciente
                 ORDER BY d.fecha_creacion DESC"
            )->fetchAll();
        } catch (PDOException $e) {
            error_log('Dashboard SNWO: ' . $e->getMessage());
            return [];
        }

        $resultado = [];
        foreach ($filas as $f) {
            $ini = $f['fecha_inicio'] ? date('d/m', strtotime((string)$f['fecha_inicio'])) : '';
            $fin = $f['fecha_fin'] ? date('d/m', strtotime((string)$f['fecha_fin'])) : '';
            $estado = (string)$f['estado'];
            $resultado[] = [
                'nombre'  => (string)$f['nombre'],
                'detalle' => 'Plan ' . $ini . ' – ' . $fin . ' · ' . number_format((float)$f['get_objetivo'], 0) . ' kcal/día',
                'estado'  => $estado,
                'color'   => $colores[$estado] ?? 'gray',
            ];
        }
        return $resultado;
    }
}
