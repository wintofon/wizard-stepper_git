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
    <title>Seleccionar Modo – Wizard CNC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS (opcional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa; 
            color: #333;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }
        .container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .btn-submit {
            width: 100%;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>¿Cómo querés operar?</h2>
    <form method="post" action="index.php" novalidate>
        <!-- Token CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

        <div class="form-check mb-2">
            <input 
                class="form-check-input" 
                type="radio" 
                name="tool_mode" 
                id="mode-manual" 
                value="manual"
                required
            >
            <label class="form-check-label" for="mode-manual">
                Modo Manual
            </label>
        </div>

        <div class="form-check mb-2">
            <input 
                class="form-check-input" 
                type="radio" 
                name="tool_mode" 
                id="mode-auto" 
                value="auto"
                required
            >
            <label class="form-check-label" for="mode-auto">
                Modo Automático
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-submit">
            Continuar
        </button>
    </form>
</div>

<!-- Bootstrap 5 JS + Popper (opcional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
