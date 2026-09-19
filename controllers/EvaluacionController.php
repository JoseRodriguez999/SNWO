<?php
declare(strict_types=1);

class EvaluacionController
{
    private PacienteModel $pacientes;
    private EvaluacionModel $evaluaciones;

    private const CAMPOS_CIRCUNFERENCIA = [
        'cuello_cm', 'brazo_relajado_cm', 'brazo_contraido_cm', 'antebrazo_cm',
        'cintura_cm', 'cadera_cm', 'muslo_cm', 'pantorrilla_cm',
    ];

    public function __construct()
    {
        $this->pacientes    = new PacienteModel();
        $this->evaluaciones = new EvaluacionModel();
    }

    public function manejar(): array
    {
        Sesion::iniciar();
        Sesion::requiereLogin();

        $idPaciente = (int)($_GET['id'] ?? 0);
        $paciente   = $this->pacientes->buscarPorId($idPaciente);

        if (!$paciente) {
            return ['paciente' => null];
        }

        $edad = CalculadoraNutricional::edad($paciente['fecha_nacimiento']);
        $error = '';
        $datosCompletos = null;

        if (isset($_GET['resultado'])) {
            $datosCompletos = $this->evaluaciones->obtenerCompleta((int)$_GET['resultado'], $idPaciente);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'registrar_evaluacion') {
            $idNueva = $this->registrar($idPaciente, $paciente, $edad, $error);
            if ($idNueva !== null) {
                header('Location: evaluacion.php?id=' . $idPaciente . '&resultado=' . $idNueva);
                exit;
            }
        }

        return [
            'paciente'          => $paciente,
            'edad'              => $edad,
            'idPaciente'        => $idPaciente,
            'error'             => $error,
            'csrf'              => Sesion::csrfToken(),
            'datosCompletos'    => $datosCompletos,
            'factoresActividad' => CalculadoraNutricional::FACTOR_ACTIVIDAD,
        ];
    }

    private function registrar(int $idPaciente, array $paciente, int $edad, string &$error): ?int
    {
        if (!Sesion::validarCsrf($_POST['csrf_token'] ?? null)) {
            $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            return null;
        }

        $peso         = (float)($_POST['peso_kg'] ?? 0);
        $talla        = (float)($_POST['talla_cm'] ?? 0);
        $tricipital   = (float)($_POST['pliegue_tricipital'] ?? 0);
        $subescapular = (float)($_POST['pliegue_subescapular'] ?? 0);
        $abdominal    = (float)($_POST['pliegue_abdominal'] ?? 0);
        $formula      = (string)($_POST['formula_utilizada'] ?? 'Mifflin-St Jeor');
        $nivelAct     = (string)($_POST['nivel_actividad'] ?? $paciente['nivel_actividad']);
        $observaciones = trim((string)($_POST['observaciones'] ?? ''));

        if ($peso < 20 || $peso > 300) {
            $error = 'El peso debe estar entre 20 y 300 kg.'; return null;
        }
        if ($talla < 100 || $talla > 250) {
            $error = 'La talla debe estar entre 100 y 250 cm.'; return null;
        }
        if ($tricipital <= 0 || $subescapular <= 0 || $abdominal <= 0 || $tricipital > 60 || $subescapular > 60 || $abdominal > 60) {
            $error = 'Los pliegues cutáneos deben estar entre 1 y 60 mm.'; return null;
        }
        if (!in_array($formula, ['Harris-Benedict', 'Mifflin-St Jeor', 'OMS'], true)) {
            $error = 'Fórmula inválida.'; return null;
        }
        if (!array_key_exists($nivelAct, CalculadoraNutricional::FACTOR_ACTIVIDAD)) {
            $error = 'Nivel de actividad inválido.'; return null;
        }

        $imc       = CalculadoraNutricional::imc($peso, $talla);
        $pctGrasa  = CalculadoraNutricional::porcentajeGrasa($tricipital, $subescapular, $abdominal, $edad, $paciente['sexo']);
        $masaMagra = CalculadoraNutricional::masaMagra($peso, $pctGrasa);
        $tmb       = CalculadoraNutricional::tmb($formula, $peso, $talla, $edad, $paciente['sexo']);
        $get       = CalculadoraNutricional::get($tmb, $nivelAct);

        $medidas = [
            'peso_kg' => $peso, 'talla_cm' => $talla, 'imc' => $imc,
            'pliegue_tricipital' => $tricipital, 'pliegue_subescapular' => $subescapular, 'pliegue_abdominal' => $abdominal,
            'porcentaje_grasa' => $pctGrasa, 'masa_magra_kg' => $masaMagra, 'get_calculado' => $get,
            'formula_utilizada' => $formula, 'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        $circunferencias = [];
        foreach (self::CAMPOS_CIRCUNFERENCIA as $campo) {
            $circunferencias[$campo] = $this->floatONull($campo);
        }

        $bio = [
            'edad_metabolica'   => $this->intONull('edad_metabolica'),
            'porcentaje_grasa'  => $this->floatONull('bio_porcentaje_grasa'),
            'porcentaje_agua'   => $this->floatONull('bio_porcentaje_agua'),
            'densidad_osea_kg'  => $this->floatONull('densidad_osea_kg'),
            'grasa_visceral'    => $this->intONull('grasa_visceral'),
            'masa_muscular_kg'  => $this->floatONull('masa_muscular_kg'),
        ];

        $habitos = [
            'comidas_por_dia'             => $this->intONull('comidas_por_dia'),
            'porciones_frutas_verduras'   => $this->intONull('porciones_frutas_verduras'),
            'consumo_agua_litros'         => $this->floatONull('consumo_agua_litros'),
            'consumo_comida_rapida'       => $this->strONull('consumo_comida_rapida'),
            'consume_alcohol'             => $this->boolONull('consume_alcohol'),
            'frecuencia_alcohol'          => $this->strONull('frecuencia_alcohol'),
            'fuma'                        => $this->boolONull('fuma'),
            'realiza_ejercicio'           => $this->boolONull('realiza_ejercicio'),
            'frecuencia_ejercicio_semana' => $this->intONull('frecuencia_ejercicio_semana'),
            'tipo_ejercicio'              => $this->strONull('tipo_ejercicio'),
            'horas_sueno'                 => $this->floatONull('horas_sueno'),
            'nivel_estres'                => $this->strONull('nivel_estres'),
            'alergias_alimentarias'       => $this->strONull('alergias_alimentarias'),
            'observaciones'               => $this->strONull('observaciones_habitos'),
        ];

        try {
            return $this->evaluaciones->registrarCompleta($idPaciente, $medidas, $circunferencias, $bio, $habitos);
        } catch (PDOException $e) {
            error_log('Error al registrar evaluación SNWO: ' . $e->getMessage());
            $error = 'Ocurrió un error al guardar la evaluación. Intenta de nuevo.';
            return null;
        }
    }

    private function floatONull(string $campo): ?float
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        return $v === '' ? null : (float)$v;
    }

    private function intONull(string $campo): ?int
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        return $v === '' ? null : (int)$v;
    }

    private function strONull(string $campo): ?string
    {
        $v = trim((string)($_POST[$campo] ?? ''));
        return $v === '' ? null : $v;
    }

    private function boolONull(string $campo): ?int
    {
        if (!isset($_POST[$campo])) return null;
        return ($_POST[$campo] === '1' || $_POST[$campo] === 'on') ? 1 : 0;
    }
}
