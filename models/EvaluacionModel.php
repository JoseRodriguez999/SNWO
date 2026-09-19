<?php
declare(strict_types=1);

class EvaluacionModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    /**
     * Guarda la evaluación completa (medidas + historial de peso + circunferencias
     * + bioimpedancia + cuestionario de hábitos) en una sola transacción.
     * Las secciones opcionales solo se insertan si traen al menos un dato.
     * Devuelve el id_evaluacion recién creado.
     */
    public function registrarCompleta(int $idPaciente, array $medidas, array $circunferencias, array $bio, array $habitos): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO tbl_evaluaciones
                    (id_paciente, peso_kg, talla_cm, imc, pliegue_tricipital, pliegue_subescapular,
                     pliegue_abdominal, porcentaje_grasa, masa_magra_kg, get_calculado, formula_utilizada, observaciones)
                 OUTPUT INSERTED.id_evaluacion
                 VALUES
                    (:id_paciente, :peso_kg, :talla_cm, :imc, :pliegue_tricipital, :pliegue_subescapular,
                     :pliegue_abdominal, :porcentaje_grasa, :masa_magra_kg, :get_calculado, :formula_utilizada, :observaciones)"
            );
            $stmt->execute(array_merge(['id_paciente' => $idPaciente], $medidas));
            $idEvaluacion = (int)$stmt->fetchColumn();

            $this->db->prepare(
                "INSERT INTO tbl_historial_peso (id_paciente, peso_kg, imc, porcentaje_grasa, circunferencia_cintura_cm)
                 VALUES (:id_paciente, :peso_kg, :imc, :porcentaje_grasa, :cintura)"
            )->execute([
                'id_paciente'      => $idPaciente,
                'peso_kg'          => $medidas['peso_kg'],
                'imc'              => $medidas['imc'],
                'porcentaje_grasa' => $medidas['porcentaje_grasa'],
                'cintura'          => $circunferencias['cintura_cm'] ?? null,
            ]);

            if (array_filter($circunferencias, fn($v) => $v !== null)) {
                $this->db->prepare(
                    "INSERT INTO tbl_circunferencias
                        (id_evaluacion, cuello_cm, brazo_relajado_cm, brazo_contraido_cm, antebrazo_cm, cintura_cm, cadera_cm, muslo_cm, pantorrilla_cm)
                     VALUES
                        (:id_evaluacion, :cuello_cm, :brazo_relajado_cm, :brazo_contraido_cm, :antebrazo_cm, :cintura_cm, :cadera_cm, :muslo_cm, :pantorrilla_cm)"
                )->execute(array_merge(['id_evaluacion' => $idEvaluacion], $circunferencias));
            }

            if (array_filter($bio, fn($v) => $v !== null)) {
                $this->db->prepare(
                    "INSERT INTO tbl_bioimpedancia
                        (id_evaluacion, edad_metabolica, porcentaje_grasa, porcentaje_agua, densidad_osea_kg, grasa_visceral, masa_muscular_kg)
                     VALUES
                        (:id_evaluacion, :edad_metabolica, :porcentaje_grasa, :porcentaje_agua, :densidad_osea_kg, :grasa_visceral, :masa_muscular_kg)"
                )->execute(array_merge(['id_evaluacion' => $idEvaluacion], $bio));
            }

            if (array_filter($habitos, fn($v) => $v !== null)) {
                $this->db->prepare(
                    "INSERT INTO tbl_cuestionario_habitos
                        (id_evaluacion, comidas_por_dia, porciones_frutas_verduras, consumo_agua_litros,
                         consumo_comida_rapida, consume_alcohol, frecuencia_alcohol, fuma, realiza_ejercicio,
                         frecuencia_ejercicio_semana, tipo_ejercicio, horas_sueno, nivel_estres,
                         alergias_alimentarias, observaciones)
                     VALUES
                        (:id_evaluacion, :comidas_por_dia, :porciones_frutas_verduras, :consumo_agua_litros,
                         :consumo_comida_rapida, :consume_alcohol, :frecuencia_alcohol, :fuma, :realiza_ejercicio,
                         :frecuencia_ejercicio_semana, :tipo_ejercicio, :horas_sueno, :nivel_estres,
                         :alergias_alimentarias, :observaciones)"
                )->execute(array_merge(['id_evaluacion' => $idEvaluacion], $habitos));
            }

            $this->db->commit();
            return $idEvaluacion;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Trae la evaluación junto con sus datos relacionados (o null si no existe / no es de ese paciente) */
    public function obtenerCompleta(int $idEvaluacion, int $idPaciente): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tbl_evaluaciones WHERE id_evaluacion = :id AND id_paciente = :idp");
        $stmt->execute(['id' => $idEvaluacion, 'idp' => $idPaciente]);
        $evaluacion = $stmt->fetch();

        if (!$evaluacion) {
            return null;
        }

        $stmtC = $this->db->prepare("SELECT * FROM tbl_circunferencias WHERE id_evaluacion = :id");
        $stmtC->execute(['id' => $idEvaluacion]);

        $stmtB = $this->db->prepare("SELECT * FROM tbl_bioimpedancia WHERE id_evaluacion = :id");
        $stmtB->execute(['id' => $idEvaluacion]);

        $stmtH = $this->db->prepare("SELECT * FROM tbl_cuestionario_habitos WHERE id_evaluacion = :id");
        $stmtH->execute(['id' => $idEvaluacion]);

        return [
            'evaluacion'      => $evaluacion,
            'circunferencias' => $stmtC->fetch() ?: null,
            'bioimpedancia'   => $stmtB->fetch() ?: null,
            'habitos'         => $stmtH->fetch() ?: null,
        ];
    }
}
