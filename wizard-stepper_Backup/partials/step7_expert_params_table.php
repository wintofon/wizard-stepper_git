<!--// public/partials/params_table.php-->
<hr />
<b>Parámetros recomendados de corte:</b>
<table class="table table-bordered table-sm w-auto">
  <tr>
    <th>Vc</th><td><?= htmlspecialchars(number_format($vc0,1)) ?> m/min</td>
    <th>Vc </th>

  </tr>
  <tr>
    <th>fz</th><td id="fz_val_display"><?= htmlspecialchars(number_format($fz0,4)) ?> mm/diente</td>
    <th>fz min - max</th>
    <td><?= number_format($fz_min0 * $coef_seg, 4) ?> – <?= number_format($fz_max0 * $coef_seg, 4) ?> mm/diente</td>
  </tr>
  <tr>
    <th>ap</th><td id="ap_val_display"><?= htmlspecialchars(number_format($ap0,3)) ?> mm</td>
    <th>ap min-max</th><td><?= number_format($ap_slot,3) ?> – <?= number_format($ap_max,3) ?> mm</td>
  </tr>
  <tr>
    <th>ae</th><td><?= htmlspecialchars(number_format($ae_slot,3)) ?> mm</td>
    <th>ae min-max</th><td><?= number_format($ae_slot,3) ?> – <?= number_format($ae_max,3) ?> mm</td>
  </tr>
  <tr>
    <th>RPM</th><td colspan="3" id="rpm_val_display"><?= htmlspecialchars($rpm0) ?> (<?= htmlspecialchars($session['rpm_min']) ?> – <?= htmlspecialchars($session['rpm_max']) ?>)</td>
  </tr>
  <tr>
    <th>Feedrate</th><td colspan="3" id="feed_val_display"><?= htmlspecialchars(round($feed0)) ?> mm/min</td>
  </tr>

<div id="datos_extra" class="mt-3 small text-secondary" style="white-space: pre-line; font-family: monospace;"></div>
<div id="vz_diente" class="small text-muted mt-2">–</div>


</table>