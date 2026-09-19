<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
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
    <a href="alimentos.php" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nuevo alimento</a>
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
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
