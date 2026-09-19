<?php
declare(strict_types=1);

class PacienteController
{
    private PacienteModel $pacientes;

    private const OBJETIVO_LABELS = [
        'perdida'       => ['texto' => 'Pérdida de peso',  'color' => 'green'],
        'mantenimiento' => ['texto' => 'Mantenimiento',     'color' => 'blue'],
        'ganancia'      => ['texto' => 'Ganancia muscular', 'color' => 'amber'],
    ];

    public function __construct()
    {
        $this->pacientes = new PacienteModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $error = '';
        $exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_paciente') {
            $error = $this->crear();
            if ($error === '') {
                $_SESSION['flash_exito'] = 'Paciente registrado correctamente.';
                header('Location: pacientes.php');
                exit;
            }
        }

        if (!empty($_SESSION['flash_exito'])) {
            $exito = $_SESSION['flash_exito'];
            unset($_SESSION['flash_exito']);
        }

        return [
            'error'     => $error,
            'exito'     => $exito,
            'csrf'      => Sesion::csrfToken(),
            'pacientes' => $this->listarParaVista(),
        ];
    }

    /** @return string Vacío si se creó bien; mensaje de error en caso contrario */
    private function crear(): string
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }

        $datos = [
            'nombre'           => trim((string)($_POST['nombre'] ?? '')),
            'fecha_nacimiento' => trim((string)($_POST['fecha_nacimiento'] ?? '')),
            'sexo'             => trim((string)($_POST['sexo'] ?? '')),
            'telefono'         => trim((string)($_POST['telefono'] ?? '')) ?: null,
            'email'            => trim((string)($_POST['email'] ?? '')),
            'objetivo'         => trim((string)($_POST['objetivo'] ?? '')),
            'nivel_actividad'  => trim((string)($_POST['nivel_actividad'] ?? '')),
        ];

        if ($datos['nombre'] === '' || $datos['fecha_nacimiento'] === '' || $datos['sexo'] === ''
            || $datos['email'] === '' || $datos['objetivo'] === '' || $datos['nivel_actividad'] === '') {
            return 'Completa todos los campos obligatorios.';
        }
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo no tiene un formato válido.';
        }
        if (!in_array($datos['sexo'], ['M', 'F'], true)) {
            return 'Sexo inválido.';
        }
        if (!array_key_exists($datos['objetivo'], self::OBJETIVO_LABELS)) {
            return 'Objetivo inválido.';
        }
        if (!array_key_exists($datos['nivel_actividad'], CalculadoraNutricional::FACTOR_ACTIVIDAD)) {
            return 'Nivel de actividad inválido.';
        }
        if ($this->pacientes->existeEmail($datos['email'])) {
            return 'Ya existe un paciente registrado con ese correo.';
        }

        try {
            $this->pacientes->crear($datos);
            return '';
        } catch (PDOException $e) {
            error_log('Error al crear paciente SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar el paciente. Intenta de nuevo.';
        }
    }

    private function listarParaVista(): array
    {
        $filas = $this->pacientes->listarActivosConUltimaEvaluacion();
        $pacientes = [];

        foreach ($filas as $f) {
            $edad = CalculadoraNutricional::edad($f['fecha_nacimiento']);
            $obj  = self::OBJETIVO_LABELS[$f['objetivo']] ?? ['texto' => $f['objetivo'], 'color' => 'gray'];

            $partes    = preg_split('/\s+/', trim($f['nombre']));
            $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[count($partes) - 1] ?? '', 0, 1));

            $pacientes[] = [
                'id'         => (int)$f['id_paciente'],
                'iniciales'  => $iniciales,
                'nombre'     => $f['nombre'],
                'correo'     => $f['email'],
                'edad'       => $edad,
                'sexo'       => $f['sexo'],
                'objetivo'   => $obj['texto'],
                'color'      => $obj['color'],
                'evaluacion' => $f['ultima_evaluacion'] ? (new DateTime($f['ultima_evaluacion']))->format('d/m/Y') : 'Sin evaluación',
            ];
        }

        return $pacientes;
    }
}
