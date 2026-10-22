<?php
declare(strict_types=1);

/**
 * Alergenos — Interpreta el texto libre de "alergias/intolerancias" del cuestionario y decide
 * si un alimento del catálogo debe excluirse. Criterio conservador: ante la duda, se excluye.
 *
 * - Grupos conocidos (lácteos, gluten, huevo, mariscos, pescado, frutos secos, soya, sésamo)
 *   se detectan por palabras clave en el nombre del alimento (sin acentos, por palabra completa).
 * - Cualquier otro término escrito por el nutricionista (ej. "fresa") excluye los alimentos
 *   cuyo nombre contenga esa palabra.
 * Es una ayuda, no sustituye la valoración del nutricionista.
 */
class Alergenos
{
    public const GRUPOS = [
        'lacteos' => [
            'etiqueta' => 'Lácteos / lactosa',
            'entrada'  => ['lactosa', 'leche', 'lacteo', 'lacteos', 'caseina', 'queso', 'yogur', 'yogurt'],
            'alimentos' => ['leche', 'queso', 'yogur', 'crema', 'requeson', 'mantequilla', 'helado', 'nata', 'mosh'],
            'excepciones' => ['mantequilla de mani'],
            'categorias' => ['lacteos'],
        ],
        'gluten' => [
            'etiqueta' => 'Gluten / trigo',
            'entrada'  => ['gluten', 'trigo', 'celiaco', 'celiaca', 'celiaquia'],
            'alimentos' => ['trigo', 'pan', 'pasta', 'espagueti', 'galletas', 'galleta', 'avena', 'cebada', 'cereal', 'semita', 'champurradas', 'concha', 'mosh', 'atol shuco'],
            'excepciones' => [],
            'categorias' => [],
        ],
        'huevo' => [
            'etiqueta' => 'Huevo',
            'entrada'  => ['huevo', 'huevos'],
            'alimentos' => ['huevo', 'mayonesa'],
            'excepciones' => [], 'categorias' => [],
        ],
        'mariscos' => [
            'etiqueta' => 'Mariscos',
            'entrada'  => ['marisco', 'mariscos', 'camaron', 'camarones', 'crustaceo', 'crustaceos'],
            'alimentos' => ['camaron', 'marisco', 'langosta', 'cangrejo', 'jaiba'],
            'excepciones' => [], 'categorias' => [],
        ],
        'pescado' => [
            'etiqueta' => 'Pescado',
            'entrada'  => ['pescado', 'pescados', 'atun', 'sardina', 'tilapia'],
            'alimentos' => ['pescado', 'tilapia', 'atun', 'sardina'],
            'excepciones' => [], 'categorias' => [],
        ],
        'frutos_secos' => [
            'etiqueta' => 'Frutos secos / maní',
            'entrada'  => ['nuez', 'nueces', 'mani', 'cacahuate', 'cacahuates', 'almendra', 'almendras', 'frutos secos', 'fruto seco'],
            'alimentos' => ['nuez', 'nueces', 'mani', 'almendra', 'pistacho', 'marañon', 'maranon'],
            'excepciones' => [], 'categorias' => [],
        ],
        'soya' => [
            'etiqueta' => 'Soya',
            'entrada'  => ['soya', 'soja'],
            'alimentos' => ['soya', 'soja', 'edamame', 'tofu'],
            'excepciones' => [], 'categorias' => [],
        ],
        'sesamo' => [
            'etiqueta' => 'Sésamo / ajonjolí',
            'entrada'  => ['sesamo', 'ajonjoli'],
            'alimentos' => ['sesamo', 'ajonjoli', 'hummus'],
            'excepciones' => [], 'categorias' => [],
        ],
    ];

    private const VACIO = '/^(ninguna?|ninguno|no|n\/?a|nada|no tiene.*|sin .*|no refiere.*|niega.*)$/';
    private const RELLENO = '/^(alergia|alergias|alergico|alergica|intolerancia|intolerante|a|al|la|las|el|los|lo|de|del|con|y|e)$/';

