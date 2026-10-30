<?php
/**
 * Yuwa Raffle — VIP Button (giant arcade button for executives)
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
$eventTitle = $raffleInfo['event_title'] ?? 'Raffle Game';
$keyAttr = htmlspecialchars($raffle_key, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <title>ปุ่ม VIP | <?= htmlspecialchars($eventTitle) ?></title>
</head>
<body class="raffle-app vip-page" data-theme="dark"
      data-raffle-key="<?= $keyAttr ?>" data-raffle-title="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?>">
    <?php require _WEBROOT_PATH_ . 'components/game-bg.php' ?>

    <div class="connection-status" role="status" aria-live="polite">
        <span class="status-dot online" id="statusDot"></span>
        <span id="statusText">เชื่อมต่อแล้ว</span>
    </div>

    <main class="vip-container">
        <h1 class="hud-title event-title"><?= htmlspecialchars($eventTitle) ?></h1>

        <div class="prize-section" id="prizeSection" style="display: none;">
            <label class="sr-only" for="prizeSelect">ประเภทรางวัล</label>
            <select class="prize-select" id="prizeSelect">
                <option value="">ไม่ระบุประเภทรางวัล</option>
            </select>
        </div>

        <div class="spin-container" id="spinContainer">
            <button type="button" class="btn-spin-vip" id="spinBtn" onclick="triggerSpin()" aria-describedby="statusMessage">
                <i class="fa-solid fa-dice fa-bounce" aria-hidden="true"></i>
                <div class="mt-3">สุ่ม</div>
            </button>
            <p class="status-text" id="statusMessage" aria-live="polite">แตะปุ่มเพื่อสุ่มรายชื่อ</p>
        </div>

        <div class="winner-reveal" id="winnerReveal" aria-live="assertive">
            <div class="winner-label"><i class="fa-solid fa-party-horn me-2" aria-hidden="true"></i>ผู้โชคดี</div>
            <div class="winner-name-display" id="winnerName"></div>
            <button type="button" class="btn-raffle-gold btn-raffle-lg mt-4" onclick="resetForNext()">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                สุ่มรายการต่อไป
            </button>
        </div>
    </main>

    <canvas id="confetti-canvas" aria-hidden="true"></canvas>

    <div class="net-badge" role="status" aria-live="polite">
        <i class="fa-solid fa-wifi-slash" aria-hidden="true"></i> ออฟไลน์
    </div>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/confetti.js" defer></script>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-vip.js" defer></script>

    <script>
      window.VIP_CONFIG = { key: <?= json_encode($raffle_key) ?> };
    </script>
</body>
</html>
