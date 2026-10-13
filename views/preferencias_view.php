<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
    q: '',
    catalogo: <?= htmlspecialchars(json_encode($catalogo), ENT_QUOTES, 'UTF-8') ?>,
    get filtrados() {
        if (!this.q.trim()) return this.catalogo;
        const t = this.q.toLowerCase();
        return this.catalogo.filter(a => a.nombre.toLowerCase().includes(t) || a.categoria.toLowerCase().includes(t));
    }
}">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Preferencias alimentarias — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  </div>

  <div class="p-5">
    <p class="text-sm text-gray-500 mb-4">
      Marca los alimentos que le gustan o no le gustan al paciente. El generador de dietas
      <strong>nunca</strong> incluirá lo marcado como "No me gusta", y priorizará lo marcado como "Me gusta".
    </p>

    <div class="relative mb-4">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
      <input type="text" x-model="q" placeholder="Buscar alimento..."
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
            <form method="POST" action="preferencias.php?id=<?= $idPaciente ?>">
              <input type="hidden" name="accion" value="marcar_preferencia">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_alimento" :value="a.id_alimento">
              <input type="hidden" name="tipo" :value="a.tipo_preferencia === 'me_gusta' ? 'neutral' : 'me_gusta'">
              <button type="submit" class="text-lg px-2 py-1 rounded-md"
                      :class="a.tipo_preferencia === 'me_gusta' ? 'bg-emerald-100' : 'hover:bg-gray-100'"
                      title="Me gusta">👍</button>
            </form>
            <form method="POST" action="preferencias.php?id=<?= $idPaciente ?>">
              <input type="hidden" name="accion" value="marcar_preferencia">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_alimento" :value="a.id_alimento">
              <input type="hidden" name="tipo" :value="a.tipo_preferencia === 'no_me_gusta' ? 'neutral' : 'no_me_gusta'">
              <button type="submit" class="text-lg px-2 py-1 rounded-md"
                      :class="a.tipo_preferencia === 'no_me_gusta' ? 'bg-red-100' : 'hover:bg-gray-100'"
                      title="No me gusta">👎</button>
            </form>
          </div>
        </div>
      </template>
      <p x-show="filtrados.length === 0" class="text-sm text-gray-400 text-center py-6">Sin resultados.</p>
    </div>

    <div class="mt-5">
      <a href="pacientes.php" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50 inline-block">Volver a pacientes</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
