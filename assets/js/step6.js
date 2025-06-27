/*  assets/js/step6.module.js
   -------------------------------------------------------------
   Paso 6 – Módulo ESM unificado
   · Exporta   init()            → permite import dinámico
   · Expone   window.step6.init  → compatibilidad vieja
   · Usa      window.step6AjaxUrl (inyectado por PHP) como
     endpoint, con fallback razonable.
   · Todas las rutas, tokens y parámetros provienen de las
     variables globales que ya insertaba el PHP.
----------------------------------------------------------------*/

export function init () {
  /* ----------------------------------------------------------
   * 1. Parámetros inyectados por PHP
   * -------------------------------------------------------- */
  const {
    diameter          : D,
    flute_count       : Z,
    rpm_min           : rpmMin,
    rpm_max           : rpmMax,
    fr_max,
    coef_seg, Kc11, mc, alpha, eta
  } = window.step6Params ?? {};

  const csrfToken = window.step6Csrf ?? '';
  const ajaxURL   = window.step6AjaxUrl ||
                    `${(window.BASE_URL ?? '')}/ajax/step6_ajax_legacy_minimal.php`;

  /* ----------------------------------------------------------
   * 2. Referencias al DOM
   * -------------------------------------------------------- */
  const sFz =  q('sliderFz'),
        sVc =  q('sliderVc'),
        sAe =  q('sliderAe'),
        sP  =  q('sliderPasadas'),
        infoP = q('textPasadasInfo'),
        errBox = q('errorMsg');

  const out = {
    vc :  q('outVc'),
    fz :  q('outFz'),
    hm :  q('outHm'),
    n  :  q('outN'),
    vf :  q('outVf'),
    hp :  q('outHp'),
    mmr:  q('valueMrr'),
    fc :  q('valueFc'),
    w  :  q('valueW'),
    eta:  q('valueEta'),
    ae :  q('outAe'),
    ap :  q('outAp')
  };

  const UI = {
    show (m){ errBox.textContent = m; errBox.style.display='block'; },
    clear(){  errBox.style.display='none'; errBox.textContent='';  },
    fatal(m){ alert(m); }
  };

  /* ----------------------------------------------------------
   * 3. Límites dinámicos de Vc
   * -------------------------------------------------------- */
  const vcMinAllowed = (rpmMin * Math.PI * D) / 1000;
  const vcMaxAllowed = (rpmMax * Math.PI * D) / 1000;
  sVc.min = vcMinAllowed.toFixed(1);
  sVc.max = vcMaxAllowed.toFixed(1);
  if (+sVc.value < vcMinAllowed) sVc.value = vcMinAllowed.toFixed(1);
  if (+sVc.value > vcMaxAllowed) sVc.value = vcMaxAllowed.toFixed(1);

  /* ----------------------------------------------------------
   * 4. Radar Chart (Chart.js)
   * -------------------------------------------------------- */
  let radar = null;
  try{
    const ctx = q('radarChart')?.getContext('2d');
    if (ctx && window.Chart){
      radar = new Chart(ctx,{
        type:'radar',
        data:{ labels:['Vida útil','Terminación','Potencia'],
               datasets:[{ data:[0,0,0],
                           backgroundColor:'rgba(79,195,247,.35)',
                           borderColor:'rgba(79,195,247,.8)', borderWidth:2 }]},
        options:{ scales:{ r:{ max:100,ticks:{ stepSize:20 } } },
                  plugins:{ legend:{ display:false } } }
      });
    }
  }catch(e){ console.warn('[step6] Chart init error',e); }

  /* ----------------------------------------------------------
   * 5. Utilidades
   * -------------------------------------------------------- */
  const thickness = parseFloat(sP.dataset.thickness);

  function q(id){ return document.getElementById(id); }

  function computeFeed(vc,fz){
    const rpm = (vc*1000)/(Math.PI*D);
    return rpm*fz*Z;
  }

  function updatePassSlider(){
    const maxP = Math.ceil(thickness / parseFloat(sAe.value));
    sP.min=1; sP.max=maxP; sP.step=1;
    if (+sP.value>maxP) sP.value=maxP;
  }
  function updatePassInfo(){
    const p = +sP.value;
    infoP.textContent = `${p} pasada${p>1?'s':''} de ${(thickness/p).toFixed(2)} mm`;
  }

  let debounceId;
  const debounce = fn => { clearTimeout(debounceId); debounceId = setTimeout(fn,200); };

  /* ----------------------------------------------------------
   * 6. Listeners de sliders
   * -------------------------------------------------------- */
  function onFzVcChange(){
    UI.clear();
    const vc = +sVc.value, fz = +sFz.value, feed = computeFeed(vc,fz);

    if (feed>fr_max){
      UI.show(`Feedrate supera límite (${fr_max}). Ajustá Vc o fz.`);
      return;
    }
    debounce(recalc);
  }

  [sFz, sVc].forEach(s => s.addEventListener('input', onFzVcChange));
  sAe.addEventListener('input', () => { updatePassSlider(); updatePassInfo(); debounce(recalc); });
  sP .addEventListener('input', () => { updatePassInfo();                 debounce(recalc); });

  /* ----------------------------------------------------------
   * 7. AJAX + pintado de resultados
   * -------------------------------------------------------- */
  async function recalc(){
    const payload = {
      fz: +sFz.value,
      vc: +sVc.value,
      ae: +sAe.value,
      passes: +sP.value,
      thickness,
      D, Z,
      params:{ fr_max, coef_seg, Kc11, mc, alpha, eta }
    };

    try{
      const r = await fetch(ajaxURL,{
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-Token': csrfToken
        },
        body:JSON.stringify(payload),
        cache:'no-store',
        credentials:'same-origin'
      });
      if(!r.ok) throw new Error(`HTTP ${r.status}`);
      const j = await r.json();
      if(!j.success) throw new Error(j.error||'Error');

      paint(j.data);
    }catch(e){
      UI.show(e.message);
      console.error('[step6] AJAX fail',e);
    }
  }

  function paint(d){
    try{
      out.vc .textContent = `${d.vc} m/min`;
      out.fz .textContent = `${d.fz} mm/tooth`;
      out.hm .textContent = `${d.hm} mm`;
      out.n  .textContent = d.n;
      out.vf .textContent = `${d.vf} mm/min`;
      out.hp .textContent = `${d.hp} HP`;
      out.mmr.textContent = d.mmr;
      out.fc .textContent = d.fc;
      out.w  .textContent = d.watts;
      out.eta.textContent = d.etaPercent;
      out.ae .textContent = d.ae.toFixed(2);
      out.ap .textContent = d.ap.toFixed(3);

      if(radar && Array.isArray(d.radar)){
        radar.data.datasets[0].data = d.radar;
        radar.update();
      }
    }catch(e){ console.error('[step6] paint()',e); }
  }

  /* ----------------------------------------------------------
   * 8. Kick-off
   * -------------------------------------------------------- */
  updatePassSlider();
  updatePassInfo();
  recalc();
  console.info('%c[step6] init OK','color:#4fc3f7;font-weight:700');
}

/* ----------------------------------------------------------------
 * Compatibilidad legacy – deja disponible window.step6.init
 * ----------------------------------------------------------------*/
if (typeof window !== 'undefined'){
  window.step6 = window.step6 || {};
  window.step6.init = init;
}
