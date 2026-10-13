<?php
declare(strict_types=1);

/**
 * AuditoriaModel — Registro de eventos críticos del sistema en tbl_auditoria
 * (RNF 4.4.2 del documento). Un fallo al auditar nunca debe tumbar la operación
 * principal, por eso registrar() atrapa sus propios errores y solo los escribe al log.
 */
class AuditoriaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    public function registrar(?int $idUsuario, string $accion, ?string $tabla = null, ?int $idRegistro = null, string $resultado = 'EXITOSO'): void
    {
        try {
            $this->db->prepare(
                "INSERT INTO tbl_auditoria (id_usuario, accion, tabla_afectada, id_registro_afectado, ip_origen, resultado)
                 VALUES (:usuario, :accion, :tabla, :registro, :ip, :resultado)"
            )->execute([
                'usuario'   => $idUsuario,
                'accion'    => $accion,
                'tabla'     => $tabla,
                'registro'  => $idRegistro,
                'ip'        => isset($_SERVER['REMOTE_ADDR']) ? substr((string)$_SERVER['REMOTE_ADDR'], 0, 45) : null,
                'resultado' => in_array($resultado, ['EXITOSO', 'FALLIDO'], true) ? $resultado : 'EXITOSO',
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo registrar auditoría SNWO: ' . $e->getMessage());
        }
    }
}
