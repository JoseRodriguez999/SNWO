<?php
declare(strict_types=1);

/**
 * CalculadoraNutricional — Lógica de negocio pura (sin base de datos, sin HTML).
 * Fórmulas documentadas en Proyecto_final_APA7_docx.docx + Anexo de ampliación:
 *   - IMC = peso_kg / (talla_m)^2
 *   - TMB Mifflin-St Jeor = (10×peso)+(6.25×talla_cm)-(5×edad)+s [s: +5 hombre, -161 mujer]
 *   - GET = TMB × Factor de actividad física
 *   - % grasa: Jackson & Pollock (1985), 3 pliegues + ecuación de Siri (1961)
 */
class CalculadoraNutricional
{
    public const FACTOR_ACTIVIDAD = [
        'sedentario'  => 1.20,
        'moderado'    => 1.375,
        'activo'      => 1.55,
        'muy activo'  => 1.725,
    ];

    public static function edad(string $fechaNacimiento): int
    {
        return (new DateTime())->diff(new DateTime($fechaNacimiento))->y;
    }

    public static function imc(float $pesoKg, float $tallaCm): float
    {
        $tallaM = $tallaCm / 100;
        return $pesoKg / ($tallaM ** 2);
    }

    public static function tmb(string $formula, float $peso, float $tallaCm, int $edad, string $sexo): float
    {
        switch ($formula) {
            case 'Harris-Benedict':
                return $sexo === 'M'
                    ? 88.362 + (13.397 * $peso) + (4.799 * $tallaCm) - (5.677 * $edad)
                    : 447.593 + (9.247 * $peso) + (3.098 * $tallaCm) - (4.330 * $edad);

            case 'OMS':
                if ($edad >= 18 && $edad <= 30) {
                    return $sexo === 'M' ? (15.3 * $peso) + 679 : (14.7 * $peso) + 496;
                } elseif ($edad > 30 && $edad <= 60) {
                    return $sexo === 'M' ? (11.6 * $peso) + 879 : (8.7 * $peso) + 829;
                } else {
                    return $sexo === 'M' ? (13.5 * $peso) + 487 : (10.5 * $peso) + 596;
                }

            case 'Mifflin-St Jeor':
            default:
                $s = $sexo === 'M' ? 5 : -161;
                return (10 * $peso) + (6.25 * $tallaCm) - (5 * $edad) + $s;
        }
    }

    public static function get(float $tmb, string $nivelActividad): float
    {
        return $tmb * (self::FACTOR_ACTIVIDAD[$nivelActividad] ?? 1.2);
    }

    public static function porcentajeGrasa(float $tricipital, float $subescapular, float $abdominal, int $edad, string $sexo): float
    {
        $sum3 = $tricipital + $subescapular + $abdominal;

        $densidad = $sexo === 'M'
            ? 1.10938 - (0.0008267 * $sum3) + (0.0000016 * ($sum3 ** 2)) - (0.0002574 * $edad)
            : 1.0994921 - (0.0009929 * $sum3) + (0.0000023 * ($sum3 ** 2)) - (0.0001392 * $edad);

        return (495 / $densidad) - 450;
    }

    public static function masaMagra(float $pesoKg, float $porcentajeGrasa): float
    {
        return $pesoKg * (1 - $porcentajeGrasa / 100);
    }
}
