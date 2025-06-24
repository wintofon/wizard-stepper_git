<!--// public/partials/tool_ficha.php-->
<div class="col-md-3 text-center">
  <div class="img-box">
    <?php if (!empty($img_url)): ?>
      <img src="<?= htmlspecialchars($img_url) ?>" class="img-fluid mb-1 tool-img" alt="Imagen herramienta principal" />
    <?php endif; ?>
  </div>
  <div class="img-box">
    <?php if (!empty($img_url2)): ?>
      <img src="<?= htmlspecialchars($img_url2) ?>" class="img-fluid tool-img" alt="Detalle herramienta" />
    <?php endif; ?>
  </div>
  <div class="ficha-titulo">
    <?= htmlspecialchars($brand ?? '-') ?><br />
    <?= htmlspecialchars($serie ?? '-') ?><br />
    <span style="font-size:0.9em"><?= htmlspecialchars($tool_code ?? '-') ?></span>
  </div>
  <table class="table table-sm mb-1">
    <tr>
      <th>Ø d1</th><td><?= htmlspecialchars($diameter ?? '-') ?> mm</td>
      <th>d3 (mango)</th><td><?= htmlspecialchars($neck_diameter ?? '-') ?> mm</td>
    </tr>
    <tr>
      <th>l1 (long. total)</th><td><?= htmlspecialchars($total_length ?? '-') ?> mm</td>
      <th>l2 (long. corte)</th><td><?= htmlspecialchars($cut_length ?? '-') ?> mm</td>
    </tr>
    <tr>
      <th>l3 (long. cuello)</th><td><?= htmlspecialchars($neck_length ?? '-') ?> mm</td>
      <th>Z (dientes)</th><td><?= htmlspecialchars($flute_count ?? '-') ?></td>
    </tr>
    <tr>
      <th>Recubrimiento</th><td><?= htmlspecialchars($coating ?? '-') ?></td>
      <th>Material fresa</th><td><?= htmlspecialchars($cutting_material ?? '-') ?></td>
    </tr>
  </table>
</div>