/**
 * Raffle Settings Page — extracted from settings.php inline script.
 * Public globals required:
 *   window.SETTINGS_CONFIG = { key, baseUrl, initialLocked }
 */
(function (global) {
  'use strict';

  const cfg = global.SETTINGS_CONFIG || {};
  if (!cfg.key) { console.warn('RaffleSettings: missing SETTINGS_CONFIG.key'); return; }

  let isLocked = !!cfg.initialLocked;

  // Set link values
  const setLinks = () => {
    if ($('linkMain'))   $('linkMain').value   = cfg.baseUrl + 'index.php?share=' + cfg.key;
    if ($('linkVIP'))    $('linkVIP').value    = cfg.baseUrl + 'button.php?share=' + cfg.key;
    if ($('linkRemote')) $('linkRemote').value = cfg.baseUrl + 'remote.php?share=' + cfg.key;
  };

  const $ = (id) => document.getElementById(id);

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    setLinks();
    loadSettings();
    updateLockUI();
    bindUI();
    loadNames();
    $('namesList')?.addEventListener('input', updateNamesCount);
  }

  function bindUI() {
    // Range ↔ Number sync
    [['fontSizeRange', 'fontSize'],
     ['entryFontSizeRange', 'entryFontSize'],
     ['winnerFontSizeRange', 'winnerFontSize']].forEach(([r, n]) => {
      const rEl = $(r), nEl = $(n);
      if (!rEl || !nEl) return;
      rEl.addEventListener('input', () => nEl.value = rEl.value);
      nEl.addEventListener('input', () => rEl.value = nEl.value);
    });

    // Radio cards
    document.querySelectorAll('.radio-card').forEach(card => {
      card.addEventListener('click', function () {
        const name = this.querySelector('input').name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(r => r.closest('.radio-card').classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
      });
    });

    // Theme live preview
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
      radio.addEventListener('change', function () { document.body.setAttribute('data-theme', this.value); });
    });

    // Prize categories toggle
    $('enablePrizeCategories')?.addEventListener('change', function () {
      $('prizeCategoriesSection').style.display = this.checked ? 'block' : 'none';
    });
  }

  async function loadSettings() {
    try {
      const res = await fetch(`./actions/get_settings.php?raffle_key=${cfg.key}`);
      const data = await res.json();
      if (!data.success) return;
      const s = data.settings;
      $('eventTitle').value = s.event_title || 'Raffle Game';
      syncVal('fontSize', 'fontSizeRange', s.result_font_size || 72);
      $('resultFontColor').value = s.result_font_color || '#ffffff';
      syncVal('entryFontSize', 'entryFontSizeRange', s.entry_font_size || 16);
      syncVal('winnerFontSize', 'winnerFontSizeRange', s.winner_font_size || 16);
      $('spinDuration').value = s.spin_duration || 5;
      $('showMainButton').checked = s.show_main_button == 1;
      $('enableConfetti').checked = s.enable_confetti == 1;
      $('enableSound').checked = s.enable_sound == 1;
      $('enablePrizeCategories').checked = s.enable_prize_categories == 1;

      if (s.animation_template === 'wheel') {
        document.querySelector('input[value="wheel"]').checked = true;
        $('templateWheel')?.classList.add('selected');
      } else {
        document.querySelector('input[value="text_roll"]').checked = true;
        $('templateTextRoll')?.classList.add('selected');
      }

      if (s.theme === 'light') {
        document.querySelector('input[value="light"]').checked = true;
        $('themeLight')?.classList.add('selected');
        document.body.setAttribute('data-theme', 'light');
      } else {
        document.querySelector('input[value="dark"]').checked = true;
        $('themeDark')?.classList.add('selected');
      }

      if (s.enable_prize_categories == 1) $('prizeCategoriesSection').style.display = 'block';

      if (data.custom_sounds?.spin)   $('currentSpinSound').textContent = data.custom_sounds.spin.file_name;
      if (data.custom_sounds?.winner) $('currentWinnerSound').textContent = data.custom_sounds.winner.file_name;

      if (data.prize_categories.length > 0) {
        $('prizeList').value = data.prize_categories.map(c => `${c.category_name} | ${c.quantity} | ${c.category_color}`).join('\n');
      }

      isLocked = data.is_locked;
      updateLockUI();
    } catch (e) { console.warn('loadSettings:', e); }
  }

  function syncVal(numId, rangeId, val) {
    if ($(numId))   $(numId).value = val;
    if ($(rangeId)) $(rangeId).value = val;
  }

  global.saveSettings = function () {
    if (isLocked) return Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('event_title', $('eventTitle').value);
    fd.append('result_font_size', $('fontSize').value);
    fd.append('result_font_color', $('resultFontColor').value);
    fd.append('entry_font_size', $('entryFontSize').value);
    fd.append('winner_font_size', $('winnerFontSize').value);
    fd.append('spin_duration', $('spinDuration').value);
    fd.append('show_main_button', $('showMainButton').checked ? 1 : 0);
    fd.append('animation_template', document.querySelector('input[name="template"]:checked').value);
    fd.append('theme', document.querySelector('input[name="theme"]:checked').value);
    fd.append('enable_confetti', $('enableConfetti').checked ? 1 : 0);
    fd.append('enable_sound', $('enableSound').checked ? 1 : 0);
    fd.append('enable_prize_categories', $('enablePrizeCategories').checked ? 1 : 0);

    if ($('enablePrizeCategories').checked) {
      const lines = $('prizeList').value.split('\n').filter(l => l.trim());
      const categories = lines.map(line => {
        const parts = line.split('|').map(p => p.trim());
        return { name: parts[0] || 'Prize', quantity: parseInt(parts[1]) || 1, color: parts[2] || '#FFD700' };
      });
      fd.append('prize_categories', JSON.stringify(categories));
    }

    fetch('./actions/save_settings.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'การตั้งค่าถูกบันทึกแล้ว', timer: 2000, showConfirmButton: false });
        else Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
      });
  };

  global.loadNames = function () {
    fetch(`./actions/get_entries.php?raffle_key=${cfg.key}&filter=all`)
      .then(r => r.json())
      .then(data => {
        if (data.success && data.entries) {
          $('namesList').value = data.entries.map(e => e.name).join('\n');
          updateNamesCount();
        }
      })
      .catch(e => console.error('loadNames error:', e));
  };

  global.saveNames = function () {
    if (isLocked) return Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
    const names = $('namesList').value.split('\n').map(n => n.trim()).filter(Boolean);
    Swal.fire({
      title: 'ยืนยันการบันทึก?',
      html: `จะบันทึก ${names.length} รายชื่อ<br><small class="text-muted">รายชื่อเดิมจะถูกแทนที่ทั้งหมด</small>`,
      icon: 'question', showCancelButton: true,
      confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก'
    }).then(r => {
      if (!r.isConfirmed) return;
      const fd = new FormData();
      fd.append('raffle_key', cfg.key);
      names.forEach(name => fd.append('names[]', name));
      Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
      fetch('./actions/save_entries.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(() => {
          Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ!', text: `บันทึก ${names.length} รายชื่อแล้ว`, timer: 2000, showConfirmButton: false });
          updateNamesCount();
        })
        .catch(e => Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error'));
    });
  };

  function updateNamesCount() {
    const count = $('namesList').value.split('\n').filter(n => n.trim() !== '').length;
    $('namesCount').textContent = `${count} รายชื่อ`;
  }

  function parseExcludeNumbers(s) {
    if (!s || !s.trim()) return [];
    const set = new Set();
    s.split(',').forEach(part => {
      part = part.trim();
      if (part.includes('-')) {
        const [a, b] = part.split('-').map(x => parseInt(x.trim()));
        if (!isNaN(a) && !isNaN(b)) for (let i = Math.min(a, b); i <= Math.max(a, b); i++) set.add(i);
      } else {
        const n = parseInt(part);
        if (!isNaN(n)) set.add(n);
      }
    });
    return Array.from(set);
  }

  global.generateNumbers = function () {
    if (isLocked) return Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
    const start = parseInt($('autoStart').value) || 1;
    const end = parseInt($('autoEnd').value) || 100;
    const prefix = $('autoPrefix').value;
    const clearExisting = $('clearExisting').checked;
    const excludes = parseExcludeNumbers($('excludeNumbers').value);
    let actual = 0;
    for (let i = start; i <= end; i++) if (!excludes.includes(i)) actual++;
    if (actual > 10000) return Swal.fire('Error', 'สร้างได้สูงสุด 10,000 ฉลากต่อครั้ง', 'error');

    let html = `จะสร้างฉลาก ${prefix}${start} ถึง ${prefix}${end}`;
    if (excludes.length) html += `<br><small class="text-warning">ยกเว้น ${excludes.length} ตัวเลข</small>`;
    html += `<br>(${actual} ฉลาก)`;

    Swal.fire({ title: 'ยืนยันการสร้างฉลาก?', html, icon: 'question', showCancelButton: true, confirmButtonText: 'สร้างเลย', cancelButtonText: 'ยกเลิก' })
      .then(r => {
        if (!r.isConfirmed) return;
        const fd = new FormData();
        fd.append('raffle_key', cfg.key);
        fd.append('start', start); fd.append('end', end); fd.append('prefix', prefix);
        fd.append('clear_existing', clearExisting ? '1' : '0');
        fd.append('exclude_numbers', JSON.stringify(excludes));
        Swal.fire({ title: 'กำลังสร้าง...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('./actions/generate_numbers.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(data => {
            if (data.success) Swal.fire({ icon: 'success', title: 'สร้างสำเร็จ!', text: `สร้าง ${data.generated} ฉลากแล้ว`, timer: 2000, showConfirmButton: false });
            else Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
          });
      });
  };

  global.uploadSound = function (type, input) {
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append('sound_type', type);
    fd.append('sound_file', input.files[0]);
    Swal.fire({ title: 'กำลังอัพโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch('./actions/upload_sound.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'อัพโหลดสำเร็จ!', timer: 1500, showConfirmButton: false });
          if (type === 'spin')   $('currentSpinSound').textContent = data.sound.filename;
          if (type === 'winner') $('currentWinnerSound').textContent = data.sound.filename;
        } else {
          Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
        }
      });
    input.value = '';
  };

  global.toggleLock = function () {
    const fd = new FormData();
    fd.append('raffle_key', cfg.key);
    fd.append(isLocked ? 'unlock' : 'lock', '1');
    fetch('./actions/save_settings.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          isLocked = !isLocked;
          updateLockUI();
          Swal.fire({ icon: 'success', title: isLocked ? 'ล็อคแล้ว' : 'ปลดล็อคแล้ว', timer: 1500, showConfirmButton: false });
        }
      });
  };

  function updateLockUI() {
    const btn = $('lockBtn');
    const warn = $('lockWarning');
    if (isLocked) {
      btn.innerHTML = '<i class="fa-solid fa-unlock me-1"></i>ปลดล็อค';
      btn.classList.remove('btn-raffle-danger');
      btn.classList.add('btn-raffle-success');
      warn.style.display = 'flex';
    } else {
      btn.innerHTML = '<i class="fa-solid fa-lock me-1"></i>ล็อค';
      btn.classList.remove('btn-raffle-success');
      btn.classList.add('btn-raffle-danger');
      warn.style.display = 'none';
    }
  }

  global.copyLink = function (id) {
    const text = $(id).value;
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(showCopySuccess).catch(() => fallbackCopy(text));
    } else {
      fallbackCopy(text);
    }
  };

  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); showCopySuccess(); }
    catch { Swal.fire({ icon: 'error', title: 'ไม่สามารถคัดลอกได้', text: 'กรุณาคัดลอกด้วยตนเอง (Ctrl+C)', timer: 2000, showConfirmButton: false }); }
    document.body.removeChild(ta);
  }

  function showCopySuccess() {
    Swal.fire({ icon: 'success', title: 'คัดลอกแล้ว!', timer: 1000, showConfirmButton: false });
  }

  global.openLink = function (id) { window.open($(id).value, '_blank'); };

  global.showQR = function (title, id) {
    const url = $(id).value;
    const qr = qrcode(0, 'M');
    qr.addData(url); qr.make();
    $('qrTitle').textContent = title;
    $('qrCode').innerHTML = qr.createImgTag(6);
    $('qrLink').textContent = url;
    $('qrModal').classList.add('show');
  };

  global.closeQR = function (e) {
    if (!e || e.target === $('qrModal')) $('qrModal').classList.remove('show');
  };
})(typeof window !== 'undefined' ? window : this);
