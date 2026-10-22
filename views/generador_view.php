<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Generador de dietas — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($dieta): ?>
      <span class="bg-white/15 text-xs px-3 py-1 rounded-full">Objetivo: <?= number_format($dieta['get'], 0) ?> kcal/día · <?= htmlspecialchars($dieta['estado'], ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
  </div>

  <div class="p-5">
    <?php if ($error): ?>
      <div class="mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($exito)): ?>
      <div class="mb-5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">✓ <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!empty($alergenosTexto)): ?>
      <div class="mb-5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
        <strong>Alergias / intolerancias registradas:</strong> <?= htmlspecialchars($alergenosTexto, ENT_QUOTES, 'UTF-8') ?>.
        El generador excluye automáticamente los alimentos relacionados (por nombre; revisa siempre el plan).
        <?php if (!empty($alergenosConflicto)): ?>
          <div class="mt-2 text-red-700 font-semibold">⚠ El plan actual incluye: <?= htmlspecialchars(implode(', ', $alergenosConflicto), ENT_QUOTES, 'UTF-8') ?>. Genera una nueva versión o cámbialos en Personalizar.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!$dieta): ?>
      <!-- ================= SIN DIETA GENERADA TODAVÍA ================= -->
      <div class="text-center py-10">
        <p class="text-gray-500 mb-4">Este paciente todavía no tiene un plan dietético generado.</p>
        <form method="POST" action="generador.php?id=<?= $idPaciente ?>">
          <input type="hidden" name="accion" value="generar_dieta">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit" class="brand-bg text-white font-semibold px-6 py-3 rounded-lg hover:opacity-90">
            ⚡ Generar dieta automática
          </button>
        </form>
        <p class="text-xs text-gray-400 mt-3">Requiere que el paciente tenga al menos una evaluación antropométrica registrada.</p>
      </div>

    <?php else: ?>
      <!-- ================= DIETA GENERADA (real, desde tbl_dietas) ================= -->
      <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-800">Menú semanal</h2>
            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($dieta['fechaInicio'])) ?> – <?= date('d/m/Y', strtotime($dieta['fechaFin'])) ?></span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="text-left brand-text border-b-2 brand-border">
                  <th class="py-2 pr-3 font-semibold">Tiempo</th>
                  <?php foreach ($dias as $numDia => $etiqueta): ?>
                    <th class="py-2 pr-3 font-semibold"><?= $etiqueta ?></th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tiempos as $tiempo): ?>
                  <tr class="border-b border-gray-100 align-top">
                    <td class="py-3 pr-3 font-semibold <?= $tiempo === 'Colacion' ? 'text-amber-600' : 'text-gray-800' ?>">
                      <?= $tiempo === 'Colacion' ? 'Colación' : $tiempo ?>
                    </td>
                    <?php foreach (array_keys($dias) as $numDia): ?>
                      <?php $celda = $dieta['menu'][$numDia][$tiempo] ?? null; ?>
                      <td class="py-3 pr-3 text-gray-600">
                        <?php if ($celda && $celda['alimentos']): ?>
                          <ul class="space-y-1">
                            <?php foreach ($celda['alimentos'] as $a): ?>
                              <li class="leading-tight">
                                <span class="text-gray-800"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-xs text-gray-400">
                                  — <?= number_format((float)$a['porcion_ajustada_g'], 0) ?> g
                                  (<?= number_format((float)$a['calorias_aportadas'], 0) ?> kcal)
                                </span>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                          <div class="text-xs font-medium text-gray-500 mt-1 pt-1 border-t border-gray-100">
                            Total: <?= number_format($celda['kcal_total'], 0) ?> kcal
                          </div>
                        <?php else: ?>
                          <span class="text-gray-300">—</span>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="flex gap-3 mt-5 flex-wrap">
            <?php if ($dieta['estado'] === 'Borrador'): ?>
              <form method="POST" action="generador.php?id=<?= $idPaciente ?>" class="inline" onsubmit="return confirm('¿Activar este plan? El paciente lo verá en “Mi plan”<?= $hayActiva ? ' y reemplazará al plan activo actual' : '' ?>.');">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="estado" value="Activa">
                <input type="hidden" name="id_dieta" value="<?= (int)$dieta['idDieta'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="bg-emerald-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">✔ Activar plan</button>
              </form>
            <?php elseif ($dieta['estado'] === 'Activa'): ?>
              <form method="POST" action="generador.php?id=<?= $idPaciente ?>" class="inline" onsubmit="return confirm('¿Finalizar este plan? El paciente dejará de verlo.');">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="estado" value="Finalizada">
                <input type="hidden" name="id_dieta" value="<?= (int)$dieta['idDieta'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="border border-red-300 text-red-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-red-50">Finalizar plan</button>
              </form>
            <?php endif; ?>
            <a href="exportar.php?id=<?= $idPaciente ?>" class="brand-bg text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">Exportar PDF</a>
            <a href="personalizar.php?id=<?= $idPaciente ?>" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">✎ Personalizar</a>
            <form method="POST" action="generador.php?id=<?= $idPaciente ?>" class="inline">
              <input type="hidden" name="accion" value="generar_dieta">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">🔄 Generar nueva versión</button>
            </form>
          </div>
        </div>

        <div>
          <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center mb-4">
            <p class="text-sm text-gray-500 mb-1">Calorías objetivo</p>
            <p class="text-3xl font-bold text-gray-900"><?= number_format($dieta['get'], 0) ?></p>
            <p class="text-xs text-emerald-600 mt-1">kcal/día
              <?php if (!empty($dieta['getBase']) && round($dieta['getBase']) != round($dieta['get'])): ?>
                · GET: <?= number_format($dieta['getBase'], 0) ?> (ajustado por objetivo)
              <?php endif; ?>
            </p>
          </div>

          <div class="mb-4 text-xs rounded-lg px-3 py-2 <?= $dieta['estado'] === 'Activa' ? 'bg-emerald-50 text-emerald-700' : ($dieta['estado'] === 'Borrador' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') ?>">
            <?php if ($dieta['estado'] === 'Borrador'): ?>
              Borrador: el paciente <?= $hayActiva ? 'sigue viendo el plan activo anterior' : 'todavía no ve ningún plan' ?> hasta que lo actives.
            <?php elseif ($dieta['estado'] === 'Activa'): ?>
              Plan activo: es el que ve el paciente en “Mi plan”.
            <?php else: ?>
              Plan finalizado: el paciente ya no lo ve. Genera una nueva versión para continuar.
            <?php endif; ?>
          </div>

          <div class="space-y-1 text-sm mb-5">
            <div class="flex justify-between"><span class="text-gray-500">Proteínas (meta)</span><span class="font-semibold"><?= number_format($dieta['metaProteina'], 0) ?> g</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Carbohidratos (meta)</span><span class="font-semibold"><?= number_format($dieta['metaCarbohidratos'], 0) ?> g</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Grasas (meta)</span><span class="font-semibold"><?= number_format($dieta['metaGrasa'], 0) ?> g</span></div>
          </div>

          <h3 class="text-sm font-semibold text-gray-700 mb-2">Distribución calórica por tiempo</h3>
          <div class="space-y-2">
            <?php
            $porcentajes = ['Desayuno' => 25, 'Almuerzo' => 35, 'Cena' => 25, 'Colacion' => 15];
            foreach ($porcentajes as $tiempo => $pct):
            ?>
              <div>
                <div class="flex justify-between text-xs text-gray-500 mb-0.5">
                  <span><?= $tiempo === 'Colacion' ? 'Colación' : $tiempo ?></span><span><?= $pct ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                  <div class="brand-bg h-2 rounded-full" style="width: <?= $pct ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
