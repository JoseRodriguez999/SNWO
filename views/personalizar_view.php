<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Personalización del plan — Almuerzo / Lunes</h1>
    <span class="text-xs text-emerald-50">generador.php</span>
  </div>

  <div class="grid md:grid-cols-2 gap-6 p-5"
       x-data="{
          buscar: '',
          editandoIdx: 1,
          alimentos: <?= htmlspecialchars(json_encode($alimentosActuales), ENT_QUOTES, 'UTF-8') ?>,
          catalogo: <?= htmlspecialchars(json_encode($catalogoAlternativas), ENT_QUOTES, 'UTF-8') ?>,
          get resultados() {
            if (!this.buscar.trim()) return this.catalogo;
            const t = this.buscar.toLowerCase();
            return this.catalogo.filter(a => a.q.toLowerCase().includes(t) || a.nombre.toLowerCase().includes(t));
          },
          elegir(item) {
            this.alimentos[this.editandoIdx] = { nombre: item.nombre, detalle: item.detalle, editando: true };
          }
       }">
    <div>
      <h2 class="font-semibold text-gray-800 mb-3">Alimentos actuales</h2>
      <div class="space-y-2">
        <template x-for="(a, idx) in alimentos" :key="idx">
          <div class="flex items-center justify-between rounded-lg border px-4 py-3"
               :class="idx === editandoIdx ? 'bg-emerald-50 border-emerald-300' : 'border-gray-200'">
            <div>
              <p class="font-medium text-gray-900" x-text="a.nombre"></p>
              <p class="text-sm text-gray-500" x-text="a.detalle"></p>
            </div>
            <button @click="editandoIdx = idx" class="text-sm" :class="idx === editandoIdx ? 'text-emerald-600' : 'brand-text hover:underline'">
              <span x-show="idx === editandoIdx">Editando</span>
              <span x-show="idx !== editandoIdx">Editar</span>
            </button>
          </div>
        </template>
      </div>
    </div>

    <div>
      <h2 class="font-semibold text-gray-800 mb-3">Buscar alimento alternativo</h2>
      <input type="text" x-model="buscar" placeholder="pasta integral"
             class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-emerald-500">

      <div class="space-y-2 mb-4">
        <template x-for="item in resultados" :key="item.nombre">
          <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
            <div>
              <p class="font-medium text-gray-900" x-text="item.nombre"></p>
              <p class="text-sm text-gray-500" x-text="item.detalle"></p>
            </div>
            <button @click="elegir(item)" class="brand-bg text-white text-sm font-medium px-3 py-1.5 rounded-md hover:opacity-90">Seleccionar</button>
          </div>
        </template>
        <p x-show="resultados.length === 0" class="text-sm text-gray-400 text-center py-3">Sin resultados para tu búsqueda.</p>
      </div>

      <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
        ✓ Compatible con el perfil del paciente. Sin restricciones por patología.
      </div>
    </div>
  </div>

  <div class="px-5 pb-5 flex gap-3">
    <a href="generador.php?paciente=<?= urlencode($pacienteNombre) ?>" class="brand-bg text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">Guardar y volver al plan</a>
    <a href="generador.php?paciente=<?= urlencode($pacienteNombre) ?>" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Cancelar</a>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
