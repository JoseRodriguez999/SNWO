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

        // Selección explícita del paciente activo (queda anclado para todas las pestañas)
        if (isset($_GET['seleccionar'])) {
            $idSel = (int)$_GET['seleccionar'];
            $sel   = $this->pacientes->buscarPorId($idSel);
            if ($sel) {
                Sesion::anclarPaciente($idSel, (string)$sel['nombre']);
            }
            header('Location: pacientes.php');
            exit;
        }
        if (isset($_GET['soltar'])) {
            Sesion::soltarPaciente();
            header('Location: pacientes.php');
            exit;
        }

        $error = '';
        $exito = '';
        $errorEdicion = '';
        $oldEdit = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_paciente') {
            $errorEdicion = $this->editar($oldEdit);
            if ($errorEdicion === '') {
                $_SESSION['flash_exito'] = 'Paciente actualizado correctamente.';
                header('Location: pacientes.php');
                exit;
            }
        }

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
            'errorEdicion' => $errorEdicion,
            'oldEdit'      => $oldEdit,
            'csrf'      => Sesion::csrfToken(),
            'pacientes' => $this->listarParaVista(),
            'pacienteActivoId' => (int)(Sesion::pacienteActivo()['id'] ?? 0),
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

        if (($err = $this->validarDatos($datos)) !== '') {
            return $err;
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

    /** Validaciones comunes de alta y edición. Devuelve '' si todo está bien. */
    private function validarDatos(array $datos): string
    {
        if ($datos['nombre'] === '' || $datos['fecha_nacimiento'] === '' || $datos['sexo'] === ''
            || $datos['email'] === '' || $datos['objetivo'] === '' || $datos['nivel_actividad'] === '') {
            return 'Completa todos los campos obligatorios.';
        }
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return 'El correo no tiene un formato válido.';
        }
        $ts = strtotime($datos['fecha_nacimiento']);
        if (!$ts || $ts > time() || $ts < strtotime('-120 years')) {
            return 'La fecha de nacimiento no es válida.';
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
        return '';
    }

    /** @return string Vacío si se actualizó bien; mensaje de error en caso contrario */
    private function editar(array &$old): string
    {
        $id = (int)($_POST['id_paciente'] ?? 0);
        $old = [
            'id'               => $id,
            'nombre'           => trim((string)($_POST['nombre'] ?? '')),
            'fecha_nacimiento' => trim((string)($_POST['fecha_nacimiento'] ?? '')),
            'sexo'             => trim((string)($_POST['sexo'] ?? '')),
            'telefono'         => trim((string)($_POST['telefono'] ?? '')),
            'email'            => trim((string)($_POST['email'] ?? '')),
            'objetivo'         => trim((string)($_POST['objetivo'] ?? '')),
            'nivel_actividad'  => trim((string)($_POST['nivel_actividad'] ?? '')),
        ];
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            return 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
        }
        if ($id <= 0 || !$this->pacientes->buscarPorId($id)) {
            return 'El paciente ya no existe.';
        }
        $datos = [
            'nombre'           => $old['nombre'],
            'fecha_nacimiento' => $old['fecha_nacimiento'],
            'sexo'             => $old['sexo'],
            'telefono'         => $old['telefono'] !== '' ? $old['telefono'] : null,
            'email'            => $old['email'],
            'objetivo'         => $old['objetivo'],
            'nivel_actividad'  => $old['nivel_actividad'],
        ];
        if (($err = $this->validarDatos($datos)) !== '') {
            return $err;
        }
        if ($this->pacientes->existeEmailOtro($datos['email'], $id)) {
            return 'Ya existe otro paciente registrado con ese correo.';
        }
        try {
            $this->pacientes->actualizar($id, $datos);
            (new AuditoriaModel())->registrar((int)($_SESSION['id_usuario'] ?? 0) ?: null, 'PACIENTE_EDITADO', 'tbl_pacientes', $id);
            // Si es el paciente anclado en la sesión, se refresca su nombre en la barra
            if ((int)(Sesion::pacienteActivo()['id'] ?? 0) === $id) {
                Sesion::anclarPaciente($id, $datos['nombre']);
            }
            return '';
        } catch (PDOException $e) {
            error_log('Error al editar paciente SNWO: ' . $e->getMessage());
            return 'Ocurrió un error al guardar los cambios. Intenta de nuevo.';
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
                'fecha_nacimiento' => date('Y-m-d', strtotime((string)(is_object($f['fecha_nacimiento']) ? $f['fecha_nacimiento']->format('Y-m-d') : $f['fecha_nacimiento']))),
                'telefono'   => (string)($f['telefono'] ?? ''),
                'objetivo_cod'    => $f['objetivo'],
                'nivel_actividad' => $f['nivel_actividad'],
                'evaluacion' => $f['ultima_evaluacion'] ? (new DateTime($f['ultima_evaluacion']))->format('d/m/Y') : 'Sin evaluación',
            ];
        }

        return $pacientes;
    }
}
