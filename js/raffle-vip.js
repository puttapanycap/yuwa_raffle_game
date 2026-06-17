/**
 * Raffle VIP Button — extracted from button.php inline script.
 * Public globals required:
 *   window.VIP_CONFIG = { key }
 *   window.AuroraPoller (loaded before this)
 */
(function (global) {
  'use strict';

  const cfg = global.VIP_CONFIG || {};
  if (!cfg.key) { console.warn('RaffleVIP: missing VIP_CONFIG.key'); return; }

  const state = {
    settings: {},
    prizeCategories: [],
    currentState: 'idle',
  };

  const $ = (id) => document.getElementById(id);

  const poller = new AuroraPoller({
    fetchFn: () => fetch(`./actions/get_state.php?raffle_key=${cfg.key}`).then(r => r.json()),
    onData: handleState,
    onError: (e) => {
      $('statusDot')?.classList.remove('online');
      if ($('statusText')) $('statusText').textContent = 'ขาดการเชื่อมต่อ';
    },
  });

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    loadSettings();
    poller.start();
  }

  // === Settings ===
  async function loadSettings() {
    try {
      const res = await fetch(`./actions/get_settings.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) return;
      state.settings = data.settings;
      state.prizeCategories = data.prize_categories || [];
      document.body.setAttribute('data-theme', state.settings.theme || 'aurora');
      document.documentElement.style.setProperty('--result-font-color', state.settings.result_font_color || 'var(--aurora-gold)');

      if (state.settings.enable_prize_categories == 1 && state.prizeCategories.length > 0) {
        $('prizeSection').style.display = 'block';
        const select = $('prizeSelect');
        const cur = select.value;
        select.innerHTML = '<option value="">🎯 ไม่ระบุประเภท</option>';
        state.prizeCategories.forEach(p => {
          const remaining = p.quantity - p.winners_count;
          const disabled = remaining <= 0 ? 'disabled' : '';
          const suffix = remaining <= 0 ? ' (หมดแล้ว)' : ` (เหลือ ${remaining}/${p.quantity})`;
          select.innerHTML += `<option value="${p.id}" ${disabled}>${escapeHtml(p.category_name)}${suffix}</option>`;
        });
        if (cur && select.querySelector(`option[value="${cur}"]:not([disabled])`)) select.value = cur;
      }
    } catch (e) { console.warn('loadSettings:', e); }
  }

  // === Polling ===
  function handleState(data) {
    if (!data?.success) return;
    poller.setState(data.state.action);
    $('statusDot')?.classList.add('online');
    if ($('statusText')) $('statusText').textContent = `${data.entries.remaining} รายชื่อคงเหลือ`;
    if (data.state.action !== state.currentState) onStateChange(data.state);
    state.currentState = data.state.action;
  }

  function onStateChange(s) {
    const btn = $('spinBtn');
    const spinContainer = $('spinContainer');
    const winnerReveal = $('winnerReveal');
    const prizeSelect = $('prizeSelect');
    switch (s.action) {
      case 'idle':
        btn.disabled = false;
        btn.classList.remove('spinning');
        btn.innerHTML = '<i class="fa-solid fa-dice fa-bounce"></i><div class="mt-3">สุ่ม</div>';
        $('statusMessage').textContent = 'แตะปุ่มเพื่อสุ่มรายชื่อ';
        spinContainer?.classList.remove('hide');
        winnerReveal?.classList.remove('show');
        if (prizeSelect) prizeSelect.disabled = false;
        break;
      case 'spinning':
        btn.disabled = true;
        btn.classList.add('spinning');
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-3">กำลังสุ่ม...</div>';
        $('statusMessage').textContent = 'รอผลการสุ่ม...';
        if (prizeSelect) prizeSelect.disabled = true;
        break;
      case 'revealing':
      case 'confirmed':
        spinContainer?.classList.add('hide');
        winnerReveal?.classList.add('show');
        $('winnerName').textContent = s.winner || 'ผู้โชคดี';
        if (prizeSelect) prizeSelect.disabled = true;
        if (state.settings.enable_confetti != 0 && global.confetti) {
          global.confetti.start();
          setTimeout(() => global.confetti.stop(), 3000);
        }
        break;
    }
  }

  // === Trigger ===
  global.triggerSpin = async function () {
    const btn = $('spinBtn');
    const prizeId = $('prizeSelect')?.value || '';

    if (prizeId !== '') {
      const sel = state.prizeCategories.find(p => p.id == prizeId);
      if (sel && (sel.quantity - sel.winners_count) <= 0) {
        Swal.fire('หมดแล้ว', `รางวัล "${sel.category_name}" หมดแล้ว กรุณาเลือกประเภทอื่น`, 'warning');
        return;
      }
    }

    btn.disabled = true;
    btn.classList.add('spinning');
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-3">กำลังสุ่ม...</div>';

    try {
      const res = await fetch(`./actions/pick_random.php?raffle_key=${cfg.key}&prize_id=${prizeId}`);
      const data = await res.json();
      if (!data.success) {
        Swal.fire('ไม่มีรายชื่อ', data.error || 'ไม่มีรายชื่อให้สุ่มแล้ว', 'warning');
        resetBtn();
        return;
      }

      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fd.append('action', 'spinning');
      fd.append('triggered_by', 'vip');
      fetch('./actions/set_state.php', { method: 'POST', body: fd });

      const duration = (parseInt(state.settings.spin_duration) || 5) * 1000;
      setTimeout(() => {
        const rfd = new FormData();
        rfd.append('raffle_key', cfg.key);
        rfd.append('action', 'revealing');
        rfd.append('winner', data.winner.name);
        rfd.append('winner_id', data.winner.id);
        rfd.append('prize_id', prizeId);
        fetch('./actions/set_state.php', { method: 'POST', body: rfd });

        setTimeout(() => {
          const cfd = new FormData();
          cfd.append('raffle_key', cfg.key);
          cfd.append('winner', data.winner.name);
          cfd.append('winner_id', data.winner.id);
          cfd.append('prize_id', prizeId);
          fetch('./actions/confirm_winner.php', { method: 'POST', body: cfd })
            .then(r => r.json())
            .then(() => loadSettings());
        }, 300);
      }, duration);
    } catch (e) {
      console.error('VIP spin error:', e);
      Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
      resetBtn();
    }
  };

  function resetBtn() {
    const btn = $('spinBtn');
    btn.disabled = false;
    btn.classList.remove('spinning');
    btn.innerHTML = '<i class="fa-solid fa-dice fa-bounce"></i><div class="mt-3">สุ่ม</div>';
  }

  global.resetForNext = function () {
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('action', 'idle');
    fetch('./actions/set_state.php', { method: 'POST', body: fd });
  };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
})(typeof window !== 'undefined' ? window : this);
