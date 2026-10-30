<?php
/**
 * Yuwa Raffle — Remote Control (backstage controller)
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
    <title>รีโมตควบคุม | <?= htmlspecialchars($eventTitle) ?></title>
</head>
<body class="raffle-app" data-theme="dark"
      data-raffle-key="<?= $keyAttr ?>" data-raffle-title="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?>">
    <?php require _WEBROOT_PATH_ . 'components/game-bg.php' ?>
    <a class="skip-link" href="#controls">ข้ามไปที่ปุ่มควบคุม</a>

    <header class="hud">
        <div class="hud-brand">
            <h1 class="hud-title"><i class="fa-solid fa-gamepad-modern" aria-hidden="true"></i>รีโมตควบคุม</h1>
        </div>
        <div class="hud-actions">
            <button type="button" class="btn-raffle-gold btn-icon" data-pwa-install hidden aria-label="ติดตั้งแอป" title="ติดตั้งแอป">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
            </button>
            <a href="./index.php?share=<?= $keyAttr ?>" class="btn-raffle-ghost btn-icon" aria-label="เปิดจอหลัก" title="จอหลัก">
                <i class="fa-solid fa-tv" aria-hidden="true"></i>
            </a>
            <a href="./settings.php?share=<?= $keyAttr ?>" class="btn-raffle-ghost btn-icon" aria-label="ตั้งค่า" title="ตั้งค่า">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    <main class="page-wrap">
        <!-- Status -->
        <section class="status-grid" aria-label="สถานะเกม">
            <div class="status-card">
                <span class="status-label">สถานะ</span>
                <span class="status-value" id="currentStatus" aria-live="polite">
                    <span class="status-dot online me-2"></span>พร้อม
                </span>
            </div>
            <div class="status-card">
                <span class="status-label">รายชื่อคงเหลือ</span>
                <span class="status-value" id="remainingCount">0</span>
            </div>
            <div class="status-card">
                <span class="status-label">ผู้โชคดีล่าสุด</span>
                <span class="status-value winner" id="lastWinner">-</span>
            </div>
        </section>

        <!-- Prize selection -->
        <section class="game-panel control-section" id="prizeSection" style="display: none;" aria-labelledby="prizeTitle">
            <header class="panel-head is-bubble">
                <h2 id="prizeTitle"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>เลือกประเภทรางวัล</h2>
            </header>
            <div class="panel-body">
                <label class="sr-only" for="prizeSelect">ประเภทรางวัล</label>
                <select class="prize-select" id="prizeSelect">
                    <option value="">ไม่ระบุประเภท</option>
                </select>
            </div>
        </section>

        <!-- Main controls -->
        <section class="game-panel control-section" id="controls" aria-labelledby="controlsTitle">
            <header class="panel-head">
                <h2 id="controlsTitle"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-joystick"></i></span>ควบคุมเกม</h2>
            </header>
            <div class="panel-body">
                <div class="main-controls">
                    <button type="button" class="control-btn spin-btn" id="spinBtn" onclick="triggerSpin()">
                        <i class="fa-solid fa-dice" aria-hidden="true"></i>
                        <span>สุ่ม</span>
                    </button>

                    <button type="button" class="control-btn success" id="confirmBtn" onclick="confirmWinner()" disabled>
                        <i class="fa-solid fa-check text-success" aria-hidden="true"></i>
                        <span>ยืนยัน</span>
                    </button>

                    <button type="button" class="control-btn" id="rerollBtn" onclick="reroll()" disabled>
                        <i class="fa-solid fa-rotate-right text-warning" aria-hidden="true"></i>
                        <span>สุ่มใหม่</span>
                    </button>

                    <button type="button" class="control-btn" onclick="resetState()">
                        <i class="fa-solid fa-arrows-rotate text-info" aria-hidden="true"></i>
                        <span>รีเซ็ตสถานะ</span>
                    </button>

                    <button type="button" class="control-btn" onclick="refreshMainPage()">
                        <i class="fa-solid fa-display text-primary" aria-hidden="true"></i>
                        <span>รีเฟรชจอหลัก</span>
                    </button>

                    <button type="button" class="control-btn danger" id="undoBtn" onclick="undoWinner()" style="grid-column: 1 / -1; min-height: 76px; flex-direction: row;">
                        <i class="fa-solid fa-rotate-left text-danger" aria-hidden="true"></i>
                        <span>ยกเลิกผู้โชคดีล่าสุด (Undo)</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Lists -->
        <section class="game-panel control-section" aria-label="รายชื่อและผู้โชคดี">
            <div class="panel-body">
                <div class="tab-buttons" role="tablist">
                    <button type="button" class="tab-btn active" role="tab" aria-selected="true" onclick="showTab('entries')">
                        <i class="fa-solid fa-users me-1" aria-hidden="true"></i>รายชื่อ
                    </button>
                    <button type="button" class="tab-btn" role="tab" aria-selected="false" onclick="showTab('winners')">
                        <i class="fa-solid fa-trophy me-1" aria-hidden="true"></i>ผู้โชคดี
                    </button>
                </div>

                <div class="tab-content active" id="entriesTab" role="tabpanel">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <label class="sr-only" for="entrySearch">ค้นหารายชื่อ</label>
                        <input type="search" class="search-input" id="entrySearch" placeholder="ค้นหารายชื่อ..." oninput="filterEntries()" autocomplete="off">
                    </div>
                    <div class="entries-list" id="entriesList">
                        <div class="text-center text-muted py-4">กำลังโหลด...</div>
                    </div>
                </div>

                <div class="tab-content" id="winnersTab" role="tabpanel">
                    <div class="filter-row">
                        <div class="search-wrapper">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <label class="sr-only" for="winnerSearch">ค้นหาผู้โชคดี</label>
                            <input type="search" class="search-input" id="winnerSearch" placeholder="ค้นหารายชื่อ..." oninput="filterWinners()" autocomplete="off">
                        </div>
                        <label class="sr-only" for="prizeFilter">กรองตามรางวัล</label>
                        <select class="filter-select" id="prizeFilter" onchange="filterWinners()">
                            <option value="">ทุกรางวัล</option>
                        </select>
                    </div>
                    <div class="entries-list" id="winnersList">
                        <div class="text-center text-muted py-4">ยังไม่มีผู้โชคดี</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-remote.js" defer></script>

    <script>
      window.REMOTE_CONFIG = { key: <?= json_encode($raffle_key) ?> };
    </script>
</body>
</html>
