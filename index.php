<?php
/**
 * Yuwa Raffle — Main Display (stage)
 * Cartoon Game UI • PWA • real-time sync via AuroraPoller
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

$eventTitle = $raffleInfo['event_title'] ?? 'Raffle Game';
$keyAttr = htmlspecialchars($raffle_key, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <title><?= htmlspecialchars($eventTitle) ?> | Yuwa Raffle</title>
</head>
<body class="raffle-app" data-theme="dark" data-game-state="idle"
      data-raffle-key="<?= $keyAttr ?>" data-raffle-title="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?>">
    <?php require _WEBROOT_PATH_ . 'components/game-bg.php' ?>
    <a class="skip-link" href="#stage">ข้ามไปที่เวทีสุ่ม</a>

    <!-- HUD -->
    <header class="hud">
        <div class="hud-brand">
            <img src="./assets/medias/logos/favicon-32x32.png" alt="โลโก้โรงพยาบาลยุวประสาทไวทโยปถัมภ์" class="hud-logo" width="52" height="52">
            <h1 class="hud-title" id="eventTitle" title="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?>"><?= htmlspecialchars($eventTitle) ?></h1>
        </div>
        <div class="hud-actions">
            <span class="hud-chip" title="รายชื่อที่ยังไม่ถูกสุ่ม">
                <span class="hud-chip-icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                <span id="remainingCount"><?= count($entries) ?></span>
                <span class="hud-chip-label">รายชื่อ</span>
            </span>
            <button type="button" class="btn-raffle-gold btn-icon" data-pwa-install hidden aria-label="ติดตั้งแอป" title="ติดตั้งแอป">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
            </button>
            <a href="./settings.php?share=<?= $keyAttr ?>" class="btn-raffle-ghost btn-icon" aria-label="ตั้งค่า" title="ตั้งค่า">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </a>
            <button type="button" class="btn-raffle-ghost btn-icon" onclick="toggleFullscreen()" aria-label="เต็มจอ" title="เต็มจอ">
                <i class="fa-solid fa-expand" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main class="main-layout">
        <!-- Players (desktop) -->
        <aside class="side-panel" data-side="left" aria-labelledby="playersTitle">
            <section class="game-panel entries-card">
                <header class="panel-head is-sky">
                    <h2 id="playersTitle"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>ผู้เล่น</h2>
                    <span class="count-badge" id="entryCount"><?= count($entries) ?></span>
                </header>
                <div class="panel-body" id="entriesList">
                    <?php foreach ($entries as $entry): ?>
                        <span class="entry-badge" data-id="<?= (int)$entry['id'] ?>"><?= htmlspecialchars($entry['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>

        <!-- Stage -->
        <section class="center-panel" id="stage" aria-label="เวทีสุ่มรางวัล">
            <div class="stage">
                <div class="stage-lights" aria-hidden="true"></div>

                <div class="prize-banner" id="prizeBanner" style="display: none;">
                    <i class="fa-solid fa-trophy me-2" aria-hidden="true"></i><span id="currentPrizeName">รางวัล</span>
                </div>

                <div class="stage-screen">
                    <div class="screen-idle" aria-hidden="true">
                        <i class="fa-solid fa-gift"></i>
                        <span>พร้อมลุ้นหรือยัง?</span>
                    </div>

                    <div class="text-roll-container" id="textRollContainer">
                        <div class="text-roll-item" id="textRollDisplay"></div>
                    </div>

                    <div class="wheel-template-wrapper" id="wheelContainer" style="display: none;">
                        <div class="wheel-template-container">
                            <div class="wheel-template-pointer" aria-hidden="true"></div>
                            <canvas id="wheel" class="wheel-template-wheel" width="600" height="600" aria-label="วงล้อสุ่มรายชื่อ" role="img"></canvas>
                            <div class="wheel-center-btn-container">
                                <div class="wheel-center-btn-ring" aria-hidden="true"></div>
                                <button type="button" class="wheel-center-btn" id="wheelSpinBtn" onclick="startSpin()">START</button>
                            </div>
                        </div>
                    </div>

                    <div class="result-display" id="resultDisplay" style="display: none;" aria-live="polite"></div>
                </div>

                <div class="btn-container" id="btnContainer">
                    <button type="button" class="btn-spin-mega" id="spinBtn" onclick="startSpin()" aria-label="สุ่มรางวัล">
                        <i class="fa-solid fa-dice" aria-hidden="true"></i>
                        <div class="mt-2">สุ่ม</div>
                    </button>
                </div>
            </div>
        </section>

        <!-- Winners (desktop) -->
        <aside class="side-panel" data-side="right" aria-labelledby="winnersTitle">
            <section class="game-panel entries-card">
                <header class="panel-head is-sun">
                    <h2 id="winnersTitle"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>ผู้โชคดี</h2>
                    <span class="count-badge" id="winnerCount"><?= count($logs) ?></span>
                </header>
                <div class="panel-body" id="winnersList">
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

    <!-- Mobile / tablet: bottom-sheet triggers -->
    <button type="button" class="drawer-trigger" data-target="entries-drawer" aria-label="เปิดรายชื่อผู้เล่น">
        <i class="fa-solid fa-users" aria-hidden="true"></i>
        <span class="count-badge" id="drawerEntryCount"><?= count($entries) ?></span>
    </button>
    <button type="button" class="drawer-trigger" data-target="winners-drawer" aria-label="เปิดรายชื่อผู้โชคดี">
        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
        <span class="count-badge" id="drawerWinnerCount"><?= count($logs) ?></span>
    </button>

    <aside class="aurora-drawer" id="entries-drawer" role="dialog" aria-modal="true" aria-labelledby="entries-drawer-title" aria-hidden="true">
        <div class="aurora-drawer-handle" aria-hidden="true"></div>
        <div class="aurora-drawer-header">
            <h3 id="entries-drawer-title"><i class="fa-solid fa-users text-info" aria-hidden="true"></i>ผู้เล่น</h3>
            <button type="button" class="aurora-drawer-close" data-action="close-drawer" aria-label="ปิด">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="aurora-drawer-body" id="entriesListMobile"></div>
    </aside>

    <aside class="aurora-drawer" id="winners-drawer" role="dialog" aria-modal="true" aria-labelledby="winners-drawer-title" aria-hidden="true">
        <div class="aurora-drawer-handle" aria-hidden="true"></div>
        <div class="aurora-drawer-header">
            <h3 id="winners-drawer-title"><i class="fa-solid fa-trophy text-warning" aria-hidden="true"></i>ผู้โชคดี</h3>
            <button type="button" class="aurora-drawer-close" data-action="close-drawer" aria-label="ปิด">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="aurora-drawer-body" id="winnersListMobile"></div>
    </aside>
    <div class="aurora-drawer-backdrop" data-action="close-drawer"></div>

    <canvas id="confetti-canvas" aria-hidden="true"></canvas>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>

    <script src="./js/confetti.js" defer></script>
    <script src="./js/aurora-drawer.js" defer></script>
    <script src="./js/aurora-poller.js" defer></script>
    <script src="./js/raffle-display.js" defer></script>

    <script>
      window.RAFFLE_CONFIG = {
        key: <?= json_encode($raffle_key) ?>,
        eventTitle: <?= json_encode($eventTitle) ?>,
        initialEntries: <?= json_encode($entries) ?>,
        initialLogsCount: <?= count($logs) ?>,
        initialEntriesCount: <?= count($entries) ?>
      };
    </script>
</body>
</html>
