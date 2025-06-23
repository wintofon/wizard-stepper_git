/*
 * File: step7_expert_calculator.js
 * Main responsibility: Part of the CNC Wizard Stepper.
 * Related files: See others in this project.
 * TODO: Extend documentation.
 */
/**
 * step7_expert_calculator.js
 * Calculadora CNC interactiva completa y didáctica.
 * Muestra todos los parámetros técnicos con cálculos explicativos.
 */
/* global module */

function initExpertResult(P) {
  const L = window.Logger;
  if (!P || typeof P !== 'object') {
    const msg = '❌ Error: No se recibió el objeto de parámetros (P) o no es válido.';
    L.error(msg);
    return;
  }

  const required = ['vc0', 'fzMinEff', 'fzMaxEff', 'D', 'Z', 'thickness', 'ae', 'rpmMin', 'rpmMax', 'frMax', 'Kc11', 'mc', 'eta', 'strategy'];
  const missing = required.filter(k => P[k] === undefined);
  if (missing.length > 0) {
    const msg = '❌ Faltan datos en el objeto P: ' + missing.join(', ');
    L.error(msg);
    return;
  }

  const domMap = {
    fz_slider: document.getElementById('fz_slider'),
    vc_slider: document.getElementById('vc_slider'),
    ae_slider: document.getElementById('ae_slider'),
    pass_slider: document.getElementById('pass_slider'),
    fz_value: document.getElementById('fz_value'),
    vc_value: document.getElementById('vc_value'),
    ae_value: document.getElementById('ae_value'),
    pass_value: document.getElementById('pass_value'),
    fz_min_label: document.getElementById('fz_min_label'),
    fz_max_label: document.getElementById('fz_max_label'),
    vc_min_label: document.getElementById('vc_min_label'),
    vc_max_label: document.getElementById('vc_max_label'),
    material_thickness: document.getElementById('material_thickness'),
    ae_notice: document.getElementById('ae_notice'),
    warning_feed: document.getElementById('warning-feed'),
    warning_rpm_low: document.getElementById('warning-rpm-low'),
    warning_rpm_high: document.getElementById('warning-rpm-high'),
    datos_extra: document.getElementById('datos_extra')
  };

  const missingDom = Object.entries(domMap)
    .filter(([, el]) => !el)
    .map(([id]) => id);
  if (missingDom.length > 0) {
    const msg = '❌ Elementos del DOM faltantes: ' + missingDom.join(', ');
    L.error(msg);
    return;
  }

  const {
    fz_slider: fzS, vc_slider: vcS, ae_slider: aeS, pass_slider: passS,
    fz_value: fzV, vc_value: vcV, ae_value: aeV, pass_value: passV,
    fz_min_label: fzMinL, fz_max_label: fzMaxL, vc_min_label: vcMinL, vc_max_label: vcMaxL,
    material_thickness: matThick, ae_notice: aeMsg,
    warning_feed: warnF, warning_rpm_low: warnL, warning_rpm_high: warnH,
    datos_extra: datosExtra
  } = domMap;

  const seg = P.coefSeg || P.coefSeguridad || 1;
  const D = P.D, Z = P.Z;
  const apBase = P.thickness;
  const fzMin0 = P.fzMinEff, fzMax0 = P.fzMaxEff;
  const vcBase = +P.vc0;
  let aeReal = P.ae * D;

  const rpmBase = (vcBase * 1000) / (Math.PI * D);
  const feedBase = rpmBase * fzMin0 * Z;
  const mmrBase = (apBase * aeReal * feedBase) / 1000;

  function enhanceSlider(slider) {
    const wrap = slider.closest('.slider-wrap');
    if (!wrap) return;
    const bubble = wrap.querySelector('.slider-bubble');
    const min = parseFloat(slider.min || 0);
    const max = parseFloat(slider.max || 1);
    const step = parseFloat(slider.step || 1);

    function update(val) {
      const pct = ((val - min) / (max - min)) * 100;
      wrap.style.setProperty('--val', pct);
      if (bubble) bubble.textContent = parseFloat(val).toFixed(3);
    }

    slider.addEventListener('input', e => {
      update(parseFloat(e.target.value));
    });

    slider.addEventListener('keydown', e => {
      let delta = 0;
      if (e.key === 'ArrowRight' || e.key === 'ArrowUp') delta = step;
      else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') delta = -step;
      else if (e.key === 'PageUp') delta = step * 10;
      else if (e.key === 'PageDown') delta = -step * 10;
      if (delta !== 0) {
        e.preventDefault();
        let newVal = parseFloat(slider.value) + delta;
        newVal = Math.min(max, Math.max(min, newVal));
        slider.value = newVal;
        slider.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });

    wrap.style.setProperty('--step-pct', (step / (max - min)) * 100);
    update(parseFloat(slider.value));
  }

  function initSliders() {
    vcS.step = 0.1;
    vcS.min = +(vcBase * 0.75).toFixed(1);
    vcS.max = +(vcBase * 1.25).toFixed(1);
    vcS.value = vcBase;
    vcV.textContent = `${vcBase} m/min`;
    vcMinL.textContent = `${vcS.min} m/min`;
    vcMaxL.textContent = `${vcS.max} m/min`;

    fzS.step = 0.0001;

    passS.min = 1;
    passS.max = 10;
    passS.value = 1;
    matThick.textContent = `${apBase.toFixed(2)} mm`;

    if (P.strategy === 'slot') {
      aeS.disabled = false;
      aeS.min = D;
      aeS.max = D;
      aeS.value = D;
      aeMsg.textContent = `⚠️ Ranurado (slot): ae = D`;
    } else {
      aeS.disabled = false;
      aeS.min = 0.1;
      aeS.max = D;
      aeS.step = 0.01;
      aeS.value = +(P.ae * D).toFixed(2);
      aeMsg.textContent = `Perfilado: ae libre entre 0.1 y D.`;
    }

    aeV.textContent = `${aeS.value} mm`;
    aeReal = +aeS.value;
    updateFzRange();
    calculateAll();
    enhanceSlider(fzS);
    enhanceSlider(vcS);
  }

  function updateFzRange() {
    const EP = +passS.value;
    const apAct = apBase / EP;
    const minFz = +(fzMin0 * apBase / apAct * seg).toFixed(4);
    const maxFz = +(fzMax0 * apBase / apAct * seg).toFixed(4);
    fzS.min = minFz;
    fzS.max = maxFz;
    fzMinL.textContent = `${minFz} mm`;
    fzMaxL.textContent = `${maxFz} mm`;
    const vc = +vcS.value;
    const rpm = (vc * 1000) / (Math.PI * D);
    const fzIdeal = (mmrBase * 1000) / (apAct * aeReal * rpm * Z);
    fzS.value = Math.min(Math.max(fzIdeal, minFz), maxFz).toFixed(4);
    fzV.textContent = `${fzS.value} mm`;
  }

  function displayValues() {
    vcV.textContent = `${vcS.value} m/min`;
    fzV.textContent = `${fzS.value} mm`;
    aeV.textContent = `${aeS.value} mm`;
    const paso = apBase / +passS.value;
    passV.textContent = `${passS.value} pasada${passS.value > 1 ? 's' : ''} de ${paso.toFixed(2)} mm`;
  }

  function calculateAll() {
    const fz = +fzS.value,
          vc = +vcS.value,
          EP = +passS.value;
    const ap = apBase / EP;
    aeReal = +aeS.value;
    const rpm = (vc * 1000) / (Math.PI * D);
    const rpmClamped = Math.max(P.rpmMin, Math.min(P.rpmMax, rpm));
    let feed = rpmClamped * fz * Z;
    const feedLimited = feed > P.frMax;
    if (feedLimited) feed = P.frMax;
    const mmr = (ap * aeReal * feed) / 1000;
    const Fc = P.Kc11 * Math.pow(ap, 1 - P.mc) * Math.pow(aeReal, P.mc);
    const watts = (Fc * fz * Z * rpmClamped) / (60 * P.eta);
    const hp = watts / 745.7;
    const vz = +(ap * aeReal * fz).toFixed(3);

    if (warnF) warnF.style.display = feed > P.frMax ? 'block' : 'none';
    if (warnL) warnL.style.display = rpm < P.rpmMin ? 'block' : 'none';
    if (warnH) warnH.style.display = rpm > P.rpmMax ? 'block' : 'none';

    L.log([
      `MMR: ${mmr.toFixed(2)} mm³/min`,
      `RPM: ${Math.round(rpmClamped)}`,
      `Feed: ${Math.round(feed)} mm/min`,
      `Potencia: ${watts.toFixed(1)} W (${hp.toFixed(2)} HP)`
    ].join('\n'));

    const extra = [
      `↕ ap (profundidad): ${ap.toFixed(2)} mm`,
      `↔ ae (ancho): ${aeReal.toFixed(2)} mm`,
      `⛭ fz: ${fz.toFixed(4)} mm/diente`,
      `🦷 Z filos: ${Z}`,
      `Vc: ${vc} m/min`,
      `RPM efectiva: ${Math.round(rpmClamped)}`,
      `Feed efectiva: ${Math.round(feed)} mm/min`,
      `Vz (por diente): ${vz} mm³`,
      `Fc: ${Fc.toFixed(1)} N`,
      `MMR: ${mmr.toFixed(2)} mm³/min`,
      `η eficiencia: ${P.eta}`,
      `Potencia: ${watts.toFixed(1)} W = ${hp.toFixed(2)} HP`
    ].join('\n');

    if (datosExtra) datosExtra.textContent = extra;
    displayValues();
  }

  vcS.addEventListener('input', calculateAll);
  fzS.addEventListener('input', calculateAll);
  aeS.addEventListener('input', () => {
    aeReal = +aeS.value;
    aeV.textContent = `${aeReal} mm`;
    updateFzRange();
    calculateAll();
  });
  passS.addEventListener('input', () => {
    updateFzRange();
    calculateAll();
  });

  initSliders();
}

if (typeof module !== 'undefined') module.exports = initExpertResult;
