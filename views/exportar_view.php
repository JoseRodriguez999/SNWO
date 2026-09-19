<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ generando: false, generado: false }">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Exportación del plan dietético en PDF</h1>
    <span class="bg-white/15 text-xs px-3 py-1 rounded-full">Estado: Activa</span>
  </div>

  <div class="grid md:grid-cols-2 gap-6 p-5">
    <div>
      <h2 class="font-semibold text-gray-800 mb-3">Vista previa del documento</h2>
      <div class="border border-gray-200 rounded-xl p-5 bg-gray-50">
        <p class="font-bold text-gray-900">CLÍNICA SMART NUTRITION</p>
        <p class="text-sm text-gray-500 mb-3">Plan dietético personalizado · 2026</p>
        <hr class="border-gray-200 mb-3">
        <p class="text-sm mb-1"><span class="font-semibold">Paciente:</span> <?= htmlspecialchars($pacienteNombre) ?></p>
        <p class="text-sm mb-1"><span class="font-semibold">Nutricionista:</span> Dra. Gabriela Sagastume</p>
        <p class="text-sm mb-3"><span class="font-semibold">GET objetivo:</span> 1,842 kcal/día</p>
        <p class="text-sm text-gray-700 mb-1">🍳 Desayuno: Avena, plátano, huevo cocido...</p>
        <p class="text-sm text-gray-700 mb-1">🥗 Almuerzo: Frijoles negros, arroz, pollo...</p>
        <p class="text-sm text-gray-700 mb-3">🍲 Cena: Sopa de verduras, pan integral...</p>
        <p class="text-xs text-gray-400">[Plan completo 7 días — 4 tiempos de comida]</p>
      </div>
    </div>

    <div>
      <h2 class="font-semibold text-gray-800 mb-3">Detalles de generación</h2>
      <div class="space-y-2 text-sm mb-5">
        <div class="flex justify-between"><span class="text-gray-500">Paciente</span><span class="font-semibold"><?= htmlspecialchars($pacienteNombre) ?></span></div>
        <div class="flex justify-between"><span class="text-gray-500">Nutricionista</span><span class="font-semibold">Dra. Sagastume</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Fecha</span><span class="font-semibold"><?= date('d/m/Y') ?></span></div>
        <div class="flex justify-between"><span class="text-gray-500">Tiempo de generación</span><span class="font-semibold text-emerald-600" x-text="generado ? '2.3 seg ✓' : '—'"></span></div>
        <div class="flex justify-between"><span class="text-gray-500">Librería</span><span class="font-semibold">TCPDF / PHP</span></div>
      </div>

      <div x-show="generado" x-cloak class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 mb-4">
        ✓ PDF generado y adjunto al expediente en SQL Server.
      </div>

      <button
        @click="generando = true; setTimeout(() => { generando = false; generado = true; }, 900)"
        :disabled="generando"
        class="w-full brand-bg text-white font-semibold py-3 rounded-lg hover:opacity-90 disabled:opacity-60">
        <span x-show="!generando && !generado">↓ Generar y descargar PDF</span>
        <span x-show="generando">Generando...</span>
        <span x-show="generado && !generando">↓ Descargar PDF</span>
      </button>
      <p class="text-xs text-gray-400 mt-2 text-center">Simulación del prototipo — al conectar TCPDF, generará el PDF real y lo guardará en el expediente.</p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
