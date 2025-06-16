window.initStep6 = function () {
  console.debug('📦 initStep6() llamado');

  const holder = document.getElementById('expertParamsHolder');
  if (!holder) return console.error('❌ Falta expertParamsHolder');

  let p;
  try {
    p = JSON.parse(holder.dataset.params);
  } catch (e) {
    console.error('❌ Error al parsear params', e);
    return;
  }

  // Valores iniciales
  let fz = +p.fz0, vc = +p.vc0, passes = 1;
  const realT = +p.ap0 * +p.passes0;
  const maxPass = Math.ceil(realT / +p.ap_slot);

  // Referencias DOM
  const sFz = document.getElementById('sliderFz');
  const sVc = document.getElementById('sliderVc');
  const bFz = document.getElementById('badgeFz');
  const bVc = document.getElementById('badgeVc');
  const btnL = document.getElementById('btnPasadasMenos');
  const btnM = document.getElementById('btnPasadasMas');
  const dPas = document.getElementById('countPasadas');
  const lPas = document.getElementById('textPasadasDetalle');
  const vVc = document.getElementById('valueVc');
  const vFz = document.getElementById('valueFz');
  const vN = document.getElementById('valueN');
  const vVf = document.getElementById('valueVf');
  const vHp = document.getElementById('valueHp');
  const vMmr = document.getElementById('valueMrr');
  const vFc = document.getElementById('valueFc');
  const vW = document.getElementById('valueW');
  const vEta = document.getElementById('valueEta');
  const spin = document.getElementById('paramSpinner');
  const chartCtx = document.getElementById('radarChart')?.getContext('2d');

  // Si ya hay un Chart anterior, lo destruimos para evitar leaks
  if (window._step6RadarChart && typeof window._step6RadarChart.destroy === 'function') {
    window._step6RadarChart.destroy();
  }

  const radar = new Chart(chartCtx, {
    type: 'radar',
    data: {
      labels: ['Vida útil', 'Terminación', 'Potencia'],
      datasets: [{
        data: [0, 0, 0],
        backgroundColor: 'rgba(79,195,247,.35)',
        borderColor: 'rgba(79,195,247,.8)',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      scales: { r: { max: 100, ticks: { stepSize: 20 } } },
      plugins: { legend: { display: false } }
    }
  });
  window._step6RadarChart = radar;

  function animate(el, val, dec = 0) {
    try {
      const c = new CountUp(el, val, { decimalPlaces: dec, duration: 0.4 });
      if (c.error) el.textContent = val.toFixed(dec);
      else c.start();
    } catch {
      el.textContent = val.toFixed(dec);
    }
  }

  async function recalc() {
    spin.classList.add('show');
    const payload = { fzCurrent: fz, vcCurrent: vc, passes, params: p };

    console.debug('📤 Enviando AJAX a step6_ajax.php', payload);

    try {
      const res = await fetch('/wizard-stepper/ajax/step6_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        const msg = await res.text();
        console.error(`❌ Error AJAX ${res.status}:`, msg);
        return;
      }

      const js = await res.json();
      console.debug('📥 Respuesta AJAX:', js);

      animate(vVc, vc, 1);
      animate(vFz, fz, 4);
      animate(vN, js.rpm);
      animate(vVf, js.feed);
      animate(vHp, js.hp, 2);
      animate(vMmr, js.mmr, 2);
      animate(vFc, js.fc, 1);
      animate(vW, js.watts);
      animate(vEta, js.etaPercent);

      if (Array.isArray(js.radar) && js.radar.length === 3) {
        radar.data.datasets[0].data = js.radar;
        radar.update();
      }

    } catch (e) {
      console.error('❌ Excepción durante recalc()', e);
    } finally {
      spin.classList.remove('show');
    }
  }

  // Inicializar sliders
  sFz.min = p.fz_min0;
  sFz.max = p.fz_max0;
  sFz.step = 0.0001;
  sFz.value = fz;
  bFz.textContent = fz.toFixed(4);

  const vcMin = p.vc0 * 0.75;
  const vcMax = p.vc0 * 1.25;
  sVc.min = vcMin.toFixed(1);
  sVc.max = vcMax.toFixed(1);
  sVc.step = 0.1;
  sVc.value = vc;
  bVc.textContent = vc.toFixed(1);

  dPas.textContent = passes;
  lPas.textContent = `${passes} pasadas de ${(realT / passes).toFixed(2)} mm`;

  // Eventos
  sFz.addEventListener('input', () => {
    fz = +sFz.value;
    bFz.textContent = fz.toFixed(4);
    console.debug('🎚️ fz =', fz);
    recalc();
  });

  sVc.addEventListener('input', () => {
    vc = +sVc.value;
    bVc.textContent = vc.toFixed(1);
    console.debug('🎚️ vc =', vc);
    recalc();
  });

  btnL.addEventListener('click', e => {
    e.preventDefault();
    if (passes > 1) {
      passes--;
      dPas.textContent = passes;
      lPas.textContent = `${passes} pasadas de ${(realT / passes).toFixed(2)} mm`;
      recalc();
    }
  });

  btnM.addEventListener('click', e => {
    e.preventDefault();
    if (passes < maxPass) {
      passes++;
      dPas.textContent = passes;
      lPas.textContent = `${passes} pasadas de ${(realT / passes).toFixed(2)} mm`;
      recalc();
    }
  });

  console.debug('▶ Primera ejecución recalc()');
  recalc();
};
