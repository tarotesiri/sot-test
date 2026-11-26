/**
 *  ============================================================================
 *    Project Name : SIAM Online Training
 *    File Name : app.js
 *    Description : JavaScript ฝั่ง Client: เมนูมือถือ, Slider, Flash Alert หายอัตโนมัติ และ AJAX helper
 * ============================================================================
 *    Author : TaRote SiRi
 *    Email : *******.***@*****.***
 *    Created Date : 2026-11-01
 *    Version : 1.0.0
 *    License : Proprietary (ใช้ภายในองค์กร Siridev.Com เท่านั้น)
 * ============================================================================
**/
/* =====================================================================
   APP.JS — Client Side Scripts
   ===================================================================== */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* 1) เมนูมือถือ */
  const toggle = $('[data-nav-toggle]');
  const menu = $('#nav-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      const open = menu.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') { menu.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); }
    });
  }

  /* 2) Flash Alert & ยืนยันก่อนทำรายการ */
  const hide = el => { if (!el) return; el.classList.add('is-hiding'); setTimeout(() => el.remove(), 300); };
  document.addEventListener('click', e => {
    const close = e.target.closest('[data-dismiss]');
    if (close) hide(close.closest('.alert'));
    const back = e.target.closest('[data-back]');
    if (back && history.length > 1) { e.preventDefault(); history.back(); }
    const c = e.target.closest('[data-confirm]');
    if (c && !window.confirm(c.dataset.confirm)) { e.preventDefault(); e.stopImmediatePropagation(); }
  });
  $$('.alert[data-autohide]').forEach(el => {
    if (!el.classList.contains('alert--error')) setTimeout(() => el.isConnected && hide(el), 6000);
  });

  /* 3) Slider (สำหรับหน้าแรกใน M14) */
  function initSlider(root) {
    const track = $('.slider__track', root);
    const slides = track ? [...track.children] : [];
    if (slides.length < 2) return;
    const dotsBox = $('.slider__dots', root);
    const interval = Number(root.dataset.interval) || 6000;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0, timer = null;

    const dots = slides.map((_, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.setAttribute('aria-label', `ไปสไลด์ที่ ${i + 1}`);
      b.addEventListener('click', () => { go(i); restart(); });
      dotsBox?.appendChild(b);
      return b;
    });

    function go(i) {
      index = (i + slides.length) % slides.length;
      track.style.transform = `translateX(-${index * 100}%)`;
      slides.forEach((s, k) => { s.setAttribute('aria-hidden', String(k !== index)); s.toggleAttribute('inert', k !== index); });
      dots.forEach((d, k) => d.classList.toggle('is-active', k === index));
    }
    const stop = () => { clearInterval(timer); timer = null; };
    const start = () => { if (!reduce && !timer) timer = setInterval(() => go(index + 1), interval); };
    const restart = () => { stop(); start(); };

    $('[data-prev]', root)?.addEventListener('click', () => { go(index - 1); restart(); });
    $('[data-next]', root)?.addEventListener('click', () => { go(index + 1); restart(); });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

    let x0 = null;
    root.addEventListener('pointerdown', e => { x0 = e.clientX; });
    root.addEventListener('pointerup', e => {
      if (x0 === null) return;
      const dx = e.clientX - x0; x0 = null;
      if (Math.abs(dx) > 50) { go(index + (dx < 0 ? 1 : -1)); restart(); }
    });

    go(0);
    start();
  }
  $$('[data-slider]').forEach(initSlider);

  /* 4) AJAX Helper (แนบ CSRF Token อัตโนมัติ) */
  window.App = {
    csrf: $('meta[name="csrf-token"]')?.content || '',
    async post(url, data = {}) {
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': this.csrf, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body: JSON.stringify(data),
      });
      return res.json();
    },
  };
})();