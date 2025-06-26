/* STEP6-AJAX */
(function (w, d) {
  'use strict';
  var DEBUG = w.DEBUG === true;
  var ttl = 600000; // 10 min
  function dbg() { if (DEBUG) console.log.apply(console, arguments); }
  function grp(t, fn) { if (!DEBUG) return fn(); console.group(t); try { return fn(); } finally { console.groupEnd(); } }
  function q(id) { return d.getElementById(id); }

  var step = d.querySelector('.step6');
  var errBox = q('errorMsg');
  var sliders = { fz: q('sliderFz'), vc: q('sliderVc'), ae: q('sliderAe'), p: q('sliderPasadas') };
  var out = { rpm: q('outN'), feed: q('outVf'), vc: q('outVc'), fz: q('outFz'), hm: q('outHm'), hp: q('outHp'), mmr: q('valueMrr'), fc: q('valueFc'), w: q('valueW'), eta: q('valueEta'), ae: q('outAe'), ap: q('outAp') };
  var radar = null;
  try {
    var ctx = q('radarChart');
    if (w.Chart && ctx) {
      radar = new w.Chart(ctx.getContext('2d'), {
        type: 'radar',
        data: { labels: ['Vida útil', 'Terminación', 'Potencia'], datasets: [{ data: [0, 0, 0], backgroundColor: 'rgba(79,195,247,0.35)', borderColor: 'rgba(79,195,247,0.8)', borderWidth: 2 }] },
        options: { scales: { r: { max: 100, ticks: { stepSize: 20 } } }, plugins: { legend: { display: false } } }
      });
    }
  } catch (e) { dbg('chart fail', e); }

  w.addEventListener('unload', function () { try { Object.keys(sessionStorage).forEach(function (k) { if (k.indexOf('step6:') === 0) sessionStorage.removeItem(k); }); } catch (e) {} });

  function hash(s) { var h = 0, i; for (i = 0; i < s.length; i++) { h = (h << 5) - h + s.charCodeAt(i); h |= 0; } return h.toString(36); }
  function showErr(m) { if (errBox) errBox.textContent = m; }
  function clearErr() { if (errBox) errBox.textContent = ''; }
  function setLoading(on) { if (step) step.classList.toggle('loading', !!on); Object.keys(sliders).forEach(function (k) { var s = sliders[k]; if (s) s.disabled = !!on; }); }
  function paint(d) {
    try {
      if (out.vc) out.vc.textContent = d.vc + ' m/min';
      if (out.fz) out.fz.textContent = d.fz + ' mm/tooth';
      if (out.rpm) out.rpm.textContent = d.n;
      if (out.feed) out.feed.textContent = d.vf + ' mm/min';
      if (out.hm) out.hm.textContent = d.hm + ' mm';
      if (out.hp) out.hp.textContent = d.hp + ' HP';
      if (out.mmr) out.mmr.textContent = d.mmr;
      if (out.fc) out.fc.textContent = d.fc;
      if (out.w) out.w.textContent = d.watts;
      if (out.eta) out.eta.textContent = d.etaPercent;
      if (out.ae) out.ae.textContent = d.ae.toFixed(2);
      if (out.ap) out.ap.textContent = d.ap.toFixed(3);
      if (radar && Array.isArray(d.radar)) { radar.data.datasets[0].data = d.radar; radar.update(); }
    } catch (e) { dbg('paint err', e); }
  }
  function getPayload () {
    const base = window.step6Params || {};
    return {
      /* sliders */
      fz:     parseFloat(sliders.fz.value),
      vc:     parseFloat(sliders.vc.value),
      ae:     parseFloat(sliders.ae.value),
      passes: parseInt(sliders.p.value, 10),
      /* extras requeridos por el backend */
      thickness: base.thickness,
      D:         base.D,
      Z:         base.Z,
      params: {
        fr_max:   base.fr_max,
        coef_seg: base.coef_seg,
        Kc11:     base.Kc11,
        mc:       base.mc,
        alpha:    base.alpha,
        eta:      base.eta
      }
    };
  }
  function fetchData(body, key, retry) {
    var ctrl = new AbortController();
    var to = setTimeout(function () { ctrl.abort(); }, 8000);
    var t0 = performance.now();
    var url = w.step6AjaxUrl || 'ajax/step6_ajax_legacy_minimal.php';
    return fetch(url, {
      method: 'POST',
      body: body,
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': w.step6Csrf, 'Accept': '*/*' },
      credentials: 'same-origin',
      cache: 'no-store',
      signal: ctrl.signal
    }).then(function (r) {
      clearTimeout(to);
      grp('response', function () { dbg('status', r.status); });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }).then(function (j) {
      if (!j.success) throw new Error(j.error || 'Error');
      sessionStorage.setItem(key, JSON.stringify({ ts: Date.now(), data: j.data }));
      paint(j.data);
    }).catch(function (e) {
      if (retry && (e.name === 'AbortError' || e.message === 'Failed to fetch')) return fetchData(body, key, false);
      showErr(e.message);
    }).finally(function () { setLoading(false); dbg('ms', (performance.now() - t0).toFixed(1)); });
  }
  function recalc() {
    try {
      clearErr();
      var p = getPayload();
      var body = JSON.stringify(p);
      var key = 'step6:' + hash(body);
      var item;
      try { item = JSON.parse(sessionStorage.getItem(key) || 'null'); } catch (e) { item = null; }
      if (item && Date.now() - item.ts < ttl) paint(item.data);
      setLoading(true);
      grp('request', function () { dbg(p); });
      fetchData(body, key, true);
    } catch (e) { showErr(e.message); setLoading(false); }
  }
  w.initStep6 = function () {
    try {
      ['fz', 'vc', 'ae', 'p'].forEach(function (k) { if (sliders[k]) sliders[k].addEventListener('input', recalc); });
      recalc();
    } catch (e) { showErr(e.message); }
  };
})(window, document);
