<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
function fechaHist($f): string
{
    $t = strtotime((string)$f);
    return $t ? date('d/m/Y', $t) : '—';
}

function horaHist($f): string
{
    $t = strtotime((string)$f);
    return $t ? date('H:i', $t) : '';
}

/**
 * Celda de variación contra la cita anterior.
 * $tono: 'peso' colorea según el objetivo del paciente; 'neutro' solo muestra la flecha.
 */
function variacion(?float $d, int $dec, string $unidad, string $tono, string $objetivo): string
{
    if ($d === null) return '<span class="text-gray-300">—</span>';
    if (round($d, $dec) == 0.0) {
        return '<span class="text-gray-400">= sin cambio</span>';
    }

    $flecha = $d > 0 ? '▲' : '▼';
    $texto  = ($d > 0 ? '+' : '−') . number_format(abs($d), $dec) . $unidad;

    $color = 'text-gray-500';
    if ($tono === 'peso') {
        if ($objetivo === 'perdida')        $bueno = $d < 0;
        elseif ($objetivo === 'ganancia')   $bueno = $d > 0;
        else                                $bueno = abs($d) <= 0.5;
        $color = $bueno ? 'text-emerald-600' : 'text-amber-600';
    }
    return '<span class="' . $color . ' font-medium">' . $flecha . ' ' . $texto . '</span>';
}

$objetivo = (string)($paciente['objetivo'] ?? '');
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Historial de evaluaciones — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="evaluacion.php?id=<?= $idPaciente ?>" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nueva evaluación</a>
  </div>

  <div class="p-5">
    <?php if ($total === 0): ?>
      <div class="rounded-lg bg-gray-50 border border-gray-200 text-gray-500 text-sm px-4 py-6 text-center">
        Este paciente todavía no tiene evaluaciones registradas.
        <a href="evaluacion.php?id=<?= $idPaciente ?>" class="brand-text font-medium hover:underline block mt-2">Registrar la primera evaluación →</a>
      </div>
    <?php else: ?>

      <!-- Resumen -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Evaluaciones</p>
          <p class="text-xl font-bold text-gray-900"><?= $total ?></p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Primera cita</p>
          <p class="text-xl font-bold text-gray-900"><?= fechaHist($primera['fecha_evaluacion']) ?></p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Última cita</p>
          <p class="text-xl font-bold text-gray-900"><?= fechaHist($ultima['fecha_evaluacion']) ?></p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Cambio de peso total</p>
          <p class="text-xl font-bold text-gray-900">
            <?= $cambioTotal === null ? '—' : (($cambioTotal > 0 ? '+' : ($cambioTotal < 0 ? '−' : '')) . number_format(abs($cambioTotal), 1) . ' kg') ?>
          </p>
        </div>
      </div>

      <!-- Tabla de evaluaciones -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b border-gray-100">
              <th class="py-2 pr-4 font-medium">Fecha</th>
              <th class="py-2 pr-4 font-medium">Peso</th>
              <th class="py-2 pr-4 font-medium">IMC</th>
              <th class="py-2 pr-4 font-medium">% Grasa</th>
              <th class="py-2 pr-4 font-medium">Cintura</th>
              <th class="py-2 pr-4 font-medium">GET</th>
              <th class="py-2 pr-4 font-medium">Fórmula</th>
              <th class="py-2 font-medium"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($evaluaciones as $i => $e): ?>
              <tr class="border-b border-gray-50 hover:bg-gray-50 align-top">
                <td class="py-3 pr-4 whitespace-nowrap">
                  <p class="font-medium text-gray-900"><?= fechaHist($e['fecha_evaluacion']) ?></p>
                  <p class="text-xs text-gray-400">
                    <?= horaHist($e['fecha_evaluacion']) ?>
                    <?php if ($i === 0): ?>
                      <span class="ml-1 text-[10px] font-semibold bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded-full">Más reciente</span>
                    <?php endif; ?>
                  </p>
                </td>
                <td class="py-3 pr-4 whitespace-nowrap">
                  <p class="font-semibold text-gray-900"><?= number_format((float)$e['peso_kg'], 1) ?> kg</p>
                  <p class="text-xs"><?= variacion($e['d_peso'], 1, ' kg', 'peso', $objetivo) ?></p>
                </td>
                <td class="py-3 pr-4 whitespace-nowrap">
                  <p class="font-semibold text-gray-900"><?= number_format((float)$e['imc'], 1) ?></p>
                  <p class="text-xs"><?= variacion($e['d_imc'], 1, '', 'neutro', $objetivo) ?></p>
                </td>
                <td class="py-3 pr-4 whitespace-nowrap">
                  <p class="font-semibold text-gray-900"><?= $e['porcentaje_grasa'] === null ? '—' : number_format((float)$e['porcentaje_grasa'], 1) . '%' ?></p>
                  <p class="text-xs"><?= variacion($e['d_grasa'], 1, '%', 'neutro', $objetivo) ?></p>
                </td>
                <td class="py-3 pr-4 whitespace-nowrap">
                  <p class="font-semibold text-gray-900"><?= $e['cintura_cm'] === null ? '—' : number_format((float)$e['cintura_cm'], 1) . ' cm' ?></p>
                  <p class="text-xs"><?= variacion($e['d_cintura'], 1, ' cm', 'neutro', $objetivo) ?></p>
                </td>
                <td class="py-3 pr-4 whitespace-nowrap font-semibold text-gray-900"><?= number_format((float)$e['get_calculado'], 0) ?> kcal</td>
                <td class="py-3 pr-4 whitespace-nowrap text-gray-600"><?= htmlspecialchars((string)$e['formula_utilizada'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="py-3 whitespace-nowrap">
                  <a href="evaluacion.php?id=<?= $idPaciente ?>&resultado=<?= (int)$e['id_evaluacion'] ?>"
                     class="brand-text font-medium hover:underline">Ver detalle</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="text-xs text-gray-400 mt-4">
        Las variaciones se calculan contra la cita inmediata anterior.
        <?php if ($objetivo === 'perdida'): ?>Objetivo del paciente: pérdida de peso (verde = baja).
        <?php elseif ($objetivo === 'ganancia'): ?>Objetivo del paciente: ganancia (verde = sube).
        <?php else: ?>Objetivo del paciente: mantenimiento (verde = cambio de hasta 0.5 kg).<?php endif; ?>
      </p>

      <div class="flex gap-3 flex-wrap mt-5">
        <a href="progreso.php?id=<?= $idPaciente ?>" class="border border-emerald-300 bg-emerald-50 brand-text text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-emerald-100">Ver gráfica de progreso</a>
        <a href="pacientes.php" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Volver a pacientes</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
