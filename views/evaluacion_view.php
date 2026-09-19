<?php include __DIR__ . '/../includes/header.php'; ?>

<?php
/** Imprime un valor o un guion si es null, con sufijo opcional */
function v($valor, string $sufijo = ''): string
{
    if ($valor === null || $valor === '') return '—';
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8') . $sufijo;
}

$resultado = $datosCompletos['evaluacion'] ?? null;
$circun    = $datosCompletos['circunferencias'] ?? null;
$bio       = $datosCompletos['bioimpedancia'] ?? null;
$habitos   = $datosCompletos['habitos'] ?? null;
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="brand-bg text-white px-5 py-4 flex items-center justify-between">
    <h1 class="font-semibold">Evaluación antropométrica — <?= htmlspecialchars($paciente['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
    <span class="text-xs text-emerald-50"><?= $edad ?> años · <?= $paciente['sexo'] === 'M' ? 'Masculino' : 'Femenino' ?></span>
  </div>

  <?php if ($resultado): ?>
    <!-- ================= VISTA DE RESULTADOS ================= -->
    <div class="p-5">
      <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 mb-6">
        ✓ Evaluación registrada correctamente y guardada en el expediente del paciente.
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">IMC</p>
          <p class="text-2xl font-bold text-gray-900"><?= number_format((float)$resultado['imc'], 1) ?></p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">GET objetivo</p>
          <p class="text-2xl font-bold text-gray-900"><?= number_format((float)$resultado['get_calculado'], 0) ?> kcal</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">% Grasa (pliegues)</p>
          <p class="text-2xl font-bold text-gray-900"><?= number_format((float)$resultado['porcentaje_grasa'], 1) ?>%</p>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 text-center">
          <p class="text-sm text-gray-500 mb-1">Masa magra</p>
          <p class="text-2xl font-bold text-gray-900"><?= number_format((float)$resultado['masa_magra_kg'], 1) ?> kg</p>
        </div>
      </div>

      <?php if ($circun): ?>
      <h2 class="font-semibold text-gray-800 mb-3">Circunferencias corporales</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-sm">
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Cuello</span><span class="font-semibold"><?= v($circun['cuello_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Brazo relajado</span><span class="font-semibold"><?= v($circun['brazo_relajado_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Brazo contraído</span><span class="font-semibold"><?= v($circun['brazo_contraido_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Antebrazo</span><span class="font-semibold"><?= v($circun['antebrazo_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Cintura</span><span class="font-semibold"><?= v($circun['cintura_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Cadera</span><span class="font-semibold"><?= v($circun['cadera_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Muslo</span><span class="font-semibold"><?= v($circun['muslo_cm'], ' cm') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Pantorrilla</span><span class="font-semibold"><?= v($circun['pantorrilla_cm'], ' cm') ?></span></div>
      </div>
      <?php endif; ?>

      <?php if ($bio): ?>
      <h2 class="font-semibold text-gray-800 mb-3">Datos de bioimpedancia</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8 text-sm">
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Edad metabólica</span><span class="font-semibold"><?= v($bio['edad_metabolica'], ' años') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">% Grasa (bioimp.)</span><span class="font-semibold"><?= v($bio['porcentaje_grasa'], '%') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">% Agua corporal</span><span class="font-semibold"><?= v($bio['porcentaje_agua'], '%') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Densidad ósea</span><span class="font-semibold"><?= v($bio['densidad_osea_kg'], ' kg') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Grasa visceral</span><span class="font-semibold"><?= v($bio['grasa_visceral']) ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Masa muscular</span><span class="font-semibold"><?= v($bio['masa_muscular_kg'], ' kg') ?></span></div>
      </div>
      <?php endif; ?>

      <?php if ($habitos): ?>
      <h2 class="font-semibold text-gray-800 mb-3">Hábitos alimenticios y estilo de vida</h2>
      <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Comidas al día</span><span class="font-semibold"><?= v($habitos['comidas_por_dia']) ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Porciones fruta/verdura</span><span class="font-semibold"><?= v($habitos['porciones_frutas_verduras']) ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Agua al día</span><span class="font-semibold"><?= v($habitos['consumo_agua_litros'], ' L') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Comida rápida</span><span class="font-semibold capitalize"><?= v($habitos['consumo_comida_rapida']) ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Alcohol</span><span class="font-semibold"><?= $habitos['consume_alcohol'] === null ? '—' : ($habitos['consume_alcohol'] ? 'Sí (' . v($habitos['frecuencia_alcohol']) . ')' : 'No') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Fuma</span><span class="font-semibold"><?= $habitos['fuma'] === null ? '—' : ($habitos['fuma'] ? 'Sí' : 'No') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Ejercicio</span><span class="font-semibold"><?= $habitos['realiza_ejercicio'] === null ? '—' : ($habitos['realiza_ejercicio'] ? v($habitos['frecuencia_ejercicio_semana']) . 'x/sem — ' . v($habitos['tipo_ejercicio']) : 'No realiza') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Horas de sueño</span><span class="font-semibold"><?= v($habitos['horas_sueno'], ' h') ?></span></div>
        <div class="flex justify-between rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Nivel de estrés</span><span class="font-semibold capitalize"><?= v($habitos['nivel_estres']) ?></span></div>
        <?php if ($habitos['alergias_alimentarias']): ?>
          <div class="col-span-2 rounded-lg border border-gray-200 px-3 py-2"><span class="text-gray-500">Alergias/intolerancias: </span><span class="font-semibold"><?= v($habitos['alergias_alimentarias']) ?></span></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="flex gap-3 flex-wrap">
        <a href="progreso.php?id=<?= $idPaciente ?>" class="border border-emerald-300 bg-emerald-50 brand-text text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-emerald-100">Ver progreso</a>
        <a href="generador.php?paciente=<?= urlencode($paciente['nombre']) ?>" class="brand-bg text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:opacity-90">Generar plan con este GET</a>
        <a href="evaluacion.php?id=<?= $idPaciente ?>" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Nueva evaluación</a>
        <a href="pacientes.php" class="border border-gray-300 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-gray-50">Volver a pacientes</a>
      </div>
    </div>

  <?php else: ?>
    <!-- ================= FORMULARIO DE MEDICIONES ================= -->
    <form method="POST" action="evaluacion.php?id=<?= $idPaciente ?>" class="p-5 space-y-8">
      <input type="hidden" name="accion" value="registrar_evaluacion">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <?php if ($error): ?>
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <div>
        <h2 class="font-semibold text-gray-800 mb-3">Medidas corporales</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg) *</label>
            <input type="number" step="0.1" name="peso_kg" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Talla (cm) *</label>
            <input type="number" step="0.1" name="talla_cm" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
      </div>

      <div>
        <h2 class="font-semibold text-gray-800 mb-1">Pliegues cutáneos (mm)</h2>
        <p class="text-xs text-gray-400 mb-3">Usados para calcular % de grasa corporal (Jackson-Pollock 3 pliegues + Siri)</p>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tricipital *</label>
            <input type="number" step="0.1" name="pliegue_tricipital" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subescapular *</label>
            <input type="number" step="0.1" name="pliegue_subescapular" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Abdominal *</label>
            <input type="number" step="0.1" name="pliegue_abdominal" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
      </div>

      <div>
        <h2 class="font-semibold text-gray-800 mb-1">Circunferencias corporales (cm)</h2>
        <p class="text-xs text-gray-400 mb-3">Opcional — llena solo las que midas</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <?php foreach ([
              'cuello_cm' => 'Cuello', 'brazo_relajado_cm' => 'Brazo relajado', 'brazo_contraido_cm' => 'Brazo contraído', 'antebrazo_cm' => 'Antebrazo',
              'cintura_cm' => 'Cintura', 'cadera_cm' => 'Cadera', 'muslo_cm' => 'Muslo', 'pantorrilla_cm' => 'Pantorrilla',
          ] as $campo => $etiqueta): ?>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"><?= $etiqueta ?></label>
              <input type="number" step="0.1" name="<?= $campo ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div>
        <h2 class="font-semibold text-gray-800 mb-1">Datos de bioimpedancia</h2>
        <p class="text-xs text-gray-400 mb-3">Opcional — si mediste con báscula/analizador de bioimpedancia</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Edad metabólica (años)</label>
            <input type="number" name="edad_metabolica" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">% Grasa (bioimpedancia)</label>
            <input type="number" step="0.1" name="bio_porcentaje_grasa" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">% Agua corporal</label>
            <input type="number" step="0.1" name="bio_porcentaje_agua" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Densidad ósea (kg)</label>
            <input type="number" step="0.01" name="densidad_osea_kg" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Grasa visceral (nivel)</label>
            <input type="number" name="grasa_visceral" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Masa muscular (kg)</label>
            <input type="number" step="0.1" name="masa_muscular_kg" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
      </div>

      <div>
        <h2 class="font-semibold text-gray-800 mb-1">Cuestionario de hábitos alimenticios y estilo de vida</h2>
        <p class="text-xs text-gray-400 mb-3">Opcional — ayuda a personalizar mejor el plan</p>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Comidas al día</label>
            <input type="number" name="comidas_por_dia" min="1" max="10" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Porciones fruta/verdura al día</label>
            <input type="number" name="porciones_frutas_verduras" min="0" max="15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Agua al día (litros)</label>
            <input type="number" step="0.1" name="consumo_agua_litros" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Consumo de comida rápida</label>
            <select name="consumo_comida_rapida" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Sin especificar</option>
              <option value="nunca">Nunca</option>
              <option value="ocasional">Ocasional</option>
              <option value="frecuente">Frecuente</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Horas de sueño</label>
            <input type="number" step="0.5" name="horas_sueno" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de estrés</label>
            <select name="nivel_estres" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">Sin especificar</option>
              <option value="bajo">Bajo</option>
              <option value="medio">Medio</option>
              <option value="alto">Alto</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" name="consume_alcohol" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Consume alcohol
            </label>
          </div>
          <div>
            <input type="text" name="frecuencia_alcohol" placeholder="Frecuencia (ej. 1 vez/semana)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" name="fuma" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Fuma
            </label>
          </div>
          <div></div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" name="realiza_ejercicio" value="1" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"> Realiza ejercicio
            </label>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <input type="number" name="frecuencia_ejercicio_semana" min="0" max="14" placeholder="Veces/semana" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <input type="text" name="tipo_ejercicio" placeholder="Tipo (ej. pesas, cardio)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Alergias / intolerancias alimentarias</label>
          <textarea name="alergias_alimentarias" rows="2" placeholder="Ej. lactosa, mariscos, gluten..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones sobre hábitos y estilo de vida</label>
          <textarea name="observaciones_habitos" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
        </div>
      </div>

      <div>
        <h2 class="font-semibold text-gray-800 mb-3">Cálculo del GET</h2>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fórmula a utilizar *</label>
            <select name="formula_utilizada" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="Mifflin-St Jeor" selected>Mifflin-St Jeor (predeterminada)</option>
              <option value="Harris-Benedict">Harris-Benedict</option>
              <option value="OMS">OMS / FAO</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de actividad física *</label>
            <select name="nivel_actividad" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <?php foreach ($factoresActividad as $nivel => $factor): ?>
                <option value="<?= $nivel ?>" <?= $nivel === $paciente['nivel_actividad'] ? 'selected' : '' ?>><?= ucfirst($nivel) ?> (×<?= $factor ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones clínicas</label>
        <textarea name="observaciones" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit" class="brand-bg text-white font-semibold px-5 py-2.5 rounded-lg hover:opacity-90">Calcular y guardar evaluación</button>
        <a href="pacientes.php" class="border border-gray-300 text-gray-700 font-semibold px-5 py-2.5 rounded-lg hover:bg-gray-50 inline-block">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
