/**
 * PWA runtime — Yuwa Raffle
 *  - registers ./sw.js (secure contexts only: https or localhost)
 *  - "install app" buttons: any element with [data-pwa-install]
 *  - update toast when a new service worker is waiting
 *  - online/offline toast + body.is-offline
 *  - remembers recent games (body[data-raffle-key]) for the launcher
 * Exposes window.GamePWA = { toast, getRecent, removeRecent }.
 */
(function (global) {
  'use strict';

  const RECENT_KEY = 'yuwaRaffle.recent';
  const RECENT_MAX = 8;
  let deferredPrompt = null;
  let region = null;

  // ---------------------------------------------------------------- toasts
  function getRegion() {
    if (region && document.body.contains(region)) return region;
    region = document.createElement('div');
    region.className = 'game-toast-region';
    region.setAttribute('role', 'status');
    region.setAttribute('aria-live', 'polite');
    document.body.appendChild(region);
    return region;
  }

  const TOAST_ICON = { info: 'fa-circle-info', success: 'fa-check', warning: 'fa-wifi-slash', error: 'fa-triangle-exclamation' };

  /**
   * @param {string} message
   * @param {{type?:string, icon?:string, duration?:number, action?:{label:string,onClick:Function}}} [opts]
   */
  function toast(message, opts = {}) {
    const type = opts.type || 'info';
    const t = document.createElement('div');
    t.className = 'game-toast is-' + type;
    const icon = document.createElement('i');
    icon.className = 'fa-solid ' + (opts.icon || TOAST_ICON[type] || TOAST_ICON.info);
    icon.setAttribute('aria-hidden', 'true');
    const msg = document.createElement('span');
    msg.className = 'toast-msg';
    msg.textContent = message;
    t.append(icon, msg);
    if (opts.action) {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn-game is-gold is-sm';
      b.textContent = opts.action.label;
      b.addEventListener('click', () => { t.remove(); opts.action.onClick(); });
      t.appendChild(b);
    }
    getRegion().appendChild(t);
    const duration = opts.duration ?? (opts.action ? 0 : 4000);
    if (duration > 0) setTimeout(() => t.remove(), duration);
    return t;
  }

  // ---------------------------------------------------------- recent games
  function getRecent() {
    try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]') || []; }
    catch (_) { return []; }
  }
  function saveRecent(list) {
    try { localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_MAX))); } catch (_) {}
  }
  function rememberGame(key, title) {
    if (!/^[a-f0-9]{16,64}$/i.test(key)) return;
    const list = getRecent().filter((g) => g.key !== key);
    list.unshift({ key, title: title || 'Raffle Game', at: Date.now() });
    saveRecent(list);
  }
  function removeRecent(key) {
    saveRecent(getRecent().filter((g) => g.key !== key));
  }

  // -------------------------------------------------------- install prompt
  function setInstallVisible(show) {
    document.querySelectorAll('[data-pwa-install]').forEach((b) => { b.hidden = !show; });
  }

  global.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    setInstallVisible(true);
  });

  global.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    setInstallVisible(false);
    toast('ติดตั้งแอปเรียบร้อย! เปิดได้จากหน้าจอหลัก', { type: 'success' });
  });

  async function promptInstall() {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    try { await deferredPrompt.userChoice; } catch (_) {}
    deferredPrompt = null;
    setInstallVisible(false);
  }

  // ------------------------------------------------------- service worker
  function registerSW() {
    if (!('serviceWorker' in navigator) || !global.isSecureContext) return;
    const swUrl = new URL('sw.js', document.baseURI.replace(/\/offline\/.*$/, '/'));
    navigator.serviceWorker.register(swUrl.href, { scope: new URL('./', swUrl).href }).then((reg) => {
      const offerUpdate = (worker) => {
        toast('มีเวอร์ชันใหม่ของเกมพร้อมแล้ว', {
          icon: 'fa-sparkles',
          action: { label: 'อัปเดต', onClick: () => worker.postMessage({ type: 'SKIP_WAITING' }) },
        });
      };
      if (reg.waiting && navigator.serviceWorker.controller) offerUpdate(reg.waiting);
      reg.addEventListener('updatefound', () => {
        const nw = reg.installing;
        if (!nw) return;
        nw.addEventListener('statechange', () => {
          if (nw.state === 'installed' && navigator.serviceWorker.controller) offerUpdate(nw);
        });
      });
    }).catch((err) => console.warn('SW register failed:', err));

    let reloading = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (reloading) return;
      reloading = true;
      global.location.reload();
    });
  }

  // --------------------------------------------------------- network state
  function syncNetwork(announce) {
    const offline = !navigator.onLine;
    document.body.classList.toggle('is-offline', offline);
    if (!announce) return;
    if (offline) toast('ออฟไลน์อยู่ — การสุ่มออนไลน์จะกลับมาเมื่อเชื่อมต่อได้', { type: 'warning' });
    else toast('กลับมาออนไลน์แล้ว', { type: 'success', icon: 'fa-wifi' });
  }

  // ------------------------------------------------------------------ init
  function init() {
    const body = document.body;
    if (body.dataset.raffleKey) rememberGame(body.dataset.raffleKey, body.dataset.raffleTitle);

    document.querySelectorAll('[data-pwa-install]').forEach((b) => {
      b.hidden = !deferredPrompt;
      b.addEventListener('click', promptInstall);
    });

    syncNetwork(false);
    global.addEventListener('online', () => syncNetwork(true));
    global.addEventListener('offline', () => syncNetwork(true));

    registerSW();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  global.GamePWA = { toast, getRecent, removeRecent, promptInstall };
})(window);
