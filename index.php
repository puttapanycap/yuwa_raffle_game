<?php
/**
 * Raffle Game Pro - Main Display
 * Premium raffle display with real-time sync
 * Aurora Glass Aesthetic • Responsive • Performance Optimized
 */
define("_WEBROOT_PATH_", "./");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    // Generate or get raffle key
    if (!isset($_GET['share'])) {
        $raffle_key = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO raffle_keys (raffle_key) VALUES (:raffle_key)");
        $stmt->execute(['raffle_key' => $raffle_key]);

        // Initialize state
        $stmt = $pdo->prepare("INSERT INTO raffle_state (raffle_key, current_action) VALUES (:raffle_key, 'idle')");
        $stmt->execute(['raffle_key' => $raffle_key]);

        header("Location: ./?share=$raffle_key");
        exit;
    } else {
        $raffle_key = $_GET['share'];
        $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);
        if ($stmt->rowCount() === 0) {
            die("Invalid raffle key.");
        }
        $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch entries
    $stmt = $pdo->prepare("SELECT id, name FROM raffle_entries WHERE raffle_key = :raffle_key AND is_winner = 0");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch logs
    $stmt = $pdo->prepare("
        SELECT l.*, p.category_name, p.category_color
        FROM raffle_logs l
        LEFT JOIN prize_categories p ON l.prize_category_id = p.id
        WHERE l.raffle_key = :raffle_key AND l.is_undone = 0
        ORDER BY l.created_at DESC
    ");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <link href="./css/raffle-theme.css" rel="stylesheet" type="text/css" />
    <title>🎰 <?= htmlspecialchars($raffleInfo['event_title']) ?> | Raffle Game Pro</title>
    <!-- Critical inline CSS (above-the-fold) -->
    <style>
      .raffle-app[data-theme="aurora"] { background: #050816; }
      .main-layout { animation: fadeIn 0.4s var(--ease-out-soft); }
      @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="raffle-app" data-theme="aurora">
    <div class="raffle-bg-animated"></div>

    <!-- Header -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <img src="./assets/medias/logos/favicon-32x32.png" alt="Logo" class="rounded-circle" style="width: 40px; height: 40px;">
            <h1 class="page-header-title" id="eventTitle"><?= htmlspecialchars($raffleInfo['event_title']) ?></h1>
        </div>
        <div class="page-header-actions">
            <span class="badge p-2 rounded-pill">
                <i class="fa-solid fa-users me-1"></i>
                <span id="remainingCount"><?= count($entries) ?></span> รายชื่อ
            </span>
            <a href="./settings.php?share=<?= $raffle_key ?>" class="btn btn-raffle-ghost" aria-label="ตั้งค่า">
                <i class="fa-solid fa-gear"></i>
            </a>
            <button class="btn btn-raffle-ghost" onclick="toggleFullscreen()" aria-label="เต็มจอ">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </div>

    <main class="main-layout">
        <!-- Left Panel - Entries (desktop) -->
        <aside class="side-panel" data-side="left" aria-label="รายชื่อผู้เข้าร่วม">
            <section class="glass-card-static entries-card">
                <div class="card-header-custom">
                    <h3><i class="fa-solid fa-users me-2"></i>รายชื่อผู้เข้าร่วม</h3>
                    <span class="count-badge" id="entryCount"><?= count($entries) ?></span>
                </div>
                <div class="card-body" id="entriesList">
                    <?php foreach ($entries as $entry): ?>
                        <span class="entry-badge" data-id="<?= (int)$entry['id'] ?>"><?= htmlspecialchars($entry['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>

        <!-- Center Panel - Main Display -->
        <section class="center-panel">
            <div class="glass-card-static spin-area">
                <!-- Prize Banner -->
                <div class="prize-banner" id="prizeBanner" style="display: none;">
                    <i class="fa-solid fa-trophy me-2"></i>
                    <span id="currentPrizeName">รางวัล</span>
                </div>

                <!-- Text Roll Animation -->
                <div class="text-roll-container" id="textRollContainer">
                    <div class="text-roll-item" id="textRollDisplay"></div>
                </div>

                <!-- Wheel Animation -->
                <div class="wheel-template-wrapper" id="wheelContainer" style="display: none;">
                    <div class="wheel-template-container">
                        <div class="wheel-template-pointer"></div>
                        <canvas id="wheel" class="wheel-template-wheel" width="400" height="400"></canvas>
                        <div class="wheel-center-btn-container">
                            <div class="wheel-center-btn-ring"></div>
                            <button class="wheel-center-btn" id="wheelSpinBtn" onclick="startSpin()">START</button>
                        </div>
                    </div>
                </div>

                <!-- Result Display -->
                <div class="result-display" id="resultDisplay" style="display: none;" aria-live="polite"></div>

                <!-- Spin Button -->
                <div class="btn-container" id="btnContainer">
                    <button class="btn-spin-mega" id="spinBtn" onclick="startSpin()" aria-label="สุ่มรางวัล">
                        <i class="fa-solid fa-dice"></i>
                        <div class="mt-2">สุ่ม</div>
                    </button>
                </div>
            </div>
        </section>

        <!-- Right Panel - Winners (desktop) -->
        <aside class="side-panel" data-side="right" aria-label="ผู้โชคดี">
            <section class="glass-card-static entries-card">
                <div class="card-header-custom">
                    <h3><i class="fa-solid fa-trophy me-2 text-warning"></i>ผู้โชคดี</h3>
                    <span class="count-badge" id="winnerCount"><?= count($logs) ?></span>
                </div>
                <div class="card-body" id="winnersList">
                    <?php if (count($logs) === 0): ?>
                        <div class="text-center text-muted py-4">ยังไม่มีผู้โชคดี</div>
                    <?php else: ?>
                        <?php $total = count($logs); ?>
                        <?php foreach ($logs as $i => $log): ?>
                            <?php $ts = date('H:i d/m/y', strtotime($log['created_at'])); ?>
                            <div class="winner-log">
                                <div class="number"><?= $total - $i ?></div>
                                <div class="name">
                                    <?= htmlspecialchars($log['log_message']) ?>
                                    <div class="text-muted" style="font-size: 0.75rem; font-weight: 400;"><?= $ts ?></div>
                                </div>
                                <?php if ($log['category_name']): ?>
                                    <span class="prize-tag" style="background: <?= htmlspecialchars($log['category_color']) ?>20; color: <?= htmlspecialchars($log['category_color']) ?>"><?= htmlspecialchars($log['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </main>

    <!-- Mobile Drawer Triggers (FABs) -->
    <button class="drawer-trigger" data-target="entries-drawer" aria-label="เปิดรายชื่อ">
        <i class="fa-solid fa-users"></i>
        <span class="count-badge" id="drawerEntryCount"><?= count($entries) ?></span>
    </button>
    <button class="drawer-trigger" data-target="winners-drawer" aria-label="เปิดผู้โชคดี" style="bottom: calc(env(safe-area-inset-bottom, 0) + 5rem);">
        <i class="fa-solid fa-trophy"></i>
        <span class="count-badge" id="drawerWinnerCount"><?= count($logs) ?></span>
    </button>

    <!-- Mobile Drawer: Entries -->
    <aside class="aurora-drawer" id="entries-drawer" role="dialog" aria-modal="true" aria-labelledby="entries-drawer-title" aria-hidden="true">
        <div class="aurora-drawer-handle"></div>
        <div class="aurora-drawer-header">
            <h3 id="entries-drawer-title"><i class="fa-solid fa-users me-2"></i>รายชื่อผู้เข้าร่วม</h3>
            <button class="aurora-drawer-close" data-action="close-drawer" aria-label="ปิด">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="aurora-drawer-body" id="entriesListMobile"></div>
    </aside>

    <!-- Mobile Drawer: Winners -->
    <aside class="aurora-drawer" id="winners-drawer" role="dialog" aria-modal="true" aria-labelledby="winners-drawer-title" aria-hidden="true">
        <div class="aurora-drawer-handle"></div>
        <div class="aurora-drawer-header">
            <h3 id="winners-drawer-title"><i class="fa-solid fa-trophy me-2 text-warning"></i>ผู้โชคดี</h3>
            <button class="aurora-drawer-close" data-action="close-drawer" aria-label="ปิด">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="aurora-drawer-body" id="winnersListMobile"></div>
    </aside>
    <div class="aurora-drawer-backdrop" data-action="close-drawer"></div>

    <!-- Confetti Canvas -->
    <canvas id="confetti-canvas" aria-hidden="true"></canvas>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>

    <!-- App JS (deferred) -->
    <script src="./js/confetti.js" defer></script>
    <script src="./js/aurora-drawer.js" defer></script>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-display.js" defer></script>

    <script>
      // Bootstrap data for deferred modules
      window.RAFFLE_CONFIG = {
        key: <?= json_encode($raffle_key) ?>,
        eventTitle: <?= json_encode($raffleInfo['event_title']) ?>,
        initialEntries: <?= json_encode($entries) ?>,
        initialLogsCount: <?= count($logs) ?>,
        initialEntriesCount: <?= count($entries) ?>
      };
    </script>
</body>
</html>
