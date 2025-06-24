<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paso 3 – Modo de Selección</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="/assets/css/tailwind.output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
  <div class="bg-white max-w-xl w-full p-8 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Paso 3: ¿Cómo querés elegir tu fresa?</h2>

    <?php if ($errors): ?>
      <div class="bg-red-100 text-red-700 p-4 mb-4 rounded-md border border-red-300">
        <ul class="list-disc pl-5 space-y-1">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
          type="submit"
          name="tool_mode"
          value="manual"
          class="bg-white border-2 border-blue-500 text-blue-700 font-semibold py-4 px-6 rounded-lg hover:bg-blue-50 transition-all text-center"
        >
          Manual<br>
          <span class="text-sm text-gray-600">(quiero seleccionar la fresa)</span>
        </button>

        <button
          type="submit"
          name="tool_mode"
          value="auto"
          class="bg-white border-2 border-green-500 text-green-700 font-semibold py-4 px-6 rounded-lg hover:bg-green-50 transition-all text-center"
        >
          Automático<br>
          <span class="text-sm text-gray-600">(quiero recomendación de fresa)</span>
        </button>
      </div>
    </form>
  </div>
</body>
</html>
