<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
$badgeColors = [
    'green' => 'bg-emerald-100 text-emerald-700',
    'amber' => 'bg-amber-100 text-amber-700',
    'gray'  => 'bg-gray-100 text-gray-600',
];
?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <?php foreach ($stats as $s): ?>
    <div class="rounded-xl border p-4 text-center <?= $s['destacado'] ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200' ?>">
      <p class="text-sm text-gray-500 mb-1"><?= $s['label'] ?></p>
      <p class="text-2xl font-bold text-gray-900"><?= $s['value'] ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid md:grid-cols-2 gap-8">
  <div>
    <h2 class="font-semibold text-gray-800 mb-3">Accesos rápidos</h2>
    <div class="space-y-2">
      <a href="pacientes.php?nuevo=1" class="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 font-semibold brand-text hover:bg-emerald-100 transition-colors">
        <span>▶</span> Nuevo paciente
      </a>
      <a href="pacientes.php" class="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-3 text-gray-600 hover:bg-gray-50 transition-colors">
        <span class="text-gray-400">▶</span> Buscar paciente
      </a>
      <a href="alimentos.php" class="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-3 text-gray-600 hover:bg-gray-50 transition-colors">
        <span class="text-gray-400">▶</span> Catálogo de alimentos
      </a>
    </div>
  </div>

  <div>
    <h2 class="font-semibold text-gray-800 mb-3">Últimas dietas generadas</h2>
    <div class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
      <?php foreach ($ultimasDietas as $d): ?>
        <a href="generador.php?paciente=<?= urlencode($d['nombre']) ?>" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
          <div>
            <p class="font-medium text-gray-900"><?= htmlspecialchars($d['nombre']) ?></p>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($d['detalle']) ?></p>
          </div>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full <?= $badgeColors[$d['color']] ?>"><?= $d['estado'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
