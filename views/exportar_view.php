<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
/** Resumen del tiempo de comida del primer día (hasta 3 alimentos) para la vista previa */
$resumenTiempo = function (array $dieta, string $tiempo): string {
    $celda = $dieta['menu'][1][$tiempo] ?? null;
    if (!$celda || empty($celda['alimentos'])) {
        return '—';
    }
    $nombres = array_map(fn($a) => $a['nombre'], array_slice($celda['alimentos'], 0, 3));
    return implode(', ', $nombres) . (count($celda['alimentos']) > 3 ? '…' : '');
};
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Exportación del plan dietético en PDF — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($dieta): ?>
      <span class="bg-white/15 text-xs px-3 py-1 rounded-full">Estado: <?= htmlspecialchars($dieta['estado'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
  </div>

  <div class="p-5">
    <?php if ($error): ?>
      <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$dieta): ?>
      <div class="text-center py-10">
        <p class="text-gray-500 mb-4">Este paciente todavía no tiene un plan dietético para exportar.</p>
        <a href="generador.php?id=<?= $idPaciente ?>" class="brand-bg text-white font-semibold px-5 py-2.5 rounded-lg hover:opacity-90 inline-block">Ir al generador de dietas</a>
      </div>

    <?php else: ?>
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Vista previa con datos reales del plan -->
        <div>
          <h2 class="font-semibold text-gray-800 mb-3">Vista previa del documento</h2>
          <div class="border border-gray-200 rounded-xl p-5 bg-gray-50">
            <p class="font-bold text-gray-900">CLÍNICA SMART NUTRITION</p>
            <p class="text-sm text-gray-500 mb-3">Plan dietético personalizado · <?= date('Y', strtotime($dieta['fechaInicio'])) ?></p>
            <hr class="border-gray-200 mb-3">
            <p class="text-sm mb-1"><span class="font-semibold">Paciente:</span> <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm mb-1"><span class="font-semibold">Nutricionista:</span> <?= htmlspecialchars($nutricionista, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm mb-1"><span class="font-semibold">GET objetivo:</span> <?= number_format($dieta['get'], 0) ?> kcal/día</p>
            <p class="text-sm mb-1"><span class="font-semibold">Metas:</span>
              Prot <?= number_format($dieta['metaProteina'], 0) ?> g ·
              CH <?= number_format($dieta['metaCarbohidratos'], 0) ?> g ·
              Grasa <?= number_format($dieta['metaGrasa'], 0) ?> g</p>
            <p class="text-sm mb-3"><span class="font-semibold">Vigencia:</span>
              <?= date('d/m/Y', strtotime($dieta['fechaInicio'])) ?> – <?= date('d/m/Y', strtotime($dieta['fechaFin'])) ?></p>

            <p class="text-xs text-gray-400 mb-1">Ejemplo — Lunes:</p>
            <p class="text-sm text-gray-700 mb-1">🍳 Desayuno: <?= htmlspecialchars($resumenTiempo($dieta, 'Desayuno'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm text-gray-700 mb-1">🥗 Almuerzo: <?= htmlspecialchars($resumenTiempo($dieta, 'Almuerzo'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-sm text-gray-700 mb-3">🍲 Cena: <?= htmlspecialchars($resumenTiempo($dieta, 'Cena'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="text-xs text-gray-400">[Plan completo 7 días — 4 tiempos de comida, con gramos y calorías por alimento]</p>
          </div>
        </div>

        <!-- Detalles y acciones -->
        <div>
          <h2 class="font-semibold text-gray-800 mb-3">Detalles de generación</h2>
          <div class="space-y-2 text-sm mb-5">
            <div class="flex justify-between"><span class="text-gray-500">Paciente</span><span class="font-semibold"><?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Nutricionista</span><span class="font-semibold"><?= htmlspecialchars($nutricionista, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Fecha</span><span class="font-semibold"><?= date('d/m/Y') ?></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Tiempo de generación</span>
              <span class="font-semibold <?= $generado ? 'text-emerald-600' : 'text-gray-400' ?>">
                <?= $generado ? htmlspecialchars((string)$generado['tiempo'], ENT_QUOTES, 'UTF-8') . ' seg ✓' : '—' ?>
              </span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Librería</span><span class="font-semibold">TCPDF / PHP</span></div>
          </div>

          <?php if ($generado): ?>
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 mb-4">
              ✓ PDF generado el <?= htmlspecialchars($generado['fecha'], ENT_QUOTES, 'UTF-8') ?> y guardado en el servidor
              <span class="block text-xs text-emerald-600 mt-0.5"><?= htmlspecialchars($generado['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <a href="exportar.php?id=<?= $idPaciente ?>&descargar=1"
               class="block w-full text-center brand-bg text-white font-semibold py-3 rounded-lg hover:opacity-90 mb-2">↓ Descargar PDF</a>
            <a href="exportar.php?id=<?= $idPaciente ?>&ver=1" target="_blank"
               class="block w-full text-center border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50 mb-2">Ver en el navegador</a>

            <form method="POST" action="exportar.php?id=<?= $idPaciente ?>">
              <input type="hidden" name="accion" value="generar_pdf">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="w-full text-sm text-gray-500 hover:text-gray-700 underline py-1">Volver a generar con el plan actual</button>
            </form>
          <?php else: ?>
            <form method="POST" action="exportar.php?id=<?= $idPaciente ?>">
              <input type="hidden" name="accion" value="generar_pdf">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="w-full brand-bg text-white font-semibold py-3 rounded-lg hover:opacity-90">📄 Generar PDF</button>
            </form>
            <p class="text-xs text-gray-400 mt-2 text-center">Se generará con el plan más reciente de este paciente, con gramos y calorías de cada alimento.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
