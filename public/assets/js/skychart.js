/* Interactive offline sky chart: stereographic projection centred on the birthday star.
   Data: /assets/data/sky-stars.json (HYG, mag <= 5.5) and constellations.lines.json (d3-celestial, BSD). */
(function () {
  'use strict';
  var cv = document.getElementById('skychart');
  if (!cv) return;
  var ctx = cv.getContext('2d');
  var D2R = Math.PI / 180;
  var target = { ra: parseFloat(cv.dataset.ra), dec: parseFloat(cv.dataset.dec), name: cv.dataset.name, con: cv.dataset.con, conName: cv.dataset.conName || cv.dataset.con };
  var view = { ra: target.ra, dec: target.dec, fov: 70 };
  var stars = [], lines = [], printing = false, t0 = performance.now();
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function size() {
    var dpr = Math.min(window.devicePixelRatio || 1, 2), w = cv.clientWidth || 900;
    cv.width = Math.round(w * dpr); cv.height = Math.round(w * 2 / 3 * dpr);
  }
  // Stereographic projection around (view.ra, view.dec). Sky seen from inside: east to the left.
  function project(ra, dec) {
    var l = (ra - view.ra) * D2R, d = dec * D2R, d0 = view.dec * D2R;
    var cosc = Math.sin(d0) * Math.sin(d) + Math.cos(d0) * Math.cos(d) * Math.cos(l);
    if (cosc < -0.2) return null;
    var k = 2 / (1 + cosc);
    var x = k * Math.cos(d) * Math.sin(l), y = k * (Math.cos(d0) * Math.sin(d) - Math.sin(d0) * Math.cos(d) * Math.cos(l));
    var scale = cv.width / (2 * 2 * Math.tan(view.fov * D2R / 4));
    return [cv.width / 2 - x * scale, cv.height / 2 - y * scale];
  }
  function colour(ci) {
    if (ci < 0) return '#a9c8ff'; if (ci < .3) return '#dfe8ff'; if (ci < .6) return '#fff8ec';
    if (ci < 1.0) return '#ffe2a8'; if (ci < 1.4) return '#ffc27a'; return '#ff9f6b';
  }
  function draw(now) {
    var W = cv.width, H = cv.height, s = W / 900;
    ctx.fillStyle = printing ? '#fff' : '#02040f'; ctx.fillRect(0, 0, W, H);
    // RA/Dec grid
    ctx.strokeStyle = printing ? '#ccc' : 'rgba(127,231,255,.12)'; ctx.lineWidth = 1 * s;
    for (var dec = -80; dec <= 80; dec += 20) path(segment(0, dec, 360, dec));
    for (var ra = 0; ra < 360; ra += 30) path(segment(ra, -85, ra, 85));
    // Constellation lines
    lines.forEach(function (c) {
      ctx.strokeStyle = c.id === target.con ? (printing ? '#c07000' : 'rgba(255,214,107,.85)') : (printing ? '#888' : 'rgba(127,231,255,.35)');
      ctx.lineWidth = (c.id === target.con ? 2.5 : 1.2) * s;
      c.lines.forEach(function (ln) { path(ln); });
    });
    // Stars
    var magLimit = view.fov > 90 ? 4.5 : 5.5;
    for (var i = 0; i < stars.length; i++) {
      var st = stars[i]; if (st[2] > magLimit) continue;
      var p = project(st[0], st[1]); if (!p || p[0] < -5 || p[1] < -5 || p[0] > W + 5 || p[1] > H + 5) continue;
      var r = Math.max(.6, (6 - st[2]) * 1.0) * s * (70 / view.fov) ** .3;
      ctx.fillStyle = printing ? '#000' : colour(st[3]);
      ctx.beginPath(); ctx.arc(p[0], p[1], r, 0, 6.283); ctx.fill();
    }
    // Constellation label
    if (target.con) {
      var lc = lines.filter(function (c) { return c.id === target.con; })[0];
      if (lc && lc.lines[0]) {
        var lp = project(lc.lines[0][0][0], lc.lines[0][0][1]);
        if (lp) { ctx.fillStyle = printing ? '#c07000' : '#ffd66b'; ctx.font = 'bold ' + 18 * s + 'px system-ui, sans-serif'; ctx.fillText(target.conName.toUpperCase(), lp[0] + 8 * s, lp[1] - 8 * s); }
      }
    }
    // Birthday star marker
    var tp = project(target.ra, target.dec);
    if (tp) {
      var pulse = reduce || printing ? 0 : (Math.sin((now - t0) / 400) + 1) * 4;
      ctx.strokeStyle = printing ? '#d00' : '#ff8bd1'; ctx.lineWidth = 3 * s;
      ctx.beginPath(); ctx.arc(tp[0], tp[1], (18 + pulse) * s, 0, 6.283); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(tp[0] - 34 * s, tp[1]); ctx.lineTo(tp[0] - 24 * s, tp[1]); ctx.moveTo(tp[0] + 24 * s, tp[1]); ctx.lineTo(tp[0] + 34 * s, tp[1]);
      ctx.moveTo(tp[0], tp[1] - 34 * s); ctx.lineTo(tp[0], tp[1] - 24 * s); ctx.moveTo(tp[0], tp[1] + 24 * s); ctx.lineTo(tp[0], tp[1] + 34 * s); ctx.stroke();
      ctx.fillStyle = printing ? '#000' : '#fff'; ctx.beginPath(); ctx.arc(tp[0], tp[1], 4 * s, 0, 6.283); ctx.fill();
      ctx.font = 'bold ' + 22 * s + 'px system-ui, sans-serif'; ctx.fillStyle = printing ? '#d00' : '#ffd66b';
      ctx.fillText(target.name, tp[0] + 30 * s, tp[1] + 40 * s);
    }
    ctx.font = 14 * s + 'px system-ui, sans-serif'; ctx.fillStyle = printing ? '#555' : 'rgba(201,209,245,.8)';
    ctx.fillText('N ↑   E ←', 12 * s, H - 14 * s);
    if (!reduce && !printing) requestAnimationFrame(draw);
  }
  function segment(ra1, d1, ra2, d2) {
    var pts = [], n = 60;
    for (var i = 0; i <= n; i++) pts.push([ra1 + (ra2 - ra1) * i / n, d1 + (d2 - d1) * i / n]);
    return pts;
  }
  function path(pts) {
    ctx.beginPath(); var pen = false;
    for (var i = 0; i < pts.length; i++) {
      var p = project(pts[i][0], pts[i][1]);
      if (!p) { pen = false; continue; }
      if (pen) ctx.lineTo(p[0], p[1]); else { ctx.moveTo(p[0], p[1]); pen = true; }
    }
    ctx.stroke();
  }
  function redraw() { if (reduce || printing) draw(performance.now()); }
  function zoom(f) { view.fov = Math.max(15, Math.min(160, view.fov * f)); redraw(); }
  function pan(dx, dy) {
    var degPerPx = view.fov / cv.clientWidth;
    view.ra = (view.ra + dx * degPerPx / Math.max(.2, Math.cos(view.dec * D2R)) + 360) % 360;
    view.dec = Math.max(-89, Math.min(89, view.dec + dy * degPerPx));
    redraw();
  }

  // Pointer: drag to pan, pinch to zoom
  var pts = {}, lastDist = 0;
  cv.addEventListener('pointerdown', function (e) { cv.setPointerCapture(e.pointerId); pts[e.pointerId] = [e.clientX, e.clientY]; cv.style.cursor = 'grabbing'; });
  cv.addEventListener('pointermove', function (e) {
    if (!pts[e.pointerId]) return;
    var ids = Object.keys(pts);
    if (ids.length === 2) {
      pts[e.pointerId] = [e.clientX, e.clientY];
      var a = pts[ids[0]], b = pts[ids[1]], d = Math.hypot(a[0] - b[0], a[1] - b[1]);
      if (lastDist) zoom(lastDist / d); lastDist = d; return;
    }
    var p = pts[e.pointerId]; pan(e.clientX - p[0], e.clientY - p[1]); pts[e.pointerId] = [e.clientX, e.clientY];
  });
  function up(e) { delete pts[e.pointerId]; lastDist = 0; cv.style.cursor = 'grab'; }
  cv.addEventListener('pointerup', up); cv.addEventListener('pointercancel', up);
  cv.addEventListener('wheel', function (e) { e.preventDefault(); zoom(e.deltaY > 0 ? 1.1 : 0.9); }, { passive: false });
  cv.addEventListener('keydown', function (e) {
    var k = { ArrowLeft: [40, 0], ArrowRight: [-40, 0], ArrowUp: [0, 40], ArrowDown: [0, -40] }[e.key];
    if (k) { pan(k[0], k[1]); e.preventDefault(); }
    if (e.key === '+' || e.key === '=') zoom(.85); if (e.key === '-') zoom(1.18);
  });
  document.querySelectorAll('[data-zoom]').forEach(function (b) {
    b.addEventListener('click', function () {
      var z = b.getAttribute('data-zoom');
      if (z === 'in') zoom(.8); else if (z === 'out') zoom(1.25); else { view = { ra: target.ra, dec: target.dec, fov: 70 }; redraw(); }
    });
  });
  addEventListener('resize', function () { size(); redraw(); });
  addEventListener('beforeprint', function () { printing = true; view = { ra: target.ra, dec: target.dec, fov: 70 }; draw(performance.now()); });
  addEventListener('afterprint', function () { printing = false; if (!reduce) requestAnimationFrame(draw); else redraw(); });

  size();
  Promise.all([fetch(cv.dataset.stars).then(function (r) { return r.json(); }), fetch(cv.dataset.lines).then(function (r) { return r.json(); })])
    .then(function (res) {
      stars = res[0].stars;
      lines = res[1].features.map(function (f) {
        return { id: f.id, lines: f.geometry.coordinates.map(function (ln) { return ln.map(function (p) { return [(p[0] + 360) % 360, p[1]]; }); }) };
      });
      requestAnimationFrame(draw);
    })
    .catch(function () { requestAnimationFrame(draw); });
})();
