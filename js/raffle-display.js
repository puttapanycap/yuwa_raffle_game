/**
 * Raffle Display — Main display logic for index.php
 * @description Handles spin lifecycle, state sync, audio, confetti, wheel.
 *              Uses AuroraPoller (adaptive) + DOM-diff rendering for efficiency.
 *
 * Public globals required (set by index.php):
 *   window.RAFFLE_CONFIG = { key, eventTitle, initialEntries, initialLogsCount, initialEntriesCount }
 */
(function (global) {
  'use strict';

  const cfg = global.RAFFLE_CONFIG || {};
  if (!cfg.key) {
    console.warn('RaffleDisplay: missing RAFFLE_CONFIG.key');
    return;
  }

  // === Module state ===
  const state = {
    settings: {},
    entries: cfg.initialEntries || [],
    currentState: 'idle',
    spinInterval: null,
    spinTimeoutId: null,
    spinAudio: null,
    winnerAudio: null,
    pollCount: 0,
    lastWinnersCount: cfg.initialLogsCount || 0,
    lastEntriesCount: cfg.initialEntriesCount || 0,
  };

  // === DOM refs ===
  const $ = (id) => document.getElementById(id);
  const dom = {
    eventTitle: $('eventTitle'),
    remainingCount: $('remainingCount'),
    entryCount: $('entryCount'),
    drawerEntryCount: $('drawerEntryCount'),
    drawerWinnerCount: $('drawerWinnerCount'),
    entriesList: $('entriesList'),
    entriesListMobile: $('entriesListMobile'),
    winnersList: $('winnersList'),
    winnersListMobile: $('winnersListMobile'),
    winnerCount: $('winnerCount'),
    prizeBanner: $('prizeBanner'),
    currentPrizeName: $('currentPrizeName'),
    textRollContainer: $('textRollContainer'),
    textRollDisplay: $('textRollDisplay'),
    wheelContainer: $('wheelContainer'),
    wheel: $('wheel'),
    wheelSpinBtn: $('wheelSpinBtn'),
    resultDisplay: $('resultDisplay'),
    btnContainer: $('btnContainer'),
    spinBtn: $('spinBtn'),
  };

  // === Poller ===
  const poller = new AuroraPoller({
    fetchFn: () => fetch(`./actions/get_state.php?raffle_key=${cfg.key}`).then(r => r.json()),
    onData: handleState,
    onError: (e) => console.warn('Poller error:', e),
  });

  // ===========================================================================
  // INITIALIZATION
  // ===========================================================================
  document.addEventListener('DOMContentLoaded', init);

  function init() {
    loadSettings();
    poller.start();
  }

  // ===========================================================================
  // SETTINGS
  // ===========================================================================
  async function loadSettings() {
    try {
      const res = await fetch(`./actions/get_settings.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) return;
      state.settings = data.settings;
      applySettings();
      if (data.custom_sounds?.spin)   state.spinAudio   = new Audio('./' + data.custom_sounds.spin.file_path);
      if (data.custom_sounds?.winner) state.winnerAudio = new Audio('./' + data.custom_sounds.winner.file_path);
    } catch (e) {
      console.warn('loadSettings error:', e);
    }
  }

  function applySettings() {
    const s = state.settings;
    document.body.setAttribute('data-theme', s.theme || 'aurora');
    const root = document.documentElement.style;
    root.setProperty('--result-font-size', (s.result_font_size || 72) + 'px');
    root.setProperty('--result-font-color', s.result_font_color || 'var(--text-primary)');
    root.setProperty('--entry-font-size', (s.entry_font_size || 16) + 'px');
    root.setProperty('--winner-font-size', (s.winner_font_size || 16) + 'px');

    // Show/hide main button
    if (s.show_main_button == 0) dom.btnContainer.classList.add('hide');
    else dom.btnContainer.classList.remove('hide');

    // Template
    if (s.animation_template === 'wheel') {
      dom.wheelContainer.style.display = 'flex';
      dom.textRollContainer.classList.add('hide');
      dom.btnContainer.classList.add('hide');
      initWheel();
    } else {
      dom.wheelContainer.style.display = 'none';
      dom.textRollContainer.classList.remove('hide');
    }
  }

  // ===========================================================================
  // STATE POLLING HANDLER
  // ===========================================================================
  function handleState(data) {
    if (!data?.success) return;
    state.pollCount++;
    poller.setState(data.state.action);

    const remaining = data.entries.remaining;
    dom.remainingCount.textContent = remaining;
    dom.entryCount.textContent = remaining;
    dom.drawerEntryCount.textContent = remaining;

    // Detect entry list changes (undo or new winner)
    if (remaining !== state.lastEntriesCount) {
      state.lastEntriesCount = remaining;
      loadEntries();
      loadWinners();
    }

    // Prize banner
    if (data.state.prize) {
      dom.prizeBanner.style.display = 'block';
      dom.currentPrizeName.textContent = data.state.prize.category_name;
    } else {
      dom.prizeBanner.style.display = 'none';
    }

    // State transition
    if (data.state.action !== state.currentState) {
      onStateChange(data.state);
    }
    state.currentState = data.state.action;

    // Periodic winner refresh every ~10s
    if (state.pollCount % 5 === 0) loadWinners();
  }

  function onStateChange(s) {
    const isWheel = state.settings.animation_template === 'wheel';
    const btn = dom.spinBtn;
    const wheelBtn = dom.wheelSpinBtn;
    const textRoll = dom.textRollContainer;
    const result = dom.resultDisplay;

    switch (s.action) {
      case 'idle':
        stopSpinAnimation();
        if (btn) {
          btn.disabled = false;
          btn.classList.remove('spinning');
          btn.innerHTML = '<i class="fa-solid fa-dice"></i><div class="mt-2">สุ่ม</div>';
        }
        if (wheelBtn) {
          wheelBtn.disabled = false;
          wheelBtn.classList.remove('spinning');
          wheelBtn.textContent = 'START';
        }
        result.style.display = 'none';
        if (!isWheel) textRoll.classList.remove('hide');
        dom.textRollDisplay.textContent = '';
        dom.textRollDisplay.classList.remove('active');
        break;

      case 'spinning':
        if (btn) {
          btn.disabled = true;
          btn.classList.add('spinning');
          btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-2">กำลังสุ่ม...</div>';
        }
        if (wheelBtn) {
          wheelBtn.disabled = true;
          wheelBtn.classList.add('spinning');
        }
        result.style.display = 'none';
        if (!isWheel) textRoll.classList.remove('hide');
        startSpinAnimation();
        if (state.settings.enable_sound != 0 && state.spinAudio) {
          state.spinAudio.loop = true;
          state.spinAudio.play().catch(() => {});
        }
        break;

      case 'revealing':
      case 'confirmed':
        stopSpinAnimation();
        textRoll.classList.add('hide');
        result.style.display = 'flex';
        result.textContent = s.winner;
        result.classList.add('winner');
        if (btn) {
          btn.disabled = false;
          btn.classList.remove('spinning');
          btn.innerHTML = '<i class="fa-solid fa-rotate-right"></i><div class="mt-2">สุ่มต่อ</div>';
        }
        if (wheelBtn) {
          wheelBtn.disabled = false;
          wheelBtn.classList.remove('spinning');
          wheelBtn.textContent = 'START';
        }
        if (state.spinAudio) state.spinAudio.pause();
        if (state.settings.enable_sound != 0 && state.winnerAudio) {
          state.winnerAudio.play().catch(() => {});
        }
        if (state.settings.enable_confetti != 0 && global.confetti) {
          global.confetti.start();
          setTimeout(() => global.confetti.stop(), 3000);
        }
        loadEntries();
        loadWinners();
        break;

      case 'refresh':
        location.reload();
        break;
    }
  }

  // ===========================================================================
  // SPIN TRIGGER (main button)
  // ===========================================================================
  global.startSpin = async function () {
    if (state.currentState === 'confirmed' || state.currentState === 'revealing') {
      // Reset first, then spin
      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fd.append('action', 'idle');
      await fetch('./actions/set_state.php', { method: 'POST', body: fd });
      setTimeout(startSpin, 300);
      return;
    }
    if (state.spinTimeoutId) { clearTimeout(state.spinTimeoutId); state.spinTimeoutId = null; }
    if (state.entries.length === 0) {
      Swal.fire('ไม่มีรายชื่อ', 'กรุณาเพิ่มรายชื่อก่อนเริ่มสุ่ม', 'warning');
      return;
    }
    const btn = dom.spinBtn;
    btn.disabled = true;
    btn.classList.add('spinning');
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-2">กำลังสุ่ม...</div>';

    try {
      const res = await fetch(`./actions/pick_random.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) {
        resetSpinUI();
        const fd = new FormData();
        fd.append('raffle_key', cfg.key);
        fd.append('action', 'idle');
        try { await fetch('./actions/set_state.php', { method: 'POST', body: fd }); } catch (_) {}
        Swal.fire('ไม่มีรายชื่อ', data.error || 'ไม่มีรายชื่อให้สุ่มแล้ว', 'warning');
        return;
      }
      if (data.entries) state.entries = data.entries;

      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fd.append('action', 'spinning');
      fd.append('triggered_by', 'main');
      try { await fetch('./actions/set_state.php', { method: 'POST', body: fd }); } catch (_) {}

      const duration = (parseInt(state.settings.spin_duration) || 5) * 1000;
      state.spinTimeoutId = setTimeout(() => {
        state.spinTimeoutId = null;
        stopSpinAnimation();
        const rfd = new FormData();
        rfd.append('raffle_key', cfg.key);
        rfd.append('action', 'revealing');
        rfd.append('winner', data.winner.name);
        rfd.append('winner_id', data.winner.id);
        try { fetch('./actions/set_state.php', { method: 'POST', body: rfd }); } catch (_) {}
        setTimeout(() => {
          const cfd = new FormData();
          cfd.append('raffle_key', cfg.key);
          cfd.append('winner', data.winner.name);
          cfd.append('winner_id', data.winner.id);
          try { fetch('./actions/confirm_winner.php', { method: 'POST', body: cfd }); } catch (_) {}
        }, 300);
      }, duration);
    } catch (e) {
      console.error('Spin error:', e);
      resetSpinUI();
      Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    }
  };

  function resetSpinUI() {
    const btn = dom.spinBtn;
    stopSpinAnimation();
    if (btn) {
      btn.disabled = false;
      btn.classList.remove('spinning');
      btn.innerHTML = '<i class="fa-solid fa-dice"></i><div class="mt-2">สุ่ม</div>';
    }
  }

  // ===========================================================================
  // SPIN ANIMATION (text-roll / wheel)
  // ===========================================================================
  function startSpinAnimation() {
    if (state.entries.length === 0) return;
    if (state.settings.animation_template !== 'wheel') {
      const display = dom.textRollDisplay;
      let i = 0;
      state.spinInterval = setInterval(() => {
        display.classList.remove('active');
        setTimeout(() => {
          display.textContent = state.entries[i].name;
          display.classList.add('active');
          i = (i + 1) % state.entries.length;
        }, 10);
      }, 80);
    } else {
      startWheelSpin();
    }
  }

  function stopSpinAnimation() {
    if (state.spinInterval) {
      clearInterval(state.spinInterval);
      state.spinInterval = null;
    }
    if (state.spinAudio) {
      state.spinAudio.pause();
      state.spinAudio.currentTime = 0;
    }
    stopWheelSpin();
  }

  // ===========================================================================
  // DATA: ENTRIES & WINNERS (DOM-diff render)
  // ===========================================================================
  async function loadEntries() {
    try {
      const res = await fetch(`./actions/get_entries.php?raffle_key=${cfg.key}&filter=available`);
      const data = await res.json();
      if (!data.success) return;
      state.entries = data.entries;
      renderEntries(dom.entriesList, data.entries);
      if (dom.entriesListMobile) renderEntries(dom.entriesListMobile, data.entries);
      dom.entryCount.textContent = data.entries.length;
      dom.remainingCount.textContent = data.entries.length;
      dom.drawerEntryCount.textContent = data.entries.length;
    } catch (e) {
      console.warn('loadEntries error:', e);
    }
  }

  /** DOM-diff render: reuse existing nodes, only update text/remove stale. */
  function renderEntries(container, entries) {
    if (!container) return;
    const existing = new Map();
    for (const el of container.children) {
      if (el.dataset.id) existing.set(el.dataset.id, el);
    }
    const seen = new Set();
    const frag = document.createDocumentFragment();
    entries.forEach((e) => {
      seen.add(String(e.id));
      let el = existing.get(String(e.id));
      if (!el) {
        el = document.createElement('span');
        el.className = 'entry-badge';
        el.dataset.id = e.id;
      }
      if (el.textContent !== e.name) el.textContent = e.name;
      frag.appendChild(el);
    });
    existing.forEach((el, id) => { if (!seen.has(id)) el.remove(); });
    container.replaceChildren(frag);
  }

  async function loadWinners() {
    try {
      const res = await fetch(`./actions/get_winners.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) return;
      renderWinners(dom.winnersList, data.logs, data.total);
      if (dom.winnersListMobile) renderWinners(dom.winnersListMobile, data.logs, data.total);
      dom.winnerCount.textContent = data.logs.length;
      dom.drawerWinnerCount.textContent = data.logs.length;
    } catch (e) {
      console.warn('loadWinners error:', e);
    }
  }

  function renderWinners(container, winners, total) {
    if (!container) return;
    if (winners.length === 0) {
      container.innerHTML = '<div class="text-center text-muted py-4">ยังไม่มีผู้โชคดี</div>';
      return;
    }
    const html = winners.map((w, i) => {
      const ts = new Date(w.created_at);
      const timeStr = ts.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: '2-digit' });
      return `
        <div class="winner-log">
          <div class="number">${total - i}</div>
          <div class="name">
            ${escapeHtml(w.winner_name)}
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 400;">${timeStr}</div>
          </div>
          ${w.prize_name
            ? `<span class="prize-tag" style="background: ${escapeHtml(w.prize_color)}20; color: ${escapeHtml(w.prize_color)}">${escapeHtml(w.prize_name)}</span>`
            : ''}
        </div>`;
    }).join('');
    container.innerHTML = html;
    container.scrollTop = 0;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  // ===========================================================================
  // FULLSCREEN
  // ===========================================================================
  global.toggleFullscreen = function () {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen();
    else document.exitFullscreen();
  };

  // ===========================================================================
  // WHEEL (canvas)
  // ===========================================================================
  const WHEEL_COLORS = ['#00d9ff', '#ffae00', '#00ffa3', '#b16cea', '#67e8f9', '#ff5e9c'];
  let wheelCtx = null, wheelRotation = 0, wheelSpinning = false, wheelAnimId = null;

  function initWheel() {
    if (!dom.wheel) return;
    wheelCtx = dom.wheel.getContext('2d');
    drawWheel();
  }

  function drawWheel() {
    if (!wheelCtx || state.entries.length === 0) return;
    const c = dom.wheel;
    const ctx = wheelCtx;
    const cx = c.width / 2, cy = c.height / 2;
    const r = Math.min(cx, cy) - 10;
    ctx.clearRect(0, 0, c.width, c.height);
    const n = Math.min(state.entries.length, 8);
    const seg = (2 * Math.PI) / n;
    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(wheelRotation);
    for (let i = 0; i < n; i++) {
      const a0 = i * seg - Math.PI / 2;
      const a1 = (i + 1) * seg - Math.PI / 2;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.arc(0, 0, r, a0, a1);
      ctx.closePath();
      ctx.fillStyle = WHEEL_COLORS[i % WHEEL_COLORS.length];
      ctx.fill();
      ctx.strokeStyle = 'rgba(0,0,0,0.2)';
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.save();
      ctx.rotate(a0 + seg / 2 + Math.PI / 2);
      ctx.fillStyle = '#fff';
      ctx.font = 'bold 14px LINESeedSansTH, sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      const e = state.entries[i % state.entries.length];
      let txt = e?.name || '';
      if (txt.length > 12) txt = txt.substring(0, 10) + '...';
      ctx.fillText(txt, 0, -r * 0.65);
      ctx.restore();
    }
    ctx.restore();
    ctx.beginPath();
    ctx.arc(cx, cy, 55, 0, 2 * Math.PI);
    ctx.fillStyle = '#1e1e1e';
    ctx.fill();
  }

  function startWheelSpin() {
    if (wheelSpinning) return;
    wheelSpinning = true;
    const speed = 0.3;
    function loop() {
      if (!wheelSpinning) return;
      wheelRotation += speed;
      if (wheelRotation > 2 * Math.PI) wheelRotation -= 2 * Math.PI;
      drawWheel();
      wheelAnimId = requestAnimationFrame(loop);
    }
    loop();
  }

  function stopWheelSpin() {
    wheelSpinning = false;
    if (wheelAnimId) { cancelAnimationFrame(wheelAnimId); wheelAnimId = null; }
  }
})(typeof window !== 'undefined' ? window : this);
