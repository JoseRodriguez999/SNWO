<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar sesión — Smart Nutrition Web Optimizer</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">

  <div class="w-full max-w-md" x-data="{ mostrarPass: false }">

    <div class="flex flex-col items-center mb-6">
      <div class="w-16 h-16 rounded-2xl bg-emerald-600 flex items-center justify-center mb-4 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 3-4 4.5-4 8a4 4 0 108 0c0-3.5-2.5-5-4-8z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-6" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Smart Nutrition</h1>
      <p class="text-gray-500">Web Optimizer — SNWO</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">

      <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

        <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
        <div class="relative mb-4">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </span>
          <input
            type="email" id="correo" name="correo" required autocomplete="username"
            placeholder="nutricionista@smartnutrition.gt"
            class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
            value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo'], ENT_QUOTES, 'UTF-8') : '' ?>"
          >
        </div>

        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
        <div class="relative mb-2">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </span>
          <input
            :type="mostrarPass ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password"
            placeholder="••••••••"
            class="w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
          >
          <button type="button" @click="mostrarPass = !mostrarPass" class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600" tabindex="-1">
            <svg x-show="!mostrarPass" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <svg x-show="mostrarPass" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.293-3.95m3.15-2.1A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.964 9.964 0 01-1.563 3.029M3 3l18 18" />
            </svg>
          </button>
        </div>

        <div class="flex items-center justify-between mb-6">
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="recordarme" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            Recordarme
          </label>
          <a href="recuperar.php" class="text-sm text-emerald-600 hover:underline">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition-colors">
          Iniciar sesión
        </button>
      </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">PHP Sessions + BCRYPT — OWASP 2021</p>
  </div>

</body>
</html>
