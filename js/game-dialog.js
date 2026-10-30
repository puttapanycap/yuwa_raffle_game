/**
 * GameDialog — cartoon-styled, SweetAlert2-compatible subset.
 * Replaces the 3.6 MB plugins bundle; exposes window.Swal with:
 *   Swal.fire(title, text, icon)
 *   Swal.fire({ title, text, html, icon, showCancelButton, showConfirmButton,
 *               confirmButtonText, cancelButtonText, timer, allowOutsideClick,
 *               didOpen }) -> Promise<{ isConfirmed, isDismissed, dismiss }>
 *   Swal.showLoading(), Swal.close(), Swal.isVisible()
 * Accessible: role=alertdialog, focus trap, Esc to dismiss, focus restore.
 */
(function (global) {
  'use strict';

  const ICONS = {
    success: 'fa-check',
    warning: 'fa-exclamation',
    error: 'fa-xmark',
    question: 'fa-question',
    info: 'fa-info',
  };

  let current = null; // { root, resolve, timerId, opts, prevFocus }
  let uid = 0;

  function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function finish(result, { animate = true } = {}) {
    if (!current) return;
    const c = current;
    current = null;
    clearTimeout(c.timerId);
    document.removeEventListener('keydown', c.onKey, true);
    const remove = () => c.root.remove();
    if (animate) {
      c.root.classList.add('is-closing');
      setTimeout(remove, 130);
    } else {
      remove();
    }
    if (c.prevFocus && document.contains(c.prevFocus)) {
      try { c.prevFocus.focus({ preventScroll: true }); } catch (_) {}
    }
    c.resolve(result);
  }

  function dismiss(reason) {
    finish({ isConfirmed: false, isDismissed: true, dismiss: reason, value: undefined });
  }

  function fire(a, b, c) {
    const opts = typeof a === 'object' && a !== null ? Object.assign({}, a) : { title: a, text: b, icon: c };
    if (opts.showConfirmButton === undefined) opts.showConfirmButton = true;
    if (opts.allowOutsideClick === undefined) opts.allowOutsideClick = true;

    let prevFocus = document.activeElement;
    if (current) {
      prevFocus = current.prevFocus;
      finish({ isConfirmed: false, isDismissed: true, dismiss: 'replaced' }, { animate: false });
    }

    return new Promise((resolve) => {
      const id = 'gd-' + (++uid);
      const root = el('div', 'gd-backdrop');
      const dlg = el('div', 'gd-dialog');
      dlg.setAttribute('role', 'alertdialog');
      dlg.setAttribute('aria-modal', 'true');
      dlg.setAttribute('aria-labelledby', id + '-t');
      dlg.setAttribute('aria-describedby', id + '-d');
      dlg.tabIndex = -1;

      if (opts.icon && ICONS[opts.icon]) {
        const ic = el('div', 'gd-icon is-' + opts.icon);
        ic.setAttribute('aria-hidden', 'true');
        ic.innerHTML = '<i class="fa-solid ' + ICONS[opts.icon] + '"></i>';
        dlg.appendChild(ic);
      } else {
        dlg.style.paddingTop = '26px';
        dlg.style.marginTop = '0';
      }

      const title = el('h2', 'gd-title', opts.title || '');
      title.id = id + '-t';
      dlg.appendChild(title);

      const body = el('div', 'gd-text');
      body.id = id + '-d';
      if (opts.html) body.innerHTML = opts.html; // trusted, app-authored strings only
      else if (opts.text) body.textContent = opts.text;
      dlg.appendChild(body);

      const loader = el('div', 'gd-loader hide');
      loader.setAttribute('role', 'status');
      loader.setAttribute('aria-label', 'กำลังดำเนินการ');
      dlg.appendChild(loader);

      const actions = el('div', 'gd-actions');
      let confirmBtn = null;
      if (opts.showCancelButton) {
        const cancel = el('button', 'btn-game is-plain', opts.cancelButtonText || 'ยกเลิก');
        cancel.type = 'button';
        cancel.addEventListener('click', () => dismiss('cancel'));
        actions.appendChild(cancel);
      }
      if (opts.showConfirmButton) {
        const isDanger = opts.icon === 'warning' && opts.showCancelButton;
        confirmBtn = el('button', 'btn-game ' + (isDanger ? 'is-cherry' : 'is-gold'), opts.confirmButtonText || 'ตกลง');
        confirmBtn.type = 'button';
        confirmBtn.addEventListener('click', () => finish({ isConfirmed: true, isDismissed: false, value: true }));
        actions.appendChild(confirmBtn);
      }
      if (actions.children.length) dlg.appendChild(actions);

      if (opts.timer) {
        const bar = el('div', 'gd-timer');
        bar.style.setProperty('--gd-timer', opts.timer + 'ms');
        bar.setAttribute('aria-hidden', 'true');
        dlg.appendChild(bar);
      }

      root.appendChild(dlg);
      root.addEventListener('mousedown', (e) => {
        if (e.target === root && current && current.opts.allowOutsideClick) dismiss('backdrop');
      });

      const onKey = (e) => {
        if (!current) return;
        if (e.key === 'Escape' && current.opts.allowOutsideClick) {
          e.preventDefault();
          dismiss('esc');
        } else if (e.key === 'Tab') {
          const f = dlg.querySelectorAll('button:not([disabled]):not(.hide)');
          if (!f.length) { e.preventDefault(); return; }
          const first = f[0], last = f[f.length - 1];
          if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
          else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
      };
      document.addEventListener('keydown', onKey, true);

      current = { root, dlg, loader, actions, resolve, opts, prevFocus, onKey, timerId: null };
      document.body.appendChild(root);

      (confirmBtn || dlg).focus({ preventScroll: true });

      if (opts.timer) {
        current.timerId = setTimeout(() => dismiss('timer'), opts.timer);
      }
      if (typeof opts.didOpen === 'function') {
        try { opts.didOpen(dlg); } catch (err) { console.warn(err); }
      }
    });
  }

  function showLoading() {
    if (!current) return;
    current.loader.classList.remove('hide');
    current.actions.classList.add('hide');
    current.dlg.setAttribute('aria-busy', 'true');
  }

  global.GameDialog = {
    fire,
    showLoading,
    close: () => dismiss('close'),
    isVisible: () => !!current,
  };
  // Drop-in compatibility for existing page modules
  global.Swal = global.GameDialog;
})(window);
