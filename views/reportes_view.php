<?php
include __DIR__ . '/../includes/header.php';

$h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
$selClase = 'rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white';
?>

<?php if ($r === ''): ?>
  <div class="mb-4">
    <h1 class="text-xl font-bold text-gray-900">Reportes</h1>
    <p class="text-sm text-gray-500">Disponibles solo para la Nutricionista. Cada reporte se puede ver en pantalla, descargar en PDF o exportar a Excel (CSV).</p>
  </div>
  <div class="grid sm:grid-cols-2 gap-4">
    <?php foreach ($catalogo as $clave => [$titulo, $descripcion]): ?>
      <a href="reportes.php?r=<?= $h($clave) ?>" class="block rounded-xl border border-gray-200 bg-white p-5 hover:border-emerald-300 hover:bg-emerald-50 transition-colors">
        <h2 class="font-semibold brand-text mb-1"><?= $h($titulo) ?></h2>
        <p class="text-sm text-gray-500"><?= $h($descripcion) ?></p>
      </a>
    <?php endforeach; ?>
  </div>

<?php else: ?>
  <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div>
      <a href="reportes.php" class="text-sm brand-text hover:underline">← Todos los reportes</a>
      <h1 class="text-xl font-bold text-gray-900"><?= $h($catalogo[$r][0]) ?></h1>
    </div>
    <?php if ($reporte !== null): ?>
      <div class="flex gap-2">
        <a href="reportes.php?<?= $h($exportar) ?>&formato=pdf" class="brand-bg text-white text-sm font-semibold px-4 py-2 rounded-lg hover:opacity-90">Descargar PDF</a>
        <a href="reportes.php?<?= $h($exportar) ?>&formato=csv" class="border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">Exportar CSV</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Filtros -->
  <form method="get" action="reportes.php" class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4">
    <input type="hidden" name="r" value="<?= $h($r) ?>">

    <?php if ($r === 'pacientes'): ?>
      <label class="text-sm text-gray-600">Estado<br>
        <select name="estado" class="<?= $selClase ?>">
          <?php foreach (['todos' => 'Todos', 'activos' => 'Solo activos', 'inactivos' => 'Solo inactivos'] as $v => $t): ?>
            <option value="<?= $v ?>" <?= $filtros['estado'] === $v ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php elseif ($r === 'seguimiento'): ?>
      <label class="text-sm text-gray-600">Sin evaluar desde hace<br>
        <select name="dias" class="<?= $selClase ?>">
          <?php foreach ([15, 30, 60, 90] as $d): ?>
            <option value="<?= $d ?>" <?= $filtros['dias'] === $d ? 'selected' : '' ?>><?= $d ?> días o más</option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php elseif ($r === 'dietas'): ?>
      <label class="text-sm text-gray-600">Desde<br><input type="date" name="desde" value="<?= $h($filtros['desde']) ?>" class="<?= $selClase ?>"></label>
      <label class="text-sm text-gray-600">Hasta<br><input type="date" name="hasta" value="<?= $h($filtros['hasta']) ?>" class="<?= $selClase ?>"></label>
    <?php else: ?>
      <label class="text-sm text-gray-600">Paciente<br>
        <select name="paciente" class="<?= $selClase ?>" required>
          <option value="">Selecciona un paciente…</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?= (int)$p['id_paciente'] ?>" <?= $filtros['paciente'] === (int)$p['id_paciente'] ? 'selected' : '' ?>><?= $h($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>

    <button type="submit" class="brand-bg text-white text-sm font-semibold px-4 py-2 rounded-lg hover:opacity-90">Ver reporte</button>
  </form>

  <?php if ($error !== ''): ?>
    <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 mb-4"><?= $h($error) ?></div>
  <?php endif; ?>

  <?php if ($r === 'evaluacion' && $filtros['paciente'] && $reporte === null && $error === ''): ?>
    <?php if (!$evaluaciones): ?>
      <p class="text-sm text-gray-500 border border-dashed border-gray-200 rounded-lg px-4 py-6 text-center">Este paciente todavía no tiene evaluaciones.</p>
    <?php else: ?>
      <h2 class="font-semibold text-gray-800 mb-2">Elige la cita</h2>
      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="text-left px-4 py-2">Fecha</th><th class="text-left px-4 py-2">Peso (kg)</th><th class="text-left px-4 py-2">IMC</th><th class="text-left px-4 py-2">GET</th><th></th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($evaluaciones as $ev): ?>
              <tr>
                <td class="px-4 py-2"><?= $h(date('d/m/Y', strtotime((string)$ev['fecha_evaluacion']))) ?></td>
                <td class="px-4 py-2"><?= $h(number_format((float)$ev['peso_kg'], 1)) ?></td>
                <td class="px-4 py-2"><?= $h(number_format((float)$ev['imc'], 1)) ?></td>
                <td class="px-4 py-2"><?= $h(number_format((float)$ev['get_calculado'], 0)) ?> kcal</td>
                <td class="px-4 py-2 text-right"><a class="brand-text font-medium hover:underline" href="reportes.php?r=evaluacion&paciente=<?= (int)$filtros['paciente'] ?>&e=<?= (int)$ev['id_evaluacion'] ?>">Ver reporte</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($reporte !== null): ?>
    <?php if (!empty($reporte['subtitulo'])): ?>
      <p class="text-sm text-gray-500 mb-3"><?= $h($reporte['subtitulo']) ?></p>
    <?php endif; ?>

    <?php if (!empty($reporte['resumen'])): ?>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <?php foreach ($reporte['resumen'] as [$etiqueta, $valor]): ?>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center">
            <p class="text-xs text-gray-500 mb-1"><?= $h($etiqueta) ?></p>
            <p class="text-lg font-bold text-gray-900"><?= $h($valor) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($reporte['grafica']) && count($reporte['grafica']) >= 2):
        $pts = $reporte['grafica'];
        $vals = array_map(fn($p) => (float)$p[1], $pts);
        $min = min($vals); $max = max($vals);
        if ($max - $min < 1.0) { $min -= 0.5; $max += 0.5; }
        $pad = ($max - $min) * 0.1; $min -= $pad; $max += $pad;
        $W = 600; $H = 220; $ml = 44; $mr = 16; $mt = 12; $mb = 28;
        $n = count($pts);
        $coords = [];
        foreach ($pts as $i => $p) {
            $coords[] = [$ml + ($W - $ml - $mr) * $i / ($n - 1), $mt + ($H - $mt - $mb) * (1 - (((float)$p[1] - $min) / ($max - $min))), $p[0], (float)$p[1]];
        }
        $linea = implode(' ', array_map(fn($c) => round($c[0], 1) . ',' . round($c[1], 1), $coords));
    ?>
      <div class="rounded-xl border border-gray-200 bg-white p-4 mb-5">
        <h2 class="font-semibold text-gray-800 mb-2"><?= $h($reporte['graficaTitulo'] ?? '') ?></h2>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" class="w-full h-auto" role="img" aria-label="<?= $h($reporte['graficaTitulo'] ?? 'Gráfica') ?>">
          <?php for ($i = 0; $i <= 4; $i++):
              $y = $mt + ($H - $mt - $mb) * (1 - $i / 4); $v = $min + ($max - $min) * $i / 4; ?>
            <line x1="<?= $ml ?>" x2="<?= $W - $mr ?>" y1="<?= round($y, 1) ?>" y2="<?= round($y, 1) ?>" stroke="#e5e7eb"/>
            <text x="<?= $ml - 6 ?>" y="<?= round($y + 4, 1) ?>" font-size="11" text-anchor="end" fill="#6b7280"><?= number_format($v, 1) ?></text>
          <?php endfor; ?>
          <polyline points="<?= $linea ?>" fill="none" stroke="#0e6e52" stroke-width="2.5"/>
          <?php foreach ($coords as $i => $c): ?>
            <circle cx="<?= round($c[0], 1) ?>" cy="<?= round($c[1], 1) ?>" r="4" fill="#0e6e52"><title><?= $h($c[2] . ': ' . number_format($c[3], 1)) ?></title></circle>
            <?php if ($n <= 12 || $i % (int)ceil($n / 10) === 0): ?>
              <text x="<?= round($c[0], 1) ?>" y="<?= $H - 8 ?>" font-size="11" text-anchor="middle" fill="#6b7280"><?= $h($c[2]) ?></text>
            <?php endif; ?>
          <?php endforeach; ?>
        </svg>
      </div>
    <?php endif; ?>

    <?php foreach ($reporte['bloques'] as $b): ?>
      <?php if (!empty($b['titulo'])): ?><h2 class="font-semibold text-gray-800 mb-2 mt-4"><?= $h($b['titulo']) ?></h2><?php endif; ?>
      <?php if (!$b['filas']): ?>
        <p class="text-sm text-gray-500 border border-dashed border-gray-200 rounded-lg px-4 py-6 text-center">No hay registros para este reporte.</p>
      <?php else: ?>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white mb-4">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600"><tr>
              <?php foreach ($b['columnas'] as $c): ?><th class="text-left px-4 py-2 whitespace-nowrap"><?= $h($c) ?></th><?php endforeach; ?>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
              <?php foreach ($b['filas'] as $fila): ?>
                <tr><?php foreach ($fila as $celda): ?><td class="px-4 py-2"><?= $h($celda) ?></td><?php endforeach; ?></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
