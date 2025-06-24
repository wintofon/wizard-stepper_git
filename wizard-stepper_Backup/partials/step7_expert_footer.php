<!--// public/partials/footer.php-->
<script src="/project_root_old/wizard/public/assets/js/step7_expert_result.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (typeof initExpertResult === 'function') {
      initExpertResult(<?= json_encode([
        'D' => $diameter,
        'Z' => $flute_count,
        'rpmMin' => $session['rpm_min'],
        'rpmMax' => $session['rpm_max'],
        'frMax' => $session['fr_max'],
        'thickness' => $session['thickness'],
        'ae' => $ae_slot,
        'ap_slot' => $ap_slot,
        'coefSeg' => $coef_seg,
        'Kc11' => $Kc11,
        'mc' => 0.2,
        'alpha' => $alpha,
        'phi' => $phi,
        'eta' => 0.85,
        'mmrBase' => $mmr_base,
        'fzMinEff' => $fz_min0 * $coef_seg,
        'fzMaxEff' => $fz_max0 * $coef_seg,
        'fz0' => $fz0,
        'vc0' => $vc0
      ]) ?>);
    }
  });
</script>