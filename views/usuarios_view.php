<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $v = static fn(string $k): string => htmlspecialchars((string)($old[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?>

<?php if ($exito): ?>
  <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3">
    ✓ <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
    mostrarModal: <?= $error !== '' ? 'true' : 'false' ?>,
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
    <?php if ($esAdmin): ?>
      <button type="button" @click="mostrarModal = true" class="bg-white brand-text text-sm font-semibold px-3 py-1.5 rounded-md hover:bg-emerald-50">+ Nuevo usuario</button>
    <?php endif; ?>
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
            <?php if ($esAdmin): ?>
            <form method="POST" action="usuarios.php" class="inline">
              <input type="hidden" name="accion" value="cambiar_estado">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_usuario" :value="u.id_usuario">
              <input type="hidden" name="activo" :value="u.activo == 1 ? 0 : 1">
              <button type="submit" class="font-medium hover:underline" :class="u.activo == 1 ? 'text-red-500' : 'text-emerald-600'" x-text="u.activo == 1 ? 'Desactivar' : 'Activar'"></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      </template>
    </tbody>
  </table>

  <div class="px-5 py-3 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100">
    <span x-text="filtrados.length + ' de <?= count($usuarios) ?> usuarios registrados — contraseñas cifradas con BCRYPT'"></span>
  </div>
  <?php if ($esAdmin): ?>
  <!-- Modal: Nuevo usuario -->
  <div x-show="mostrarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="mostrarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Nuevo usuario</h2>
        <button type="button" @click="mostrarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>

      <form method="POST" action="usuarios.php" autocomplete="off" class="p-5 space-y-4"
            x-data="{ ver: false, pass: '', pass2: '',
                      generar() {
                        const L = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz', N = '23456789';
                        const r = (s, n) => Array.from(crypto.getRandomValues(new Uint32Array(n)), v => s[v % s.length]).join('');
                        this.pass = this.pass2 = r(L, 8) + r(N, 3) + r(L, 1); this.ver = true;
                      } }">
        <input type="hidden" name="accion" value="crear_usuario">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <?php if ($error !== ''): ?>
          <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <input type="text" name="nombre_usuario" required maxlength="100" value="<?= $v('nombre_usuario') ?>"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
            <input type="email" name="email" required maxlength="150" value="<?= $v('email') ?>"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
            <select name="rol" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Selecciona...</option>
              <?php foreach (['Administrador', 'Nutricionista', 'Paciente'] as $r): ?>
                <option value="<?= $r ?>" <?= ($old['rol'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña inicial *</label>
            <input :type="ver ? 'text' : 'password'" name="password" required minlength="8" maxlength="72" x-model="pass" autocomplete="new-password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
            <input :type="ver ? 'text' : 'password'" name="password2" required x-model="pass2" autocomplete="new-password"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div class="flex items-center justify-between text-xs">
          <label class="flex items-center gap-2 text-gray-500"><input type="checkbox" x-model="ver"> Mostrar contraseñas</label>
          <button type="button" @click="generar()" class="brand-text font-medium hover:underline">Generar contraseña segura</button>
        </div>
        <p class="text-xs text-gray-400">De 8 a 72 caracteres, con letras y números. Se guarda cifrada (BCRYPT). Entrégala al usuario y pídele cambiarla con “¿Olvidaste tu contraseña?”.</p>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar usuario</button>
          <button type="button" @click="mostrarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
