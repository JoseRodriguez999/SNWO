<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>

<?php if ($exito): ?>
  <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">✓ <?= $h($exito) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= $h($error) ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-semibold">Mi plan alimentario — <?= $h($paciente['nombre']) ?></h1>
      <?php if ($dieta): ?>
        <p class="text-xs text-emerald-100 mt-0.5">Meta: <?= number_format((float)$dieta['get'], 0) ?> kcal por día</p>
      <?php endif; ?>
    </div>
    <?php if ($dieta): ?>
      <a href="mi_plan.php?pdf=1" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">📄 Descargar PDF</a>
    <?php endif; ?>
  </div>

  <div class="flex gap-1 border-b border-gray-100 px-5 text-sm">
    <a href="mi_plan.php" class="px-3 py-2.5 border-b-2 <?= $vista === 'plan' ? 'border-[var(--brand)] brand-text font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">Mi plan</a>
    <a href="mi_plan.php?vista=gustos" class="px-3 py-2.5 border-b-2 <?= $vista === 'gustos' ? 'border-[var(--brand)] brand-text font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">Mis gustos</a>
  </div>

  <div class="p-5">

  <?php if ($vista === 'gustos'): ?>
    <!-- ================= MIS GUSTOS ================= -->
    <div x-data="{
        q: '',
        alimentos: <?= htmlspecialchars(json_encode($catalogo), ENT_QUOTES, 'UTF-8') ?>,
        get filtrados() {
            if (!this.q.trim()) return this.alimentos;
            const t = this.q.toLowerCase();
            return this.alimentos.filter(a => a.nombre.toLowerCase().includes(t) || a.categoria.toLowerCase().includes(t));
        }
    }">
      <p class="text-sm text-gray-500 mb-4">Marca 👍 lo que te gusta y 👎 lo que no quieres comer. Tus gustos se usarán cuando tu nutricionista prepare tu próximo plan, y los alimentos que marques con 👎 no se te ofrecerán al cambiar uno de tu plan.</p>
      <div class="relative mb-4">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
        <input type="text" x-model="q" placeholder="Buscar alimento... (ej.: frijol)"
               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
      </div>
      <div class="divide-y divide-gray-100 border border-gray-100 rounded-lg max-h-[600px] overflow-y-auto">
        <template x-for="a in filtrados" :key="a.id_alimento">
          <div class="flex items-center justify-between px-4 py-3">
            <div>
              <p class="font-medium text-gray-900" x-text="a.nombre"></p>
              <p class="text-xs text-gray-400" x-text="a.categoria"></p>
            </div>
            <div class="flex gap-2">
              <form method="POST" action="mi_plan.php">
                <input type="hidden" name="accion" value="marcar_preferencia">
                <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
                <input type="hidden" name="id_alimento" :value="a.id_alimento">
                <input type="hidden" name="tipo" :value="a.tipo_preferencia === 'me_gusta' ? 'neutral' : 'me_gusta'">
                <button type="submit" class="text-lg px-2 py-1 rounded-md" :class="a.tipo_preferencia === 'me_gusta' ? 'bg-emerald-100' : 'hover:bg-gray-100'" title="Me gusta">👍</button>
              </form>
              <form method="POST" action="mi_plan.php">
                <input type="hidden" name="accion" value="marcar_preferencia">
                <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
                <input type="hidden" name="id_alimento" :value="a.id_alimento">
                <input type="hidden" name="tipo" :value="a.tipo_preferencia === 'no_me_gusta' ? 'neutral' : 'no_me_gusta'">
                <button type="submit" class="text-lg px-2 py-1 rounded-md" :class="a.tipo_preferencia === 'no_me_gusta' ? 'bg-red-100' : 'hover:bg-gray-100'" title="No me gusta">👎</button>
              </form>
            </div>
          </div>
        </template>
        <p x-show="filtrados.length === 0" class="text-sm text-gray-400 text-center py-6">Sin resultados.</p>
      </div>
    </div>

  <?php elseif (!$dieta): ?>
    <div class="text-center py-10">
      <p class="text-gray-500">Todavía no tienes un plan alimentario. Tu nutricionista lo generará después de tu evaluación.</p>
      <a href="mi_plan.php?vista=gustos" class="inline-block mt-4 brand-text font-medium hover:underline">Mientras tanto, marca los alimentos que te gustan →</a>
    </div>

  <?php elseif ($editandoMenu > 0 && $tiempoSlot): ?>
    <!-- ================= BUSCADOR DE SUSTITUTO ================= -->
    <div class="mb-4"><a href="mi_plan.php" class="text-sm brand-text hover:underline">← Volver a mi plan</a></div>
    <h2 class="font-semibold text-gray-800 mb-1">Cambiar alimento del <?= $tiempoSlot === 'Colacion' ? 'colación' : mb_strtolower($tiempoSlot) ?></h2>
    <p class="text-xs text-gray-400 mb-4">Solo ves alimentos adecuados para este tiempo de comida y para tu plan. La porción se ajusta para mantener las mismas calorías.</p>

    <form method="GET" action="mi_plan.php" class="flex gap-3 mb-4">
      <input type="hidden" name="editar_menu" value="<?= (int)$editandoMenu ?>">
      <input type="hidden" name="editar_alimento" value="<?= (int)$editandoAlimento ?>">
      <input type="text" name="buscar" value="<?= $h($termino) ?>" placeholder="Ej. pollo, frijol, papaya..."
             class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
      <button type="submit" class="brand-bg text-white text-sm font-semibold px-5 rounded-lg hover:opacity-90">Buscar</button>
    </form>

    <div class="space-y-2">
      <?php foreach ($resultados as $r): ?>
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
          <div>
            <p class="font-medium text-gray-900"><?= $h($r['nombre']) ?>
              <?php if (($r['tipo_preferencia'] ?? '') === 'me_gusta'): ?><span class="ml-1">👍</span><?php endif; ?>
            </p>
            <p class="text-sm text-gray-500"><?= $h($r['categoria']) ?> — <?= number_format((float)$r['porcion_gramos'], 0) ?> g de referencia = <?= number_format((float)$r['calorias'], 0) ?> kcal</p>
          </div>
          <form method="POST" action="mi_plan.php">
            <input type="hidden" name="accion" value="sustituir_alimento">
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <input type="hidden" name="id_menu" value="<?= (int)$editandoMenu ?>">
            <input type="hidden" name="id_alimento_viejo" value="<?= (int)$editandoAlimento ?>">
            <input type="hidden" name="id_alimento_nuevo" value="<?= (int)$r['id_alimento'] ?>">
            <button type="submit" class="brand-bg text-white text-sm font-medium px-3 py-1.5 rounded-md hover:opacity-90">Elegir</button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (empty($resultados)): ?>
        <p class="text-sm text-gray-400 text-center py-6">Sin resultados. Prueba con otro término.</p>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <!-- ================= MI PLAN ================= -->
    <p class="text-sm text-gray-500 mb-2">Toca <strong>🔁 Cambiar</strong> junto a un alimento para elegir otro parecido. Tu nutricionista podrá ver los cambios que hagas.</p>

    <?php foreach ($dias as $numDia => $etiquetaDia): ?>
      <?php
        $kcalDia = 0.0;
        foreach ($tiempos as $t) { $kcalDia += (float)($dieta['menu'][$numDia][$t]['kcal_total'] ?? 0); }
      ?>
      <div class="flex items-baseline justify-between mt-6 mb-2">
        <h2 class="font-semibold text-gray-800"><?= $h($etiquetaDia) ?></h2>
        <span class="text-xs text-gray-400"><?= number_format($kcalDia, 0) ?> kcal</span>
      </div>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3 mb-2">
        <?php foreach ($tiempos as $tiempo): ?>
          <?php $celda = $dieta['menu'][$numDia][$tiempo] ?? null; ?>
          <div class="border border-gray-200 rounded-lg p-3">
            <p class="text-xs font-semibold <?= $tiempo === 'Colacion' ? 'text-amber-600' : 'brand-text' ?> mb-2"><?= $tiempo === 'Colacion' ? 'Colación' : $h($tiempo) ?></p>
            <?php if ($celda && $celda['alimentos']): ?>
              <ul class="space-y-1.5">
                <?php foreach ($celda['alimentos'] as $a): ?>
                  <li class="flex items-center justify-between text-sm">
                    <span class="text-gray-700"><?= $h($a['nombre']) ?> <span class="text-xs text-gray-400">(<?= number_format($a['porcion_ajustada_g'], 0) ?>g)</span></span>
                    <a href="mi_plan.php?editar_menu=<?= (int)$celda['id_menu'] ?>&editar_alimento=<?= (int)$a['id_alimento'] ?>" class="text-xs brand-text hover:underline whitespace-nowrap ml-2">🔁 Cambiar</a>
                  </li>
                <?php endforeach; ?>
              </ul>
              <p class="text-xs text-gray-400 mt-2"><?= number_format($celda['kcal_total'], 0) ?> kcal</p>
            <?php else: ?>
              <span class="text-gray-300 text-sm">—</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
