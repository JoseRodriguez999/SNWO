<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Generador de dietas — <?= htmlspecialchars($pacienteNombre) ?></h1>
    <span class="bg-white/15 text-xs px-3 py-1 rounded-full">GET objetivo: 1,842 kcal/día</span>
  </div>

  <div class="grid lg:grid-cols-3 gap-6 p-5">
    <div class="lg:col-span-2">
      <h2 class="font-semibold text-gray-800 mb-3">Menú semanal</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="text-left brand-text border-b-2 brand-border">
              <th class="py-2 pr-3 font-semibold">Tiempo</th>
              <?php foreach ($dias as $d): ?>
                <th class="py-2 pr-3 font-semibold"><?= $d ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($menu as $tiempo => $comidas): ?>
              <tr class="border-b border-gray-100 align-top">
                <td class="py-3 pr-3 font-semibold <?= $tiempo === 'Colación' ? 'text-amber-600' : 'text-gray-800' ?>"><?= $tiempo ?></td>
                <?php foreach ($comidas as $c): ?>
                  <td class="py-3 pr-3 text-gray-600"><?= htmlspecialchars($c) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="flex gap-3 mt-5">
        <a href="exportar.php?paciente=<?= urlencode($pacienteNombre) ?>" class="brand-bg text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">Exportar PDF</a>
        <a href="personalizar.php?paciente=<?= urlencode($pacienteNombre) ?>" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">✎ Personalizar</a>
      </div>
    </div>

    <div>
      <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center mb-4">
        <p class="text-sm text-gray-500 mb-1">Calorías totales</p>
        <p class="text-3xl font-bold text-gray-900">1,843</p>
        <p class="text-xs text-emerald-600 mt-1">Desviación: 0.05% ✓</p>
      </div>

      <div class="space-y-1 text-sm mb-5">
        <div class="flex justify-between"><span class="text-gray-500">Proteínas</span><span class="font-semibold">92 g</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Carbohidratos</span><span class="font-semibold">253 g</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Grasas</span><span class="font-semibold">51 g</span></div>
      </div>

      <h3 class="text-sm font-semibold text-gray-700 mb-2">Distribución calórica</h3>
      <div class="space-y-2">
        <?php foreach ($distribucion as $d): ?>
          <div>
            <div class="flex justify-between text-xs text-gray-500 mb-0.5">
              <span><?= $d['label'] ?></span><span><?= $d['pct'] ?>%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div class="brand-bg h-2 rounded-full" style="width: <?= $d['pct'] ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
