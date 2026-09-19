<?php
/**
 * header.php — Encabezado compartido de todas las pantallas del prototipo SNWO.
 * Variables esperadas antes del include:
 *   $pageTitle  (string) título mostrado en la barra superior
 *   $activeNav  (string) clave de la pestaña activa en el nav de demo
 */
$activeNav = $activeNav ?? '';

$navItems = [
    'dashboard'    => ['label' => 'Dashboard',       'href' => 'dashboard.php'],
    'pacientes'    => ['label' => 'Pacientes',       'href' => 'pacientes.php'],
    'generador'    => ['label' => 'Generador',       'href' => 'generador.php'],
    'personalizar' => ['label' => 'Personalizar',    'href' => 'personalizar.php'],
    'exportar'     => ['label' => 'Exportar PDF',    'href' => 'exportar.php'],
    'progreso'     => ['label' => 'Progreso',        'href' => 'progreso.php'],
    'alimentos'    => ['label' => 'Alimentos',       'href' => 'alimentos.php'],
    'usuarios'     => ['label' => 'Usuarios',        'href' => 'usuarios.php'],
];
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
</style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-800">

<!-- Barra superior de la app -->
<header class="brand-bg text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 3-4 4.5-4 8a4 4 0 108 0c0-3.5-2.5-5-4-8z" />
        </svg>
      </div>
      <span class="font-bold tracking-tight">SNWO — Smart Nutrition</span>
    </div>
    <div class="text-sm text-emerald-50">Nutricionista: Dra. Gabriela Sagastume</div>
  </div>
</header>

<!-- Nav de demostración (no forma parte del diseño final, es guía de navegación del prototipo) -->
<nav class="bg-white border-b border-gray-200">
  <div class="max-w-6xl mx-auto px-4">
    <div class="flex gap-1 overflow-x-auto text-sm">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= $item['href'] ?>"
           class="whitespace-nowrap px-3 py-2.5 border-b-2 <?= $activeNav === $key ? 'border-[var(--brand)] brand-text font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
          <?= $item['label'] ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-6">