    public static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        return preg_replace('/\s+/', ' ', $t);
    }

    /** @return array{grupos:string[], terminos:string[]} */
    public static function interpretar(?string $texto): array
    {
        $res = ['grupos' => [], 'terminos' => []];
        if ($texto === null) return $res;
        $norm = self::normalizar($texto);
        if ($norm === '' || preg_match(self::VACIO, $norm)) return $res;

        $segmentos = preg_split('/[,;\/\n\r]+|\s+y\s+|\s+e\s+/', $norm) ?: [];
        foreach ($segmentos as $seg) {
            $seg = trim($seg);
            if ($seg === '' || preg_match(self::VACIO, $seg)) continue;

            $hallado = false;
            foreach (self::GRUPOS as $clave => $g) {
                foreach ($g['entrada'] as $palabra) {
                    if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/u', $seg)) {
                        $res['grupos'][$clave] = $clave;
                        $hallado = true;
                    }
                }
            }
            if ($hallado) continue;

            // Término libre: quita palabras de relleno y deja el resto como palabra a excluir
            $palabras = array_filter(explode(' ', $seg), fn($p) => $p !== '' && !preg_match(self::RELLENO, $p));
            $termino = trim(implode(' ', $palabras));
            if (mb_strlen($termino) >= 3) {
                $res['terminos'][$termino] = $termino;
            }
        }
        return ['grupos' => array_values($res['grupos']), 'terminos' => array_values($res['terminos'])];
    }

    public static function vacio(?array $a): bool
    {
        return !$a || (empty($a['grupos']) && empty($a['terminos']));
    }

    /** ¿Debe excluirse este alimento? Requiere 'nombre' y, si existe, 'categoria'. */
    public static function excluye(array $alimento, ?array $a): bool
    {
        if (self::vacio($a)) return false;
        $nombre = self::normalizar((string)($alimento['nombre'] ?? ''));
        $cat = self::normalizar((string)($alimento['categoria'] ?? ''));

        foreach ($a['grupos'] ?? [] as $clave) {
            $g = self::GRUPOS[$clave] ?? null;
            if (!$g) continue;
            if ($cat !== '' && in_array($cat, $g['categorias'], true)) return true;
            $excepcion = false;
            foreach ($g['excepciones'] as $ex) {
                if (str_contains($nombre, $ex)) { $excepcion = true; break; }
            }
            if ($excepcion) continue;
            foreach ($g['alimentos'] as $palabra) {
                if (preg_match('/\b' . preg_quote($palabra, '/') . '(s|es)?\b/u', $nombre)) return true;
            }
        }
        foreach ($a['terminos'] ?? [] as $t) {
            // raíz sin "s"/"es" final para cubrir plurales
            $raiz = preg_replace('/(es|s)$/', '', $t);
            if (mb_strlen($raiz) < 3) $raiz = $t;
            if (preg_match('/\b' . preg_quote($raiz, '/') . '/u', $nombre)) return true;
        }
        return false;
    }

    public static function filtrar(array $alimentos, ?array $a): array
    {
        if (self::vacio($a)) return $alimentos;
        return array_values(array_filter($alimentos, fn($x) => !self::excluye($x, $a)));
    }

    /**
     * Alimentos de un plan ya guardado que chocan con las alergias registradas
     * (útil si la alergia se anotó después de generar el plan).
     * @return string[] nombres únicos
     */
    public static function conflictos(array $menu, ?array $a): array
    {
        if (self::vacio($a)) return [];
        $out = [];
        foreach ($menu as $tiempos) {
            foreach ($tiempos as $celda) {
                foreach ($celda['alimentos'] ?? [] as $al) {
                    if (self::excluye(['nombre' => $al['nombre'] ?? ''], $a)) {
                        $out[$al['nombre']] = $al['nombre'];
                    }
                }
            }
        }
        return array_values($out);
    }

    /** Texto legible para mostrar al nutricionista */
    public static function describir(?array $a): string
    {
        if (self::vacio($a)) return '';
        $partes = [];
        foreach ($a['grupos'] as $c) $partes[] = self::GRUPOS[$c]['etiqueta'];
        foreach ($a['terminos'] as $t) $partes[] = '“' . $t . '”';
        return implode(', ', $partes);
    }
}
