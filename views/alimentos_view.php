<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $v = static fn(string $k): string => htmlspecialchars((string)($old[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?>

<?php if ($exito): ?>
  <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
    ✓ <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
    mostrarModal: <?= $error !== '' ? 'true' : 'false' ?>,
    q: '',
    alimentos: <?= htmlspecialchars(json_encode($alimentos), ENT_QUOTES, 'UTF-8') ?>,
    get filtrados() {
        if (!this.q.trim()) return this.alimentos;
        const t = this.q.toLowerCase();
        return this.alimentos.filter(a => a.nombre.toLowerCase().includes(t) || a.categoria.toLowerCase().includes(t));
    }
}">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Catálogo de alimentos guatemaltecos</h1>
    <?php if ($puedeCrear): ?>
      <button type="button" @click="mostrarModal = true" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nuevo alimento</button>
    <?php endif; ?>
  </div>

  <div class="p-4 flex gap-3 border-b border-gray-100">
    <div class="relative flex-1">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
      <input type="text" x-model="q" placeholder="Buscar alimento... (ej.: frijol)"
             class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
    </div>
  </div>

  <table class="w-full text-sm">
    <thead>
      <tr class="text-left text-gray-500 border-b border-gray-100">
        <th class="px-5 py-2 font-medium">Alimento</th>
        <th class="px-5 py-2 font-medium">Porción (g)</th>
        <th class="px-5 py-2 font-medium">Kcal</th>
        <th class="px-5 py-2 font-medium">Prot.</th>
        <th class="px-5 py-2 font-medium">C/H</th>
        <th class="px-5 py-2 font-medium">Grasa</th>
        <th class="px-5 py-2 font-medium">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <template x-for="a in filtrados" :key="a.id_alimento">
        <tr class="border-b border-gray-50 hover:bg-gray-50">
          <td class="px-5 py-3">
            <p class="font-medium text-gray-900" x-text="a.nombre"></p>
            <p class="text-xs text-gray-400" x-text="a.categoria"></p>
          </td>
          <td class="px-5 py-3 text-gray-600" x-text="a.porcion_gramos + 'g'"></td>
          <td class="px-5 py-3 font-semibold text-gray-900" x-text="a.calorias"></td>
          <td class="px-5 py-3 text-gray-600" x-text="a.proteinas_g + 'g'"></td>
          <td class="px-5 py-3 text-gray-600" x-text="a.carbohidratos_g + 'g'"></td>
          <td class="px-5 py-3 text-gray-600" x-text="a.grasas_g + 'g'"></td>
          <td class="px-5 py-3">
            <a href="alimentos.php" class="brand-text font-medium hover:underline">Editar</a>
          </td>
        </tr>
      </template>
      <tr x-show="filtrados.length === 0">
        <td colspan="7" class="px-5 py-8 text-center text-gray-400">Sin resultados para tu búsqueda.</td>
      </tr>
    </tbody>
  </table>

  <div class="px-5 py-3 text-xs text-gray-400 border-t border-gray-100">
    Mostrando <span x-text="filtrados.length"></span> de <?= (int)$total ?> alimentos en el catálogo
  </div>
  <?php if ($puedeCrear): ?>
  <!-- Modal: Nuevo alimento -->
  <div x-show="mostrarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="mostrarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Nuevo alimento</h2>
        <button type="button" @click="mostrarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>

      <form method="POST" action="alimentos.php" class="p-5 space-y-4"
            x-data="{ p: '<?= $v('proteinas_g') ?>', c: '<?= $v('carbohidratos_g') ?>', g: '<?= $v('grasas_g') ?>', kcal: '<?= $v('calorias') ?>',
                      calc() { const n = x => parseFloat(String(x).replace(',', '.')) || 0; this.kcal = Math.round(n(this.p) * 4 + n(this.c) * 4 + n(this.g) * 9); } }">
        <input type="hidden" name="accion" value="crear_alimento">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($error !== ''): ?>
          <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del alimento *</label>
          <input type="text" name="nombre" required maxlength="100" value="<?= $v('nombre') ?>" placeholder="Ej.: Tortilla de maíz"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
            <select name="id_categoria" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Selecciona...</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>" <?= (string)($old['id_categoria'] ?? '') === (string)$cat['id_categoria'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)$cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Porción (g) *</label>
            <input type="text" inputmode="decimal" name="porcion_gramos" required value="<?= $v('porcion_gramos') ?>" placeholder="Ej.: 30"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <p class="text-xs text-gray-500 -mb-2">Valores nutricionales de <strong>una porción</strong> (según la porción indicada).</p>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Proteínas (g) *</label>
            <input type="text" inputmode="decimal" name="proteinas_g" required x-model="p"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Carbohidratos (g) *</label>
            <input type="text" inputmode="decimal" name="carbohidratos_g" required x-model="c"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Grasas (g) *</label>
            <input type="text" inputmode="decimal" name="grasas_g" required x-model="g"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Calorías (kcal) *</label>
          <div class="flex gap-2">
            <input type="text" inputmode="decimal" name="calorias" required x-model="kcal"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <button type="button" @click="calc()" class="border border-emerald-300 bg-emerald-50 brand-text text-sm font-semibold px-3 rounded-lg hover:bg-emerald-100">Calcular desde macros</button>
          </div>
          <p class="text-xs text-gray-400 mt-1">Aproximado: proteínas×4 + carbohidratos×4 + grasas×9. Puedes escribir el valor de la tabla nutricional.</p>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fibra (g)</label>
            <input type="text" inputmode="decimal" name="fibra_g" value="<?= $v('fibra_g') ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sodio (mg)</label>
            <input type="text" inputmode="decimal" name="sodio_mg" value="<?= $v('sodio_mg') ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Azúcar (g)</label>
            <input type="text" inputmode="decimal" name="azucar_g" value="<?= $v('azucar_g') ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <p class="text-xs text-gray-400">Sodio y azúcar se usan para excluir el alimento en pacientes con hipertensión o diabetes; si los conoces, regístralos.</p>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar alimento</button>
          <button type="button" @click="mostrarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
