<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CNC Tool Manager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <i class="bi bi-tools"></i> CNC Tool Manager
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Fresas -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navFresas" data-bs-toggle="dropdown">
            <i class="bi bi-drill"></i> Fresas
          </a>
          <ul class="dropdown-menu" aria-labelledby="navFresas">
            <li><a class="dropdown-item" href="tools.php">Todas las fresas</a></li>
            <li><a class="dropdown-item" href="tool_form.php">➕ Nueva fresa</a></li>
          </ul>
        </li>
        <!-- Materiales -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navMateriales" data-bs-toggle="dropdown">
            <i class="bi bi-stack"></i> Materiales
          </a>
          <ul class="dropdown-menu" aria-labelledby="navMateriales">
            <li><a class="dropdown-item" href="materials.php">Lista de materiales</a></li>
            <li><a class="dropdown-item" href="material_form.php">➕ Nuevo material</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="categories.php">Categorías</a></li>
          </ul>
        </li>
        <!-- Máquina -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navMaquina" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> Máquina
          </a>
          <ul class="dropdown-menu" aria-labelledby="navMaquina">
            <li><a class="dropdown-item" href="transmissions.php">Transmisiones</a></li>
            <li><a class="dropdown-item" href="transmission_form.php">➕ Nueva transmisión</a></li>
          </ul>
        </li>
        <!-- Mecanizado -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navMecanizado" data-bs-toggle="dropdown">
            <i class="bi bi-lightning-charge"></i> Mecanizado
          </a>
          <ul class="dropdown-menu" aria-labelledby="navMecanizado">
            <li><a class="dropdown-item" href="machining_types.php">Tipos de mecanizado</a></li>
            <li><a class="dropdown-item" href="machining_types_form.php">➕ Nuevo tipo</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="strategies.php">Estrategias</a></li>
            <li><a class="dropdown-item" href="strategy_form.php">➕ Nueva estrategia</a></li>
          </ul>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto">
        <!-- Carga masiva -->
        <li class="nav-item">
          <a class="nav-link" href="bulk_upload.php">
            <i class="bi bi-file-earmark-arrow-up"></i> Carga masiva
          </a>
        </li>
        <!-- Salir -->
        <li class="nav-item">
          <a class="nav-link" href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid">
