<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
    q: '', filtroRol: '', filtroEstado: '',
    usuarios: <?= htmlspecialchars(json_encode($usuarios), ENT_QUOTES, 'UTF-8') ?>,
    get filtrados() {
        return this.usuarios.filter(u => {
            const t = this.q.toLowerCase();
            const coincideTexto = !t || u.nombre_usuario.toLowerCase().includes(t) || u.email.toLowerCase().includes(t);
            const coincideRol = !this.filtroRol || u.rol === this.filtroRol;
            const coincideEstado = !this.filtroEstado || (this.filtroEstado === 'Activo' ? u.activo == 1 : u.activo == 0);
            return coincideTexto && coincideRol && coincideEstado;
        });
    },
    iniciales(nombre) {
        const partes = nombre.trim().split(/\s+/);
        return (partes[0][0] + (partes[partes.length-1][0]||'')).toUpperCase();
    },
    rolClase(rol) {
        return { 'bg-blue-100 text-blue-700': rol==='Administrador', 'bg-emerald-100 text-emerald-700': rol==='Nutricionista', 'bg-gray-100 text-gray-600': rol==='Paciente' }
    }
}">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Gestión de usuarios y roles</h1>
    <a href="usuarios.php" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nuevo usuario</a>
  </div>

  <div class="p-4 flex flex-col sm:flex-row gap-3 border-b border-gray-100">
    <div class="relative flex-1">
      <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">🔍</span>
      <input type="text" x-model="q" placeholder="Buscar usuario por nombre o correo..."
             class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
    </div>
    <select x-model="filtroRol" class="border border-gray-300 rounded-lg text-sm px-3 py-2 text-gray-600">
      <option value="">Todos los roles</option>
      <option value="Administrador">Administrador</option>
      <option value="Nutricionista">Nutricionista</option>
      <option value="Paciente">Paciente</option>
    </select>
    <select x-model="filtroEstado" class="border border-gray-300 rounded-lg text-sm px-3 py-2 text-gray-600">
      <option value="">Todos los estados</option>
      <option value="Activo">Activo</option>
      <option value="Inactivo">Inactivo</option>
    </select>
  </div>

  <table class="w-full text-sm">
    <thead>
      <tr class="text-left text-gray-500 border-b border-gray-100">
        <th class="px-5 py-2 font-medium">Usuario</th>
        <th class="px-5 py-2 font-medium">Rol</th>
        <th class="px-5 py-2 font-medium">Estado</th>
        <th class="px-5 py-2 font-medium">Último acceso</th>
        <th class="px-5 py-2 font-medium">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <template x-for="u in filtrados" :key="u.id_usuario">
        <tr class="border-b border-gray-50 hover:bg-gray-50">
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full brand-bg text-white text-xs flex items-center justify-center font-semibold" x-text="iniciales(u.nombre_usuario)"></div>
              <div>
                <p class="font-medium text-gray-900" x-text="u.nombre_usuario"></p>
                <p class="text-gray-400 text-xs" x-text="u.email"></p>
              </div>
            </div>
          </td>
          <td class="px-5 py-3">
            <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="rolClase(u.rol)" x-text="u.rol"></span>
          </td>
          <td class="px-5 py-3">
            <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                  :class="u.activo == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                  x-text="u.activo == 1 ? 'Activo' : 'Inactivo'"></span>
          </td>
          <td class="px-5 py-3 text-gray-600" x-text="u.ultimo_acceso ? u.ultimo_acceso.substring(0,10) : 'Nunca'"></td>
          <td class="px-5 py-3">
            <a href="usuarios.php" class="brand-text font-medium hover:underline">Editar</a>
            <span class="text-gray-300 mx-1">|</span>
            <form method="POST" action="usuarios.php" class="inline">
              <input type="hidden" name="accion" value="cambiar_estado">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_usuario" :value="u.id_usuario">
              <input type="hidden" name="activo" :value="u.activo == 1 ? 0 : 1">
              <button type="submit" class="font-medium hover:underline" :class="u.activo == 1 ? 'text-red-500' : 'text-emerald-600'" x-text="u.activo == 1 ? 'Desactivar' : 'Activar'"></button>
            </form>
          </td>
        </tr>
      </template>
    </tbody>
  </table>

  <div class="px-5 py-3 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100">
    <span x-text="filtrados.length + ' de <?= count($usuarios) ?> usuarios registrados — contraseñas cifradas con BCRYPT'"></span>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
