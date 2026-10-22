<?php include __DIR__ . '/../includes/header.php'; ?>

<div x-data="{
    mostrarModal: <?= isset($_GET['nuevo']) ? 'true' : 'false' ?>,
    editarModal: <?= $errorEdicion !== '' ? 'true' : 'false' ?>,
    ed: <?= htmlspecialchars(json_encode((object)$oldEdit), ENT_QUOTES, 'UTF-8') ?>,
    abrirEditar(p) {
        this.ed = { id: p.id, nombre: p.nombre, fecha_nacimiento: p.fecha_nacimiento, sexo: p.sexo, email: p.correo,
                    telefono: p.telefono || '', objetivo: p.objetivo_cod, nivel_actividad: p.nivel_actividad };
        this.editarModal = true;
    },
    activoId: <?= (int)$pacienteActivoId ?>,
    q: '',
    pacientes: <?= htmlspecialchars(json_encode($pacientes), ENT_QUOTES, 'UTF-8') ?>,
    get filtrados() {
        if (!this.q.trim()) return this.pacientes;
        const t = this.q.toLowerCase();
        return this.pacientes.filter(p => p.nombre.toLowerCase().includes(t) || p.correo.toLowerCase().includes(t));
    }
}">

  <?php if (isset($_GET['elegir'])): ?>
    <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3">
      Primero elige un paciente con el botón <strong>Seleccionar</strong>. Después podrás moverte entre
      Evaluación, Generador, Personalizar, Exportar PDF y Progreso sin volver a elegirlo.
    </div>
  <?php endif; ?>

  <?php if (!empty($errorGlobal)): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
      <?= htmlspecialchars($errorGlobal, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($exito): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
      ✓ <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if ($error && !isset($_GET['nuevo'])): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
      <h1 class="font-semibold">Gestión de pacientes</h1>
      <button @click="mostrarModal = true" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nuevo paciente</button>
    </div>

    <div class="p-4 flex flex-col sm:flex-row gap-3 border-b border-gray-100">
      <div class="relative flex-1">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
        <input type="text" x-model="q" placeholder="Buscar paciente por nombre o correo..."
               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
      </div>
      <select class="border border-gray-300 rounded-lg text-sm px-3 py-2 text-gray-600">
        <option>Todos los estados</option>
        <option>Activos</option>
        <option>Inactivos</option>
      </select>
    </div>

    <div class="overflow-x-auto">

    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-gray-500 border-b border-gray-100">
          <th class="px-5 py-2 font-medium">Paciente</th>
          <th class="px-5 py-2 font-medium">Edad / Sexo</th>
          <th class="px-5 py-2 font-medium">Objetivo</th>
          <th class="px-5 py-2 font-medium">Última evaluación</th>
          <th class="px-5 py-2 font-medium">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <template x-for="p in filtrados" :key="p.correo">
          <tr class="border-b border-gray-50 hover:bg-gray-50" :class="p.id === activoId ? 'bg-emerald-50/60' : ''">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full brand-bg text-white text-xs flex items-center justify-center font-semibold" x-text="p.iniciales"></div>
                <div>
                  <p class="font-medium text-gray-900" x-text="p.nombre"></p>
                  <p class="text-gray-400 text-xs" x-text="p.correo"></p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3 text-gray-600" x-text="p.edad + ' años / ' + p.sexo"></td>
            <td class="px-5 py-3">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                    :class="{'bg-emerald-100 text-emerald-700': p.color==='green','bg-blue-100 text-blue-700': p.color==='blue','bg-amber-100 text-amber-700': p.color==='amber'}"
                    x-text="p.objetivo"></span>
            </td>
            <td class="px-5 py-3 text-gray-600" x-text="p.evaluacion"></td>
            <td class="px-5 py-3">
              <span x-show="p.id === activoId" class="text-xs font-semibold bg-emerald-600 text-white px-2 py-0.5 rounded-full">✓ Activo</span>
              <a x-show="p.id !== activoId" :href="'pacientes.php?seleccionar=' + p.id"
                 class="text-xs font-semibold border border-emerald-600 brand-text px-2 py-0.5 rounded-full hover:bg-emerald-50">Seleccionar</a>
              <span class="text-gray-300 mx-1">|</span>
              <a :href="'evaluacion.php?id=' + p.id" class="brand-text font-medium hover:underline">Evaluar</a>
              <span class="text-gray-300 mx-1">|</span>
              <a :href="'progreso.php?id=' + p.id" class="brand-text font-medium hover:underline">Progreso</a>
              <span class="text-gray-300 mx-1">|</span>
              <a :href="'generador.php?id=' + p.id" class="brand-text font-medium hover:underline">Ver dieta</a>
              <span class="text-gray-300 mx-1">|</span>
              <a :href="'preferencias.php?id=' + p.id" class="brand-text font-medium hover:underline">Preferencias</a>
              <span class="text-gray-300 mx-1">|</span>
              <button type="button" @click="abrirEditar(p)" class="brand-text font-medium hover:underline">Editar</button>
              <?php if (!empty($esSuper)): ?>
              <span class="text-gray-300 mx-1">|</span>
              <form method="POST" action="pacientes.php" class="inline"
                    @submit="if (!confirm('¿Eliminar definitivamente a ' + p.nombre + '?\n\nSe borrarán también sus evaluaciones, dietas, historial, preferencias y su cuenta de acceso. Esta acción no se puede deshacer.')) $event.preventDefault()">
                <input type="hidden" name="accion" value="eliminar_paciente">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_paciente" :value="p.id">
                <button type="submit" class="font-medium text-red-600 hover:underline">Eliminar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        </template>
        <tr x-show="filtrados.length === 0">
          <td colspan="5" class="px-5 py-8 text-center text-gray-400">No hay pacientes que coincidan con tu búsqueda.</td>
        </tr>
      </tbody>
    </table>
  </div>

    <div class="px-5 py-3 text-xs text-gray-400 border-t border-gray-100">
      Mostrando <span x-text="filtrados.length"></span> de <?= count($pacientes) ?> pacientes
    </div>
  </div>

  <!-- Modal: Nuevo paciente -->
  <div x-show="mostrarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="mostrarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Nuevo paciente</h2>
        <button @click="mostrarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>

      <form method="POST" action="pacientes.php" class="p-5 space-y-4">
        <input type="hidden" name="accion" value="crear_paciente">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($error && isset($_GET['nuevo'])): ?>
          <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <input type="text" name="nombre" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento *</label>
            <input type="date" name="fecha_nacimiento" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
            <select name="sexo" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Selecciona...</option>
              <option value="F">Femenino</option>
              <option value="M">Masculino</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
            <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" maxlength="15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo *</label>
          <select name="objetivo" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">Selecciona...</option>
            <option value="perdida">Pérdida de peso</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="ganancia">Ganancia muscular</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de actividad *</label>
          <select name="nivel_actividad" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">Selecciona...</option>
            <option value="sedentario">Sedentario</option>
            <option value="moderado">Moderado</option>
            <option value="activo">Activo</option>
            <option value="muy activo">Muy activo</option>
          </select>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar paciente</button>
          <button type="button" @click="mostrarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Editar paciente -->
  <div x-show="editarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="editarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Editar paciente</h2>
        <button type="button" @click="editarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>

      <form method="POST" action="pacientes.php" class="p-5 space-y-4">
        <input type="hidden" name="accion" value="editar_paciente">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_paciente" :value="ed.id">

        <?php if ($errorEdicion !== ''): ?>
          <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <?= htmlspecialchars($errorEdicion, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <input type="text" name="nombre" required x-model="ed.nombre" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento *</label>
            <input type="date" name="fecha_nacimiento" required x-model="ed.fecha_nacimiento" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
            <select name="sexo" required x-model="ed.sexo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="F">Femenino</option>
              <option value="M">Masculino</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
            <input type="email" name="email" required x-model="ed.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" maxlength="15" x-model="ed.telefono" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo *</label>
          <select name="objetivo" required x-model="ed.objetivo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="perdida">Pérdida de peso</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="ganancia">Ganancia muscular</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de actividad *</label>
          <select name="nivel_actividad" required x-model="ed.nivel_actividad" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="sedentario">Sedentario</option>
            <option value="moderado">Moderado</option>
            <option value="activo">Activo</option>
            <option value="muy activo">Muy activo</option>
          </select>
        </div>
        <p class="text-xs text-gray-400">Si cambias el objetivo o el nivel de actividad, haz una nueva evaluación para recalcular el plan.</p>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar cambios</button>
          <button type="button" @click="editarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
