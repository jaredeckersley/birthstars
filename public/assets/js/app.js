/* Birthday Stars – plain JavaScript, no build step, no network calls except same-origin data. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Animated starfield background ---------- */
  function starfield() {
    var c = document.getElementById('starfield');
    if (!c || !c.getContext) return;
    var ctx = c.getContext('2d'), stars = [], shooting = null, w, h, dpr;
    function resize() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      w = c.width = Math.floor(innerWidth * dpr); h = c.height = Math.floor(innerHeight * dpr);
      var n = Math.min(450, Math.floor(innerWidth * innerHeight / 3500));
      stars = [];
      for (var i = 0; i < n; i++) {
        stars.push({ x: Math.random() * w, y: Math.random() * h, r: (Math.random() * 1.4 + .3) * dpr,
          p: Math.random() * 6.28, s: .5 + Math.random() * 2, hue: [200, 45, 320, 0][i % 4], z: Math.random() });
      }
    }
    function frame(t) {
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < stars.length; i++) {
        var s = stars[i], a = .45 + .55 * Math.sin(s.p + t / 1000 * s.s);
        if (!reduceMotion) { s.x -= s.z * .08 * dpr; if (s.x < 0) s.x = w; }
        ctx.globalAlpha = Math.max(.15, a);
        ctx.fillStyle = s.hue === 0 ? '#fff' : 'hsl(' + s.hue + ',90%,85%)';
        ctx.beginPath(); ctx.arc(s.x, s.y, s.r, 0, 6.283); ctx.fill();
      }
      if (!reduceMotion) {
        if (!shooting && Math.random() < .004) shooting = { x: Math.random() * w, y: Math.random() * h * .5, l: 0 };
        if (shooting) {
          shooting.l += 14 * dpr;
          var g = ctx.createLinearGradient(shooting.x, shooting.y, shooting.x - shooting.l, shooting.y - shooting.l * .4);
          g.addColorStop(0, 'rgba(255,255,255,.9)'); g.addColorStop(1, 'rgba(255,255,255,0)');
          ctx.globalAlpha = 1; ctx.strokeStyle = g; ctx.lineWidth = 2 * dpr; ctx.beginPath();
          ctx.moveTo(shooting.x, shooting.y); ctx.lineTo(shooting.x - shooting.l, shooting.y - shooting.l * .4); ctx.stroke();
          shooting.x += 10 * dpr; shooting.y += 4 * dpr;
          if (shooting.l > 300 * dpr) shooting = null;
        }
      }
      ctx.globalAlpha = 1;
      if (!reduceMotion) requestAnimationFrame(frame);
    }
    resize(); addEventListener('resize', resize);
    requestAnimationFrame(frame);
  }

  /* ---------- Big touch-friendly birthday wizard ---------- */
  function wizard() {
    var wiz = document.getElementById('wizard');
    var form = document.getElementById('birthday-form');
    if (!wiz || !form) return;
    var fy = document.getElementById('f-year'), fm = document.getElementById('f-month'), fd = document.getElementById('f-day');
    var minYear = parseInt(fy.options[fy.options.length - 1].value, 10), maxYear = parseInt(fy.options[1].value, 10);
    var display = document.getElementById('year-display'), go = wiz.querySelector('.key-go');
    var digits = '';
    wiz.hidden = false;
    var link = document.createElement('p'); link.className = 'hint';
    link.innerHTML = '<button type="button" class="btn-ghost" id="show-plain">Prefer drop-down menus?</button>';
    form.appendChild(link);
    document.getElementById('show-plain').addEventListener('click', function () {
      document.documentElement.classList.toggle('show-plain'); fy.focus();
    });

    function show(step) {
      wiz.querySelectorAll('.step').forEach(function (s) { s.hidden = s.getAttribute('data-step') !== step; });
      var order = ['year', 'month', 'day'], idx = order.indexOf(step);
      wiz.querySelectorAll('[data-step-dot]').forEach(function (d) {
        var i = order.indexOf(d.getAttribute('data-step-dot'));
        d.className = i === idx ? 'active' : (i < idx ? 'done' : '');
        if (i === idx) d.setAttribute('aria-current', 'step'); else d.removeAttribute('aria-current');
      });
      var h = wiz.querySelector('[data-step="' + step + '"] h2'); if (h) { h.tabIndex = -1; h.focus(); }
    }
    function updateYear() {
      display.textContent = (digits + '____').slice(0, 4);
      var y = parseInt(digits, 10);
      var ok = digits.length === 4 && y >= minYear && y <= maxYear;
      go.disabled = !ok;
      if (digits.length === 4 && !ok) display.textContent = digits + ' ?';
    }
    wiz.querySelectorAll('[data-key]').forEach(function (b) {
      b.addEventListener('click', function () {
        var k = b.getAttribute('data-key');
        if (k === 'back') digits = digits.slice(0, -1);
        else if (digits.length < 4) digits += k;
        else digits = k;
        updateYear();
      });
    });
    document.addEventListener('keydown', function (e) {
      var yearStep = wiz.querySelector('[data-step="year"]');
      if (yearStep.hidden || document.documentElement.classList.contains('show-plain')) return;
      if (/^[0-9]$/.test(e.key)) { wiz.querySelector('[data-key="' + e.key + '"]').click(); e.preventDefault(); }
      else if (e.key === 'Backspace') { wiz.querySelector('[data-key="back"]').click(); e.preventDefault(); }
      else if (e.key === 'Enter' && !go.disabled && e.target.tagName !== 'BUTTON') { go.click(); e.preventDefault(); }
    });
    go.addEventListener('click', function () { fy.value = digits; show('month'); });
    wiz.querySelectorAll('[data-month]').forEach(function (b) {
      b.addEventListener('click', function () {
        var m = parseInt(b.getAttribute('data-month'), 10); fm.value = String(m);
        var y = parseInt(digits, 10), n = new Date(y, m, 0).getDate(), box = document.getElementById('day-tiles');
        var today = new Date();
        box.innerHTML = '';
        for (var d = 1; d <= n; d++) {
          var t = document.createElement('button');
          t.type = 'button'; t.className = 'tile'; t.textContent = String(d); t.setAttribute('data-day', String(d));
          if (new Date(y, m - 1, d) > today) { t.disabled = true; t.style.opacity = .3; }
          t.addEventListener('click', function (ev) {
            fd.value = ev.currentTarget.getAttribute('data-day');
            document.documentElement.classList.add('launching');
            form.submit();
          });
          box.appendChild(t);
        }
        show('day');
      });
    });
    wiz.querySelectorAll('[data-back]').forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-back')); }); });
    updateYear(); show('year');
    var yh = wiz.querySelector('#h-year'); if (yh) yh.blur();
  }

  /* ---------- Kiosk mode: fullscreen + reset after inactivity ---------- */
  function kiosk() {
    var body = document.body, home = body.getAttribute('data-kiosk-home');
    if (!home) return;
    var timeout = (parseInt(body.getAttribute('data-kiosk-timeout'), 10) || 90) * 1000;
    var warn = document.getElementById('idle-warning'), count = document.getElementById('idle-count');
    var idleTimer, countTimer, onHome = location.pathname === home && !location.search;
    function reset() {
      clearTimeout(idleTimer); clearInterval(countTimer); if (warn) warn.hidden = true;
      if (onHome && !document.querySelector('.error')) {
        // On the start page, just return to step 1 after a while.
        idleTimer = setTimeout(function () { if (document.getElementById('year-display').textContent !== '____') location.replace(home); }, timeout);
        return;
      }
      idleTimer = setTimeout(function () {
        var n = 10; warn.hidden = false; count.textContent = n;
        countTimer = setInterval(function () { n--; count.textContent = n; if (n <= 0) location.replace(home); }, 1000);
      }, timeout);
    }
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (ev) { addEventListener(ev, reset, { passive: true }); });
    reset();
    var fs = document.getElementById('fullscreen-btn');
    function goFull() { var el = document.documentElement; if (!document.fullscreenElement && el.requestFullscreen) el.requestFullscreen().catch(function () {}); }
    if (fs) fs.addEventListener('click', goFull);
    addEventListener('pointerdown', goFull, { once: true });
    addEventListener('contextmenu', function (e) { e.preventDefault(); });
  }

  function printButton() {
    var b = document.getElementById('print-btn');
    if (b) b.addEventListener('click', function () { window.print(); });
  }

  function loadChart() {
    if (!document.getElementById('skychart')) return;
    var s = document.createElement('script');
    s.src = (document.body.getAttribute('data-base') || '') + '/assets/js/skychart.js';
    document.head.appendChild(s);
  }

  document.addEventListener('DOMContentLoaded', function () { starfield(); wizard(); kiosk(); printButton(); loadChart(); });
})();
