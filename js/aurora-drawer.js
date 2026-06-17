/**
 * Aurora Drawer — Bottom sheet drawer with swipe + backdrop + a11y
 * @description Wires up data-target triggers to .aurora-drawer panels.
 *              Supports: click trigger, click backdrop, click close button,
 *              ESC key, swipe-down to dismiss, focus trap.
 *
 * Markup:
 *   <button class="drawer-trigger" data-target="my-drawer">…</button>
 *   <aside class="aurora-drawer" id="my-drawer" role="dialog" aria-modal="true" aria-hidden="true">
 *     <div class="aurora-drawer-handle"></div>
 *     <div class="aurora-drawer-header">…</div>
 *     <div class="aurora-drawer-body">…</div>
 *   </aside>
 *   <div class="aurora-drawer-backdrop" data-action="close-drawer"></div>
 */
(function (global) {
  'use strict';

  class AuroraDrawer {
    constructor(drawerEl, backdropEl) {
      if (!drawerEl) return null;
      this.el = drawerEl;
      this.backdrop = backdropEl;
      this.isOpen = false;
      this._lastFocus = null;
      this._touchStartY = null;
      this._bind();
    }

    _bind() {
      this.backdrop?.addEventListener('click', () => this.close());

      // Close buttons inside drawer
      this.el.querySelectorAll('[data-action="close-drawer"]').forEach(btn => {
        btn.addEventListener('click', () => this.close());
      });

      // ESC key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) this.close();
      });

      // Swipe down to close
      const handle = this.el.querySelector('.aurora-drawer-handle');
      if (handle) {
        handle.addEventListener('touchstart', (e) => {
          this._touchStartY = e.touches[0].clientY;
        }, { passive: true });

        handle.addEventListener('touchmove', (e) => {
          if (this._touchStartY == null) return;
          const dy = e.touches[0].clientY - this._touchStartY;
          if (dy > 0) {
            this.el.style.transform = `translateY(${dy}px)`;
            this.el.style.transition = 'none';
          }
        }, { passive: true });

        const endSwipe = (e) => {
          if (this._touchStartY == null) return;
          const dy = (e.changedTouches?.[0]?.clientY ?? this._touchStartY) - this._touchStartY;
          this.el.style.transition = '';
          this.el.style.transform = '';
          if (dy > 80) this.close();
          this._touchStartY = null;
        };
        handle.addEventListener('touchend', endSwipe, { passive: true });
        handle.addEventListener('touchcancel', endSwipe, { passive: true });
      }
    }

    open() {
      if (this.isOpen) return;
      this._lastFocus = document.activeElement;
      this.el.classList.add('open');
      this.el.setAttribute('aria-hidden', 'false');
      this.backdrop?.classList.add('open');
      this.isOpen = true;
      document.body.classList.add('no-scroll');

      // Focus the close button or first focusable
      setTimeout(() => {
        const target = this.el.querySelector('[data-action="close-drawer"]')
                    || this.el.querySelector('button, a, input, [tabindex]');
        target?.focus();
      }, 350);
    }

    close() {
      if (!this.isOpen) return;
      this.el.classList.remove('open');
      this.el.setAttribute('aria-hidden', 'true');
      this.backdrop?.classList.remove('open');
      this.isOpen = false;
      document.body.classList.remove('no-scroll');
      this._lastFocus?.focus?.();
    }

    toggle() { this.isOpen ? this.close() : this.open(); }
  }

  /**
   * Initialize all drawers on the page based on .drawer-trigger [data-target]
   * and a single .aurora-drawer-backdrop element.
   */
  function initAll() {
    const backdrop = document.querySelector('.aurora-drawer-backdrop');
    const triggers = document.querySelectorAll('.drawer-trigger[data-target]');
    const drawers = new Map();

    triggers.forEach(trigger => {
      const id = trigger.dataset.target;
      const drawerEl = document.getElementById(id);
      if (!drawerEl) {
        console.warn(`AuroraDrawer: #${id} not found`);
        return;
      }
      if (!drawers.has(id)) {
        drawers.set(id, new AuroraDrawer(drawerEl, backdrop));
      }
      const drawer = drawers.get(id);
      trigger.addEventListener('click', () => {
        // Close any other open drawer first
        drawers.forEach((d, k) => { if (k !== id) d.close(); });
        drawer.toggle();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }

  global.AuroraDrawer = AuroraDrawer;
})(typeof window !== 'undefined' ? window : this);
