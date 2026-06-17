/**
 * Aurora Poller — Adaptive Polling with Page Visibility API
 * @description Polls a fetch function with adaptive intervals and pauses
 *              when the tab is hidden to save battery and bandwidth.
 *
 * Usage:
 *   const poller = new AuroraPoller({
 *     fetchFn: () => fetch('./api').then(r => r.json()),
 *     onData:  (data) => { ... },
 *     onError: (err)  => { ... },
 *   });
 *   poller.start();
 *   poller.setState('spinning'); // change cadence
 *   poller.stop();
 */
(function (global) {
  'use strict';

  class AuroraPoller {
    constructor(options = {}) {
      this.fetchFn  = options.fetchFn  || (() => Promise.resolve(null));
      this.onData    = options.onData   || (() => {});
      this.onError   = options.onError  || ((e) => console.warn('AuroraPoller error:', e));

      // Adaptive intervals per state (ms)
      this.intervals = Object.assign({
        idle:      2000,
        revealing: 500,
        spinning:  250,
        confirmed: 2000,
      }, options.intervals || {});

      this.currentState = 'idle';
      this.timer = null;
      this.isVisible = !document.hidden;
      this.isRunning = false;
      this.lastData = null;
      this.consecutiveErrors = 0;
    }

    start() {
      if (this.isRunning) return;
      this.isRunning = true;
      this._bindVisibility();
      this._scheduleNext(0); // immediate first call
    }

    stop() {
      this.isRunning = false;
      if (this.timer) {
        clearTimeout(this.timer);
        this.timer = null;
      }
    }

    setState(state) {
      if (this.intervals[state] === undefined) {
        console.warn(`AuroraPoller: unknown state "${state}"`);
        return;
      }
      const changed = this.currentState !== state;
      this.currentState = state;
      // If already running, restart cadence immediately
      if (changed && this.isRunning) {
        this._scheduleNext(0);
      }
    }

    _scheduleNext(delay) {
      if (!this.isRunning) return;
      if (this.timer) clearTimeout(this.timer);
      this.timer = setTimeout(() => this._tick(), delay);
    }

    async _tick() {
      if (!this.isRunning) return;
      if (!this.isVisible) {
        // Skip while hidden, wait until visible
        this._scheduleNext(1000);
        return;
      }
      try {
        const data = await this.fetchFn();
        this.consecutiveErrors = 0;
        this.lastData = data;
        this.onData(data);
      } catch (e) {
        this.consecutiveErrors++;
        this.onError(e, this.consecutiveErrors);
      }
      const interval = this.intervals[this.currentState] || 2000;
      // Backoff on consecutive errors (max 10s)
      const backoff = Math.min(this.consecutiveErrors * 1000, 10000);
      this._scheduleNext(interval + backoff);
    }

    _bindVisibility() {
      document.addEventListener('visibilitychange', () => {
        this.isVisible = !document.hidden;
        if (this.isVisible && this.isRunning) {
          // Fire immediately on becoming visible
          this._scheduleNext(0);
        }
      }, { passive: true });
    }
  }

  // Export
  global.AuroraPoller = AuroraPoller;
})(typeof window !== 'undefined' ? window : this);
