<?php
/**
 * Raffle Game Pro - Remote Control Page
 * Control panel for backstage management
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
    <title>🎮 Remote Control | <?= htmlspecialchars($raffleInfo['event_title']) ?></title>
    <!-- Critical inline CSS (above-the-fold) -->
    <style>
      .raffle-app[data-theme="aurora"] { background: #050816; }
      .remote-container { animation: fadeIn 0.3s var(--ease-out-soft); }
      @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="raffle-app" data-theme="aurora">
    <div class="raffle-bg-animated"></div>

    <!-- Header -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <h1 class="page-header-title">
                <i class="fa-solid fa-gamepad me-2"></i>Remote Control
            </h1>
        </div>
        <div class="page-header-actions">
            <a href="./settings.php?share=<?= $raffle_key ?>" class="btn btn-raffle-ghost">
                <i class="fa-solid fa-gear"></i>
            </a>
        </div>
    </div>

    <div class="remote-container">
        <!-- Status Section -->
        <div class="control-section">
            <div class="status-card">
                <span class="status-label">สถานะ</span>
                <span class="status-value" id="currentStatus">
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
        </div>

        <!-- Prize Selection -->
        <div class="control-section" id="prizeSection" style="display: none;">
            <div class="control-section-title">🏆 เลือกประเภทรางวัล</div>
            <select class="prize-select" id="prizeSelect">
                <option value="">ไม่ระบุประเภท</option>
            </select>
        </div>

        <!-- Main Controls -->
        <div class="control-section">
            <div class="control-section-title">🎮 ควบคุม</div>
            <div class="main-controls">
                <button class="control-btn spin-btn" id="spinBtn" onclick="triggerSpin()">
                    <i class="fa-solid fa-dice"></i>
                    <span>สุ่ม</span>
                </button>

                <button class="control-btn success" id="confirmBtn" onclick="confirmWinner()" disabled>
                    <i class="fa-solid fa-check text-success"></i>
                    <span>ยืนยัน</span>
                </button>

                <button class="control-btn" id="rerollBtn" onclick="reroll()" disabled>
                    <i class="fa-solid fa-rotate-right text-warning"></i>
                    <span>สุ่มใหม่</span>
                </button>

                <button class="control-btn danger" id="undoBtn" onclick="undoWinner()">
                    <i class="fa-solid fa-undo text-danger"></i>
                    <span>Undo ล่าสุด</span>
                </button>

                <button class="control-btn" onclick="resetState()">
                    <i class="fa-solid fa-arrows-rotate text-info"></i>
                    <span>Reset</span>
                </button>

                <button class="control-btn" onclick="refreshMainPage()">
                    <i class="fa-solid fa-display text-primary"></i>
                    <span>Refresh หน้าหลัก</span>
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="control-section">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab('entries')">
                    <i class="fa-solid fa-users me-1"></i>รายชื่อ
                </button>
                <button class="tab-btn" onclick="showTab('winners')">
                    <i class="fa-solid fa-trophy me-1"></i>ผู้โชคดี
                </button>
            </div>

            <div class="tab-content active" id="entriesTab">
                <div class="search-wrapper" style="margin-bottom: 0.75rem;">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" class="search-input" id="entrySearch" placeholder="ค้นหารายชื่อ..." oninput="filterEntries()" style="margin-bottom: 0;">
                </div>
                <div class="entries-list" id="entriesList">
                    <div class="text-center text-muted py-4">กำลังโหลด...</div>
                </div>
            </div>

            <div class="tab-content" id="winnersTab">
                <div class="filter-row">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" class="search-input" id="winnerSearch" placeholder="ค้นหารายชื่อ..." oninput="filterWinners()">
                    </div>
                    <select class="filter-select" id="prizeFilter" onchange="filterWinners()">
                        <option value="">ทุกรางวัล</option>
                    </select>
                </div>
                <div class="entries-list" id="winnersList">
                    <div class="text-center text-muted py-4">ยังไม่มีผู้โชคดี</div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-remote.js" defer></script>

    <script>
      // Bootstrap data for deferred remote module
      window.REMOTE_CONFIG = {
        key: <?= json_encode($raffle_key) ?>
      };
    </script>
</body>
</html>
