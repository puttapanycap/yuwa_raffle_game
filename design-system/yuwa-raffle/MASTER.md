# Yuwa Raffle — Cartoon Game UI (Master)

Source of truth for every page. Implementation: [`css/cartoon-game.css`](../../css/cartoon-game.css).
Page-specific overrides go in `design-system/yuwa-raffle/pages/<page>.md`.

## Direction
Mobile-game / game-show look: **thick ink outlines, hard "toy" drop shadows (no blur), glossy candy buttons,
sunburst backgrounds, marquee-bulb stage**. Derived from ui-ux-pro-max *Vibrant & Block-based* + *Claymorphism*.

## Tokens
| Role | Token | Value |
|---|---|---|
| Outline (ink) | `--c-ink` / `--outline` | `#1E1240` (dark: `#12062E`) |
| Primary | `--c-grape-500` | `#7C3AED` (white text 5.7:1) |
| CTA / reward | `--c-sun-400` | `#FFC93C` (ink text) |
| Success | `--c-mint-500` | `#2ED69A` (ink text) |
| Danger | `--c-cherry-500` | `#E11D48` (white text) |
| Info | `--c-sky-500` | `#38BDF8` (ink text) |
| Accent | `--c-bubble-500`, `--c-tangerine-400` | `#FF5DA2`, `#FF8A3D` |

Themes (`body[data-theme]`): **dark** = night arcade (default; `aurora` maps here), **light** = sunny sky.
Semantic tokens per theme: `--bg --surface --surface-2 --surface-3 --text --text-muted --focus --scrim --tint-1..5`.
The stage "screen" is always dark so the user-chosen result colour (default white) stays readable.

## Shape & elevation
- Outline `3px` (`--bw`), hero elements `4–6px`.
- Radius: 12 / 16 / 24 / 32 / full.
- Elevation = solid offset shadow: `--pop-1` 3px, `--pop-2` 5px, `--pop-3` 8px. Never blurred shadows.
- Buttons: top gloss `inset 0 3px 0 rgba(255,255,255,.45)` + bottom edge `inset 0 -5px 0 <dark>`; press = `translateY(4px)` and shadow collapses.

## Type
- Display: **Fredoka** (Latin/digits) → **Mitr** (Thai) — headings, buttons, numbers.
- Body: **LINE Seed Sans TH** (local woff2, `font-display: swap`).
- Body 16px / 1.55. Result text: `min(var(--result-font-size), 15cqi)` (container-relative, word-level wrap).

## Components (class → use)
`btn-game` (+ `is-gold|is-mint|is-cherry|is-sky|is-bubble|is-plain`, `is-sm|is-lg|is-xl|is-block`),
legacy aliases `btn-raffle*` · `btn-icon` · `game-panel` + `panel-head.is-<color>` · `count-badge` ·
`hud`, `hud-title`, `hud-chip` · `stage`, `stage-lights`, `stage-screen` · `btn-spin-mega` · `btn-spin-vip` ·
`control-btn` · `tab-buttons` · `toggle-switch` · `radio-card` · `form-control-raffle` · `game-toast` · dialogs via `Swal.fire()` (js/game-dialog.js).

## Motion
Spring `cubic-bezier(.34,1.56,.64,1)` for arrivals, 110ms press, 160–280ms state changes.
Body `data-game-state` (`idle|spinning|revealing|confirmed`) drives stage effects (bulb speed, winner sunburst).
All decorative loops stop under `prefers-reduced-motion`.

## Rules
- Icons: Font Awesome 6 Pro solid only; no emoji as icons. Decorative icons `aria-hidden="true"`.
- Touch targets ≥ 44px; focus ring 4px `--focus`.
- Z-index scale: bg 0 · content 1 · hud 20 · fab 30 · backdrop 40 · drawer 50 · modal 60 · toast 70 · fx 80.
- Breakpoints: ≥1600 wide columns · 1024–1199 narrow columns · <1024 stage only + bottom sheets · <768 stacked HUD.

## PWA
`manifest.webmanifest` (start_url `start.html`), `sw.js` (shell precache, network-first pages, live `/actions/`),
`js/pwa.js` (install button `[data-pwa-install]`, update/online toasts, recent games). Bump `VERSION` in `sw.js` on each deploy.
