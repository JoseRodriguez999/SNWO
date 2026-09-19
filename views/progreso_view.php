<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Progreso — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  </div>

  <div class="p-5">
    <?php if (empty($historial)): ?>
      <div class="rounded-lg bg-gray-50 border border-gray-200 text-gray-500 text-sm px-4 py-6 text-center">
        Este paciente todavía no tiene evaluaciones registradas. El progreso se llena automáticamente
        cada vez que se guarda una nueva evaluación antropométrica.
        <a href="evaluacion.php?id=<?= $idPaciente ?>" class="brand-text font-medium hover:underline block mt-2">Registrar primera evaluación →</a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Peso inicial</p>
          <p class="text-xl font-bold text-gray-900"><?= number_format($pesoInicial, 1) ?> kg</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Peso actual</p>
          <p class="text-xl font-bold text-gray-900"><?= number_format($pesoActual, 1) ?> kg</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Cambio</p>
          <p class="text-xl font-bold text-gray-900"><?= ($reduccion <= 0 ? '' : '+') . number_format($reduccion, 1) ?> kg</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">IMC actual</p>
          <p class="text-xl font-bold text-gray-900"><?= number_format($imcActual, 1) ?></p>
        </div>
      </div>

      <h2 class="font-semibold text-gray-800 mb-3">Evolución de peso (kg)</h2>
      <div class="mb-6" style="height:220px;">
        <canvas id="graficaPeso"></canvas>
      </div>
    <?php endif; ?>

    <div class="flex gap-3 mt-4">
      <a href="evaluacion.php?id=<?= $idPaciente ?>" class="brand-bg text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">Nueva evaluación</a>
      <a href="pacientes.php" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Volver a pacientes</a>
    </div>
  </div>
</div>

<?php if (!empty($historial)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  const ctx = document.getElementById('graficaPeso');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_map(fn($h) => (new DateTime($h['fecha_registro']))->format('d/m'), $historial)) ?>,
      datasets: [{
        data: <?= json_encode(array_map(fn($h) => (float)$h['peso_kg'], $historial)) ?>,
        borderColor: '#0e6e52',
        backgroundColor: 'rgba(14,110,82,0.08)',
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#0e6e52',
        pointBorderWidth: 2,
        pointRadius: 5,
        tension: 0.35,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
    }
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
