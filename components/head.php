<?php
/**
 * Shared <head> — Cartoon Game UI + PWA
 * Each page adds its own <title> after including this file.
 */
// Cache-bust by file mtime so CSS edits reach browsers (and the SW) immediately
$__assetVer = @filemtime(__DIR__ . '/../css/cartoon-game.css') ?: '1';
?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="description" content="เกมจับฉลากสุ่มชื่อ (Raffle drawn game) | โรงพยาบาลยุวประสาทไวทโยปถัมภ์" />
<meta name="keywords" content="เกม, จับฉลาก, สุ่มชื่อ, raffle, lucky draw, โรงพยาบาลยุวประสาทไวทโยปถัมภ์" />
<meta property="og:locale" content="th_TH" />
<meta property="og:type" content="website" />
<meta property="og:title" content="Yuwa Raffle — เกมจับฉลากสุ่มชื่อ" />
<meta property="og:site_name" content="Yuwa Raffle Game" />

<!-- PWA -->
<link rel="manifest" href="./manifest.webmanifest" />
<meta name="theme-color" content="#2B1166" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="Yuwa Raffle" />
<meta name="application-name" content="Yuwa Raffle" />
<link rel="icon" type="image/svg+xml" href="./assets/medias/icons/icon.svg" />
<link rel="icon" type="image/png" sizes="32x32" href="./assets/medias/icons/icon-32.png" />
<link rel="apple-touch-icon" sizes="180x180" href="./assets/medias/icons/apple-touch-icon.png" />

<!-- Fonts: Fredoka (Latin/digits) + Mitr (Thai) for display, LINE Seed Sans TH (local) for body -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Mitr:wght@400;500;600&display=swap" />
<link rel="preload" href="./assets/fonts/LINESeedSansTH/WOFF2/LINESeedSansTH_W_Rg.woff2" as="font" type="font/woff2" crossorigin />

<link href="./assets/css/fonts.css" rel="stylesheet" type="text/css" />
<link href="./assets/css/fa_pro_67.css" rel="stylesheet" type="text/css" />
<link href="./css/cartoon-game.css?v=<?= $__assetVer ?>" rel="stylesheet" type="text/css" />
