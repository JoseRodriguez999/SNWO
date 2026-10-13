<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Personalizar plan — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  </div>

  <div class="p-5">
    <?php if ($error): ?>
      <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$dieta): ?>
      <div class="text-center py-10">
        <p class="text-gray-500 mb-4">Este paciente todavía no tiene un plan generado.</p>
        <a href="generador.php?id=<?= $idPaciente ?>" class="brand-bg text-white font-semibold px-5 py-2.5 rounded-lg hover:opacity-90 inline-block">Ir al generador</a>
      </div>

    <?php elseif ($editandoMenu > 0 && $tiempoSlot): ?>
      <!-- ================= BUSCADOR DE SUSTITUTO ================= -->
      <div class="mb-4">
        <a href="personalizar.php?id=<?= $idPaciente ?>" class="text-sm brand-text hover:underline">← Volver al plan completo</a>
      </div>
      <h2 class="font-semibold text-gray-800 mb-1">Buscar sustituto para <?= $tiempoSlot === 'Colacion' ? 'Colación' : $tiempoSlot ?></h2>
      <p class="text-xs text-gray-400 mb-4">Solo se muestran alimentos compatibles con este tiempo de comida (mismas reglas que usa el generador automático).</p>

      <form method="GET" action="personalizar.php" class="flex gap-3 mb-4">
        <input type="hidden" name="id" value="<?= $idPaciente ?>">
        <input type="hidden" name="editar_menu" value="<?= $editandoMenu ?>">
        <input type="hidden" name="editar_alimento" value="<?= $editandoAlimento ?>">
        <input type="text" name="buscar" value="<?= htmlspecialchars($terminoBusqueda, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Ej. pollo, frijol, papaya..."
               class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        <button type="submit" class="brand-bg text-white text-sm font-semibold px-5 rounded-lg hover:opacity-90">Buscar</button>
      </form>

      <div class="space-y-2">
        <?php foreach ($resultadosBusqueda as $r): ?>
          <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
            <div>
              <p class="font-medium text-gray-900"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-sm text-gray-500"><?= $r['categoria'] ?> — <?= number_format((float)$r['porcion_gramos'], 0) ?> g de referencia = <?= number_format((float)$r['calorias'], 0) ?> kcal</p>
            </div>
            <form method="POST" action="personalizar.php?id=<?= $idPaciente ?>">
              <input type="hidden" name="accion" value="sustituir_alimento">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_menu" value="<?= $editandoMenu ?>">
              <input type="hidden" name="id_alimento_viejo" value="<?= $editandoAlimento ?>">
              <input type="hidden" name="id_alimento_nuevo" value="<?= $r['id_alimento'] ?>">
              <button type="submit" class="brand-bg text-white text-sm font-medium px-3 py-1.5 rounded-md hover:opacity-90">Seleccionar</button>
            </form>
          </div>
        <?php endforeach; ?>
        <?php if (empty($resultadosBusqueda)): ?>
          <p class="text-sm text-gray-400 text-center py-6">Sin resultados. Prueba con otro término.</p>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- ================= MENÚ COMPLETO CON OPCIÓN DE CAMBIAR ================= -->
      <p class="text-sm text-gray-500 mb-4">Dale clic a <strong>🔁 Cambiar</strong> junto a cualquier alimento para sustituirlo manteniendo las mismas calorías del tiempo de comida.</p>

      <?php foreach ($dias as $numDia => $etiquetaDia): ?>
        <h2 class="font-semibold text-gray-800 mt-6 mb-2"><?= $etiquetaDia ?></h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3 mb-2">
          <?php foreach ($tiempos as $tiempo): ?>
            <?php $celda = $dieta['menu'][$numDia][$tiempo] ?? null; ?>
            <div class="border border-gray-200 rounded-lg p-3">
              <p class="text-xs font-semibold <?= $tiempo === 'Colacion' ? 'text-amber-600' : 'brand-text' ?> mb-2">
                <?= $tiempo === 'Colacion' ? 'Colación' : $tiempo ?>
              </p>
              <?php if ($celda && $celda['alimentos']): ?>
                <ul class="space-y-1.5">
                  <?php foreach ($celda['alimentos'] as $a): ?>
                    <li class="flex items-center justify-between text-sm">
                      <span class="text-gray-700"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-xs text-gray-400">(<?= number_format($a['porcion_ajustada_g'], 0) ?>g)</span>
                      </span>
                      <a href="personalizar.php?id=<?= $idPaciente ?>&editar_menu=<?= $celda['id_menu'] ?>&editar_alimento=<?= $a['id_alimento'] ?>"
                         class="text-xs brand-text hover:underline whitespace-nowrap ml-2">🔁 Cambiar</a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <span class="text-gray-300 text-sm">—</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <div class="flex gap-3 mt-6">
        <a href="generador.php?id=<?= $idPaciente ?>" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Volver al plan</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
