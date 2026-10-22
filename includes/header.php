<?php
/**
 * header.php — Encabezado compartido de todas las pantallas de SNWO.
 * Variables esperadas antes del include:
 *   $pageTitle  (string) título mostrado en la barra superior
 *   $activeNav  (string) clave de la pestaña activa en el menú
 */
$activeNav = $activeNav ?? '';

// Paciente activo (anclado en sesión): las pestañas marcadas con 'paciente' => true
// trabajan con él; si no hay uno elegido, llevan a la lista de pacientes para elegirlo.
$pacActivo = $_SESSION['paciente_activo'] ?? null;

$navItems = [
    'dashboard'    => ['label' => 'Dashboard',    'href' => 'dashboard.php'],
    'pacientes'    => ['label' => 'Pacientes',    'href' => 'pacientes.php'],
    'evaluacion'   => ['label' => 'Evaluación',   'href' => 'evaluacion.php',   'paciente' => true],
    'historial'    => ['label' => 'Historial',    'href' => 'historial.php',    'paciente' => true],
    'preferencias' => ['label' => 'Preferencias', 'href' => 'preferencias.php', 'paciente' => true],
    'generador'    => ['label' => 'Generador',    'href' => 'generador.php',    'paciente' => true],
    'personalizar' => ['label' => 'Personalizar', 'href' => 'personalizar.php', 'paciente' => true],
    'exportar'     => ['label' => 'Exportar PDF', 'href' => 'exportar.php',     'paciente' => true],
    'progreso'     => ['label' => 'Progreso',     'href' => 'progreso.php',     'paciente' => true],
    'reportes'     => ['label' => 'Reportes',     'href' => 'reportes.php'],
    'alimentos'    => ['label' => 'Alimentos',    'href' => 'alimentos.php'],
    'usuarios'     => ['label' => 'Usuarios',     'href' => 'usuarios.php'],
];

// Rol Paciente: menú reducido (solo "Mi plan") y sin barra de paciente activo
if (($_SESSION['rol'] ?? '') === 'Paciente') {
    $navItems  = ['miplan' => ['label' => 'Mi plan', 'href' => 'mi_plan.php']];
    $pacActivo = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'SNWO', ENT_QUOTES, 'UTF-8') ?> — Smart Nutrition</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    :root{
        --brand:#0e6e52;
        --brand-dark:#0a5640;
        --brand-light:#e7f5ef;
    }
    .brand-bg{background-color:var(--brand);}
    .brand-text{color:var(--brand);}
    .brand-border{border-color:var(--brand);}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;}
    [x-cloak]{display:none !important;}
    html{-webkit-text-size-adjust:100%;}
    img,svg{max-width:100%;}
    /* En celular, 16px evita que el navegador haga zoom al tocar un campo */
    @media (max-width:640px){ input,select,textarea{font-size:16px !important;} }
</style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">

<!-- Barra superior de la app -->
<header class="brand-bg text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 3-4 4.5-4 8a4 4 0 108 0c0-3.5-2.5-5-4-8z" />
        </svg>
      </div>
      <span class="font-bold tracking-tight">SNWO — Smart Nutrition</span>
    </div>
    <div class="flex items-center gap-3 sm:gap-4">
      <span class="text-sm text-emerald-50 max-w-[9rem] sm:max-w-none truncate">
        <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($_SESSION['rol'])): ?>
          <span class="text-emerald-200">(<?= htmlspecialchars($_SESSION['rol'] === 'SuperAdmin' ? 'Super Administrador' : $_SESSION['rol'], ENT_QUOTES, 'UTF-8') ?>)</span>
        <?php endif; ?>
      </span>
      <a href="logout.php" class="text-sm bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-md transition-colors">
        Cerrar sesión
      </a>
    </div>
  </div>
</header>

<!-- Navegación principal (se desplaza horizontalmente en pantallas pequeñas) -->
<nav class="bg-white border-b border-gray-200">
  <div class="max-w-6xl mx-auto px-4">
    <div class="flex gap-1 overflow-x-auto text-sm">
      <?php foreach ($navItems as $key => $item):
          $esActiva = $activeNav === $key;
          $necesitaPaciente = !empty($item['paciente']);
          if ($necesitaPaciente && $pacActivo) {
              $href  = $item['href'] . '?id=' . (int)$pacActivo['id'];
              $clase = $esActiva ? 'border-[var(--brand)] brand-text font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700';
              $titulo = '';
          } elseif ($necesitaPaciente) {
              $href  = 'pacientes.php?elegir=1';
              $clase = 'border-transparent text-gray-300 hover:text-gray-500';
              $titulo = 'Primero selecciona un paciente';
          } else {
              $href  = $item['href'];
              $clase = $esActiva ? 'border-[var(--brand)] brand-text font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700';
              $titulo = '';
          }
      ?>
        <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
           <?= $titulo ? 'title="' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
           class="whitespace-nowrap px-3 py-2.5 border-b-2 <?= $clase ?>">
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

<?php if ($pacActivo): ?>
  <!-- Barra del paciente activo: se mantiene al navegar entre pestañas -->
  <div class="bg-emerald-50 border-b border-emerald-100">
    <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-between text-sm">
      <span class="text-emerald-800">
        Paciente activo: <strong><?= htmlspecialchars($pacActivo['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
      </span>
      <span class="flex items-center gap-4">
        <a href="pacientes.php" class="brand-text hover:underline">Cambiar paciente</a>
        <a href="pacientes.php?soltar=1" class="text-gray-500 hover:text-gray-700" title="Dejar de trabajar con este paciente">Quitar ✕</a>
      </span>
    </div>
  </div>
<?php endif; ?>

<main class="max-w-6xl mx-auto px-4 py-4 sm:py-6">
