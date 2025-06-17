<?php
/**
 * File: views/select_mode.php
 * ---------------------------------------------------------------
 * Vista para elegir “Manual” o “Automático”
 * ---------------------------------------------------------------
 * Se asume que $csrfToken ha sido definido por index.php antes de hacer include.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seleccionar Modo – Wizard CNC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/wizard.css">
</head>
<body>
<div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">
    <div class="text-center" style="max-width: 400px;">
        <h2 class="display-6 fw-semibold mb-4">¿Cómo querés operar?</h2>
        <form method="post" action="index.php" class="d-grid gap-2" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

            <div class="form-check">
                <input class="form-check-input" type="radio" name="tool_mode" id="mode-manual" value="manual" required>
                <label class="form-check-label" for="mode-manual">Modo Manual</label>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="radio" name="tool_mode" id="mode-auto" value="auto" required>
                <label class="form-check-label" for="mode-auto">Modo Automático</label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Continuar</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
