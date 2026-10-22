<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $v = static fn(string $k): string => htmlspecialchars((string)($old[$k] ?? ''), ENT_QUOTES, 'UTF-8'); ?>

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

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{
    mostrarModal: <?= $error !== '' ? 'true' : 'false' ?>,
    editarModal: <?= $errorEdicion !== '' ? 'true' : 'false' ?>,
    ed: <?= htmlspecialchars(json_encode((object)$oldEdit), ENT_QUOTES, 'UTF-8') ?>,
    yo: <?= (int)($_SESSION['id_usuario'] ?? 0) ?>,
    esSuper: <?= !empty($esSuper) ? 'true' : 'false' ?>,
    verEd: false, p1: '', p2: '',
    abrirEditar(u) {
        this.ed = { id_usuario: u.id_usuario, nombre_usuario: u.nombre_usuario, email: u.email, rol: (u.rol === 'Administrador' ? 'Nutricionista' : u.rol), esPaciente: u.rol === 'Paciente' };
        this.p1 = this.p2 = ''; this.verEd = false; this.editarModal = true;
    },
    generarEd() {
        const L = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz', N = '23456789';
        const r = (s, n) => Array.from(crypto.getRandomValues(new Uint32Array(n)), v => s[v % s.length]).join('');
        this.p1 = this.p2 = r(L, 8) + r(N, 3) + r(L, 1); this.verEd = true;
    },
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
        return { 'bg-purple-100 text-purple-700': rol==='SuperAdmin', 'bg-blue-100 text-blue-700': rol==='Administrador', 'bg-emerald-100 text-emerald-700': rol==='Nutricionista', 'bg-gray-100 text-gray-600': rol==='Paciente' }
    }
}">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Gestión de usuarios y roles</h1>
    <?php if ($puedeCrear): ?>
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
      <option value="Nutricionista">Nutricionista</option>
      <?php if (!empty($esSuper)): ?><option value="SuperAdmin">Super Administrador</option><?php endif; ?>
      <option value="Paciente">Paciente</option>
    </select>
    <select x-model="filtroEstado" class="border border-gray-300 rounded-lg text-sm px-3 py-2 text-gray-600">
      <option value="">Todos los estados</option>
      <option value="Activo">Activo</option>
      <option value="Inactivo">Inactivo</option>
    </select>
  </div>

  <div class="overflow-x-auto">

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
            <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="rolClase(u.rol)" x-text="u.rol === 'SuperAdmin' ? 'Super Administrador' : u.rol"></span>
          </td>
          <td class="px-5 py-3">
            <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                  :class="u.activo == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                  x-text="u.activo == 1 ? 'Activo' : 'Inactivo'"></span>
          </td>
          <td class="px-5 py-3 text-gray-600" x-text="u.ultimo_acceso ? u.ultimo_acceso.substring(0,10) : 'Nunca'"></td>
          <td class="px-5 py-3">
            <?php if ($esAdmin): ?>
            <button type="button" @click="abrirEditar(u)" class="brand-text font-medium hover:underline">Editar</button>
            <template x-if="u.rol !== 'SuperAdmin'">
            <span>
            <span class="text-gray-300 mx-1">|</span>
            <form method="POST" action="usuarios.php" class="inline">
              <input type="hidden" name="accion" value="cambiar_estado">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="id_usuario" :value="u.id_usuario">
              <input type="hidden" name="activo" :value="u.activo == 1 ? 0 : 1">
              <button type="submit" class="font-medium hover:underline" :class="u.activo == 1 ? 'text-red-500' : 'text-emerald-600'" x-text="u.activo == 1 ? 'Desactivar' : 'Activar'"></button>
            </form>
            <template x-if="esSuper">
              <span>
                <span class="text-gray-300 mx-1">|</span>
                <form method="POST" action="usuarios.php" class="inline"
                      @submit="if (!confirm('¿Eliminar definitivamente a ' + u.nombre_usuario + '? Esta acción no se puede deshacer.')) $event.preventDefault()">
                  <input type="hidden" name="accion" value="eliminar_usuario">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id_usuario" :value="u.id_usuario">
                  <button type="submit" class="font-medium text-red-600 hover:underline">Eliminar</button>
                </form>
              </span>
            </template>
            </span>
            </template>
            <?php endif; ?>
          </td>
        </tr>
      </template>
    </tbody>
  </table>
  </div>

  <div class="px-5 py-3 flex items-center justify-between text-xs text-gray-400 border-t border-gray-100">
    <span x-text="filtrados.length + ' de <?= count($usuarios) ?> usuarios registrados — contraseñas cifradas con BCRYPT'"></span>
  </div>
  <?php if ($puedeCrear): ?>
  <!-- Modal: Nuevo usuario -->
  <div x-show="mostrarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="mostrarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Nuevo usuario</h2>
        <button type="button" @click="mostrarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>

      <form method="POST" action="usuarios.php" autocomplete="off" class="p-5 space-y-4"
            x-data="{ ver: false, pass: '', pass2: '',
                      rol: '<?= $v('rol') ?>', idPaciente: '<?= $v('id_paciente') ?>',
                      pacientes: <?= htmlspecialchars(json_encode($pacientesLibres), ENT_QUOTES, 'UTF-8') ?>,
                      nombre: '<?= $v('nombre_usuario') ?>', correo: '<?= $v('email') ?>',
                      elegirPaciente() {
                        const p = this.pacientes.find(x => String(x.id_paciente) === String(this.idPaciente));
                        if (p) { this.nombre = p.nombre; this.correo = p.email; }
                      },
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
          <input type="text" name="nombre_usuario" required maxlength="100" x-model="nombre"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
            <input type="email" name="email" required maxlength="150" x-model="correo"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
            <select name="rol" required x-model="rol" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Selecciona...</option>
              <?php foreach (['Nutricionista', 'Paciente'] as $r): ?>
                <option value="<?= $r ?>" <?= ($old['rol'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div x-show="rol === 'Paciente'" x-cloak>
          <label class="block text-sm font-medium text-gray-700 mb-1">Paciente de esta cuenta *</label>
          <select name="id_paciente" x-model="idPaciente" @change="elegirPaciente()" :disabled="rol !== 'Paciente'"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">Selecciona...</option>
            <template x-for="p in pacientes" :key="p.id_paciente">
              <option :value="p.id_paciente" x-text="p.nombre + ' — ' + p.email"></option>
            </template>
          </select>
          <p class="text-xs text-gray-400 mt-1">Esta cuenta solo verá el plan de ese paciente. Solo aparecen pacientes que aún no tienen usuario; si falta alguien, regístralo primero en Pacientes.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        <p class="text-xs text-gray-400">De 8 a 72 caracteres, con letras y números. Se guarda cifrada (BCRYPT). Entrégala al usuario; si la olvida, el administrador puede asignarle una nueva desde Editar.</p>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar usuario</button>
          <button type="button" @click="mostrarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($esAdmin): ?>
  <!-- Modal: Editar usuario (Nutricionista) -->
  <div x-show="editarModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50" style="display: none;">
    <div @click.outside="editarModal = false" class="bg-white rounded-xl shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
      <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
        <h2 class="font-semibold">Editar usuario</h2>
        <button type="button" @click="editarModal = false" class="text-white/80 hover:text-white">✕</button>
      </div>
      <form method="POST" action="usuarios.php" autocomplete="off" class="p-5 space-y-4">
        <input type="hidden" name="accion" value="editar_usuario">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_usuario" :value="ed.id_usuario">

        <?php if ($errorEdicion !== ''): ?>
          <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($errorEdicion, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <input type="text" name="nombre_usuario" required maxlength="100" x-model="ed.nombre_usuario" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
            <input type="email" name="email" required maxlength="150" x-model="ed.email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
            <template x-if="ed.rol === 'Paciente'">
              <div>
                <input type="hidden" name="rol" value="Paciente">
                <input type="text" value="Paciente" disabled class="w-full border border-gray-200 bg-gray-50 text-gray-500 rounded-lg px-3 py-2 text-sm">
              </div>
            </template>
            <template x-if="ed.rol !== 'Paciente'">
              <select name="rol" required x-model="ed.rol" :disabled="ed.id_usuario == yo" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="Nutricionista">Nutricionista</option>
                <option value="SuperAdmin" x-show="ed.rol === 'SuperAdmin'">Super Administrador</option>
              </select>
            </template>
            <template x-if="ed.rol !== 'Paciente' && ed.id_usuario == yo">
              <input type="hidden" name="rol" :value="ed.rol">
            </template>
          </div>
        </div>
        <p class="text-xs text-gray-400">El rol Paciente no se puede cambiar (está ligado a un paciente) y no puedes cambiar tu propio rol.</p>

        <div class="border-t border-gray-100 pt-3">
          <p class="text-sm font-medium text-gray-700 mb-2">Nueva contraseña <span class="text-gray-400 font-normal">(opcional — déjala vacía para no cambiarla)</span></p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <input :type="verEd ? 'text' : 'password'" name="password" maxlength="72" x-model="p1" autocomplete="new-password" placeholder="Nueva contraseña" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <input :type="verEd ? 'text' : 'password'" name="password2" x-model="p2" autocomplete="new-password" placeholder="Confirmar" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div class="flex items-center justify-between text-xs mt-2">
            <label class="flex items-center gap-2 text-gray-500"><input type="checkbox" x-model="verEd"> Mostrar contraseñas</label>
            <button type="button" @click="generarEd()" class="brand-text font-medium hover:underline">Generar contraseña segura</button>
          </div>
          <p class="text-xs text-gray-400 mt-1">De 8 a 72 caracteres, con letras y números.</p>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="flex-1 brand-bg text-white font-semibold py-2.5 rounded-lg hover:opacity-90">Guardar cambios</button>
          <button type="button" @click="editarModal = false" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
