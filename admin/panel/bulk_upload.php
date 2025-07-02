<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
include 'header.php';

// Mensajes de éxito o error
if (isset($_GET['bulk_ok'])) {
    $ok = (int) $_GET['bulk_ok'];
    $dup = (int) ($_GET['bulk_dup'] ?? 0);
    echo "<div class='alert alert-success'>
            ✅ Se importaron <strong>{$ok}</strong> herramientas.
            " . ($dup ? "⚠️ Se omitieron {$dup} duplicados." : "") . "
          </div>";
} elseif (isset($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error']);
    echo "<div class='alert alert-danger'>❌ {$msg}</div>";
}
?>

<h2>Carga Masiva de Herramientas</h2>
<p>Sube un archivo <strong>CSV</strong> con delimitador <code>;</code> y  
cabecera EXACTA (en este orden):</p>
<pre>series_id;tool_code;name;flute_count;diameter_mm;
shank_diameter_mm;flute_length_mm;cut_length_mm;full_length_mm;
rack_angle;helix;conical_angle;radius;
tool_type;made_in;material;coated;notes;
material_id;vc_m_min;fz_min_mm;fz_max_mm;ap_slot_mm;ae_slot_mm</pre>

<form action="bulk_process.php" method="POST" enctype="multipart/form-data" class="mt-4">
  <div class="mb-3">
    <label for="brand_id" class="form-label">Marca</label>
    <select name="brand_id" id="brand_id" class="form-select" required>
      <option value="">-- Selecciona una marca --</option>
      <?php
      $stmt = $pdo->query("SELECT id,name FROM brands ORDER BY name");
      while ($b = $stmt->fetch()) {
          echo "<option value=\"{$b['id']}\">".htmlspecialchars($b['name'])."</option>";
      }
      ?>
    </select>
  </div>

  <div class="mb-3">
    <label for="csv" class="form-label">Archivo CSV</label>
    <input type="file" name="csv" id="csv" class="form-control" accept=".csv" required>
  </div>

  <button type="submit" class="btn btn-primary">📤 Subir CSV</button>
  <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php include 'footer.php'; ?>
