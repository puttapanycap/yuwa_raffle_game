/**
 * Raffle Remote Control — extracted from remote.php inline script.
 * Public globals required:
 *   window.REMOTE_CONFIG = { key }
 *   window.AuroraPoller  (loaded before this)
 */
(function (global) {
  'use strict';

  const cfg = global.REMOTE_CONFIG || {};
  if (!cfg.key) { console.warn('RaffleRemote: missing REMOTE_CONFIG.key'); return; }

  const state = {
    settings: {},
    prizeCategories: [],
    currentState: 'idle',
    currentWinner: null,
    currentWinnerId: null,
    spinTimeoutId: null,
    allEntries: [],
    allWinners: [],
    winnersTotal: 0,
  };

  const $ = (id) => document.getElementById(id);

  const poller = new AuroraPoller({
    fetchFn: () => fetch(`./actions/get_state.php?raffle_key=${cfg.key}`).then(r => r.json()),
    onData: handleState,
    onError: (e) => console.warn('Poller error:', e),
  });

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    loadSettings();
    loadEntries();
    loadWinners();
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
        select.innerHTML = '<option value="">ไม่ระบุประเภท</option>';
        state.prizeCategories.forEach(p => {
          const remaining = p.quantity - p.winners_count;
          select.innerHTML += `<option value="${p.id}" style="color: ${p.category_color}">${p.category_name} (เหลือ ${remaining}/${p.quantity})</option>`;
        });
      }
      const prizeFilter = $('prizeFilter');
      prizeFilter.innerHTML = '<option value="">ทุกรางวัล</option><option value="none">ไม่ระบุรางวัล</option>';
      state.prizeCategories.forEach(p => {
        prizeFilter.innerHTML += `<option value="${p.id}">${p.category_name}</option>`;
      });
    } catch (e) { console.warn('loadSettings:', e); }
  }

  // === Entries ===
  async function loadEntries() {
    try {
      const res = await fetch(`./actions/get_entries.php?raffle_key=${cfg.key}&filter=available`);
      const data = await res.json();
      if (!data.success) return;
      state.allEntries = data.entries;
      renderEntries(data.entries);
      $('remainingCount').textContent = data.counts.available;
    } catch (e) { console.warn('loadEntries:', e); }
  }

  function renderEntries(entries) {
    const list = $('entriesList');
    if (entries.length === 0) {
      list.innerHTML = '<div class="text-center text-muted py-4">ไม่พบรายชื่อ</div>';
      return;
    }
    list.innerHTML = entries.map(e => `
      <div class="entry-item" data-name="${escapeAttr(e.name.toLowerCase())}">
        <span>${escapeHtml(e.name)}</span>
        <button class="delete-btn" onclick="deleteEntry(${e.id}, '${escapeAttr(e.name)}')" aria-label="ลบ ${escapeAttr(e.name)}">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>`).join('');
  }

  global.filterEntries = function () {
    const term = $('entrySearch').value.toLowerCase().trim();
    if (!term) return renderEntries(state.allEntries);
    renderEntries(state.allEntries.filter(e => e.name.toLowerCase().includes(term)));
  };

  global.deleteEntry = function (entryId, entryName) {
    Swal.fire({
      title: 'ลบรายชื่อ?',
      text: `ต้องการลบ "${entryName}" ออกจากรายชื่อหรือไม่?`,
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
      confirmButtonColor: '#ef4444'
    }).then(r => {
      if (!r.isConfirmed) return;
      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fd.append('entry_id', entryId);
      fetch('./actions/delete_entry.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            Swal.fire({ icon: 'success', title: 'ลบแล้ว!', text: `${entryName} ถูกลบออกจากรายชื่อ`, timer: 1500, showConfirmButton: false });
            loadEntries();
          } else {
            Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
          }
        });
    });
  };

  // === Winners ===
  async function loadWinners() {
    try {
      const res = await fetch(`./actions/get_winners.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) return;
      state.allWinners = data.logs;
      state.winnersTotal = data.total;
      renderWinners(data.logs, data.total);
      if (data.logs.length > 0) {
        $('lastWinner').textContent = data.logs[data.logs.length - 1].winner_name;
      }
    } catch (e) { console.warn('loadWinners:', e); }
  }

  function renderWinners(winners, total) {
    const list = $('winnersList');
    if (winners.length === 0) {
      list.innerHTML = '<div class="text-center text-muted py-4">ไม่พบรายชื่อ</div>';
      return;
    }
    list.innerHTML = winners.map((w, i) => {
      const ts = new Date(w.created_at);
      const timeStr = ts.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: '2-digit' });
      const orig = state.allWinners.findIndex(aw => aw.id === w.id);
      return `
        <div class="winner-item" data-name="${escapeAttr(w.winner_name.toLowerCase())}">
          <div>
            <span class="text-muted me-2">#${total - orig}</span>
            <strong>${escapeHtml(w.winner_name)}</strong>
            <div class="text-muted" style="font-size: 0.75rem;">${timeStr}</div>
          </div>
          ${w.prize_name ? `<span class="prize" style="background: ${escapeAttr(w.prize_color)}20; color: ${escapeAttr(w.prize_color)}">${escapeHtml(w.prize_name)}</span>` : ''}
        </div>`;
    }).join('');
  }

  global.filterWinners = function () {
    const term = $('winnerSearch').value.toLowerCase().trim();
    const prizeVal = $('prizeFilter').value;
    let filtered = state.allWinners;
    if (term) filtered = filtered.filter(w => w.winner_name.toLowerCase().includes(term));
    if (prizeVal !== '') {
      if (prizeVal === 'none') filtered = filtered.filter(w => !w.prize_id);
      else filtered = filtered.filter(w => w.prize_id == prizeVal);
    }
    renderWinners(filtered, state.winnersTotal);
  };

  // === Polling handler ===
  function handleState(data) {
    if (!data?.success) return;
    poller.setState(data.state.action);
    $('remainingCount').textContent = data.entries.remaining;

    // Stale spinning detection (>30s)
    if (data.state.action === 'spinning' && data.state.last_updated) {
      const last = new Date(data.state.last_updated.replace(' ', 'T') + '+07:00');
      const stale = (Date.now() - last.getTime()) / 1000;
      if (stale > 30) {
        const fd = new FormData();
        fd.append('raffle_key', cfg.key);
        fd.append('action', 'idle');
        fetch('./actions/set_state.php', { method: 'POST', body: fd });
        return;
      }
    }
    if (data.state.action !== state.currentState) onStateChange(data.state);
    state.currentState = data.state.action;
  }

  function onStateChange(s) {
    const spinBtn = $('spinBtn');
    const confirmBtn = $('confirmBtn');
    const rerollBtn = $('rerollBtn');
    switch (s.action) {
      case 'idle':
        spinBtn.disabled = false;
        spinBtn.classList.remove('spinning');
        spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่ม</span>';
        confirmBtn.disabled = true; rerollBtn.disabled = true;
        $('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>พร้อม';
        state.currentWinner = null; state.currentWinnerId = null;
        break;
      case 'spinning':
        spinBtn.disabled = true;
        spinBtn.classList.add('spinning');
        spinBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>กำลังสุ่ม...</span>';
        confirmBtn.disabled = true; rerollBtn.disabled = true;
        $('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>กำลังสุ่ม...';
        break;
      case 'revealing':
        spinBtn.disabled = true;
        spinBtn.classList.remove('spinning');
        confirmBtn.disabled = false; rerollBtn.disabled = false;
        state.currentWinner = s.winner; state.currentWinnerId = s.winner_id;
        $('currentStatus').innerHTML = `<span class="status-dot online me-2"></span>ผู้โชคดี: <strong class="text-warning">${escapeHtml(s.winner)}</strong>`;
        spinBtn.innerHTML = `<i class="fa-solid fa-star text-warning"></i><span>${escapeHtml(s.winner)}</span>`;
        break;
      case 'confirmed':
        spinBtn.disabled = false;
        spinBtn.classList.remove('spinning');
        spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่มรายการถัดไป</span>';
        confirmBtn.disabled = true; rerollBtn.disabled = true;
        $('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>ยืนยันแล้ว';
        loadEntries(); loadWinners();
        break;
    }
  }

  // === Actions ===
  global.triggerSpin = async function () {
    if (state.currentState === 'confirmed') {
      resetState();
      setTimeout(triggerSpin, 300);
      return;
    }
    if (state.spinTimeoutId) { clearTimeout(state.spinTimeoutId); state.spinTimeoutId = null; }

    const prizeId = $('prizeSelect')?.value || '';
    if (prizeId !== '') {
      const sel = state.prizeCategories.find(p => p.id == prizeId);
      if (sel && (sel.quantity - sel.winners_count) <= 0) {
        Swal.fire('หมดแล้ว', `รางวัล "${sel.category_name}" หมดแล้ว`, 'warning');
        return;
      }
    }

    const spinBtn = $('spinBtn');
    spinBtn.disabled = true;
    spinBtn.classList.add('spinning');
    spinBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>กำลังสุ่ม...</span>';

    try {
      const res = await fetch(`./actions/pick_random.php?raffle_key=${cfg.key}&prize_id=${prizeId}`);
      const data = await res.json();
      if (!data.success) {
        resetSpinUI();
        const fd = new FormData();
        fd.append('raffle_key', cfg.key);
        fd.append('action', 'idle');
        fetch('./actions/set_state.php', { method: 'POST', body: fd });
        Swal.fire('ไม่มีรายชื่อ', data.error || 'ไม่มีรายชื่อให้สุ่มแล้ว', 'warning');
        return;
      }
      state.currentWinner = data.winner.name;
      state.currentWinnerId = data.winner.id;

      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fd.append('action', 'spinning');
      fd.append('triggered_by', 'remote');
      fetch('./actions/set_state.php', { method: 'POST', body: fd });

      const duration = (parseInt(state.settings.spin_duration) || 5) * 1000;
      state.spinTimeoutId = setTimeout(() => {
        state.spinTimeoutId = null;
        const rfd = new FormData();
        rfd.append('raffle_key', cfg.key);
        rfd.append('action', 'revealing');
        rfd.append('winner', state.currentWinner);
        rfd.append('winner_id', state.currentWinnerId);
        rfd.append('prize_id', prizeId);
        fetch('./actions/set_state.php', { method: 'POST', body: rfd });
      }, duration);
    } catch (e) {
      console.error('Spin error:', e);
      resetSpinUI();
      Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    }
  };

  function resetSpinUI() {
    const spinBtn = $('spinBtn');
    spinBtn.disabled = false;
    spinBtn.classList.remove('spinning');
    spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่ม</span>';
  }

  global.confirmWinner = function () {
    if (!state.currentWinner) return;
    const prizeId = $('prizeSelect')?.value || '';
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('winner', state.currentWinner);
    fd.append('winner_id', state.currentWinnerId);
    fd.append('prize_id', prizeId);
    fetch('./actions/confirm_winner.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const sd = new FormData();
          sd.append('raffle_key', cfg.key);
          sd.append('action', 'confirmed');
          sd.append('winner', state.currentWinner);
          sd.append('winner_id', state.currentWinnerId);
          fetch('./actions/set_state.php', { method: 'POST', body: sd });
          loadSettings();
          Swal.fire({ icon: 'success', title: 'ยืนยันแล้ว!', text: `${state.currentWinner} ได้รับรางวัล`, timer: 2000, showConfirmButton: false });
        } else {
          Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
        }
      });
  };

  global.reroll = function () { resetState(); setTimeout(triggerSpin, 300); };

  global.resetState = function () {
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('action', 'idle');
    fetch('./actions/set_state.php', { method: 'POST', body: fd });
  };

  global.refreshMainPage = function () {
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('action', 'refresh');
    fetch('./actions/set_state.php', { method: 'POST', body: fd }).then(() => {
      Swal.fire({ icon: 'success', title: 'สั่ง Refresh แล้ว!', text: 'หน้าหลักจะ Refresh ภายในไม่กี่วินาที', timer: 1500, showConfirmButton: false });
      setTimeout(() => global.resetState(), 1000);
    });
  };

  global.undoWinner = function () {
    Swal.fire({
      title: 'ยกเลิกผู้โชคดีล่าสุด?',
      text: 'ผู้โชคดีล่าสุดจะถูกนำกลับเข้ารายชื่อ',
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
    }).then(r => {
      if (!r.isConfirmed) return;
      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      fetch('./actions/undo_winner.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            Swal.fire({ icon: 'success', title: 'ยกเลิกแล้ว!', text: `${data.undone_winner} ถูกนำกลับเข้ารายชื่อ`, timer: 2000, showConfirmButton: false });
            loadEntries(); loadWinners();
          } else {
            Swal.fire('Error', data.error || 'ไม่มีรายการให้ยกเลิก', 'error');
          }
        });
    });
  };

  global.showTab = function (tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`[onclick="showTab('${tab}')"]`)?.classList.add('active');
    $('entriesTab')?.classList.toggle('active', tab === 'entries');
    $('winnersTab')?.classList.toggle('active', tab === 'winners');
    if (tab === 'entries') loadEntries();
    if (tab === 'winners') loadWinners();
  };

  // === Helpers ===
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }
  function escapeAttr(s) {
    return escapeHtml(s).replace(/'/g, '&#39;');
  }
})(typeof window !== 'undefined' ? window : this);
