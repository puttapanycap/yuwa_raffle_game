<?php
/**
 * Raffle Game Pro - VIP Button Page
 * Full screen button for VIP/executives to press
 */
define("_WEBROOT_PATH_", "./");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

if (!isset($_GET['share'])) {
    die("Missing raffle key. Please access via shared link.");
}

$raffle_key = $_GET['share'];
$stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
$stmt->execute(['raffle_key' => $raffle_key]);
if ($stmt->rowCount() === 0) {
    die("Invalid raffle key.");
}
$raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <link href="./css/raffle-theme.css" rel="stylesheet" type="text/css" />
    <title>🔘 VIP Button | <?= htmlspecialchars($raffleInfo['event_title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <style>
      /* Minimal critical CSS (fullscreen button) */
      .raffle-app[data-theme="aurora"] { background: #050816; }
      html, body { height: 100%; height: 100dvh; overflow: hidden; }
      .vip-container { animation: zoomIn 0.4s var(--ease-out-soft); }
      @keyframes zoomIn { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="raffle-app" data-theme="aurora">
    <div class="raffle-bg-animated"></div>

    <!-- Connection Status -->
    <div class="connection-status">
        <span class="status-dot online" id="statusDot"></span>
        <span id="statusText">เชื่อมต่อแล้ว</span>
    </div>

    <div class="vip-container">
        <h1 class="event-title"><?= htmlspecialchars($raffleInfo['event_title']) ?></h1>

        <!-- Prize Selection -->
        <div class="prize-section" id="prizeSection" style="display: none;">
            <select class="prize-select" id="prizeSelect">
                <option value="">🎯 ไม่ระบุประเภท</option>
            </select>
        </div>

        <!-- Spin Button -->
        <div class="spin-container" id="spinContainer">
            <button class="btn-spin-vip" id="spinBtn" onclick="triggerSpin()">
                <i class="fa-solid fa-dice fa-bounce"></i>
                <div class="mt-3">สุ่ม</div>
            </button>
            <p class="status-text" id="statusMessage">แตะปุ่มเพื่อสุ่มรายชื่อ</p>
        </div>

        <!-- Winner Display -->
        <div class="winner-reveal" id="winnerReveal">
            <div class="winner-label">🎉 ผู้โชคดี</div>
            <div class="winner-name-display" id="winnerName"></div>
            <button class="btn btn-raffle btn-raffle-lg mt-4" onclick="resetForNext()">
                <i class="fa-solid fa-rotate-right me-2"></i>
                สุ่มรายการต่อไป
            </button>
        </div>
    </div>

    <!-- Confetti Canvas -->
    <canvas id="confetti-canvas" aria-hidden="true"></canvas>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/confetti.js" defer></script>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-vip.js" defer></script>

    <script>
      // Bootstrap data for deferred VIP module
      window.VIP_CONFIG = {
        key: <?= json_encode($raffle_key) ?>
      };
    </script>
</body>
</html>
