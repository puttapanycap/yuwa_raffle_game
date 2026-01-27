<?php
/**
 * Raffle Game Pro - Main Display
 * Premium raffle display with real-time sync
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
    <style>
        .main-layout {
            display: grid;
            grid-template-columns: 320px 1fr 320px;
            gap: 1.5rem;
            padding: 1.5rem;
            height: calc(100vh - 80px);
            max-height: calc(100vh - 80px);
            overflow: hidden;
        }
        @media (max-width: 1200px) {
            .main-layout {
                grid-template-columns: 1fr;
                height: auto;
                max-height: none;
                overflow: visible;
            }
            .side-panel {
                display: none;
            }
        }
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 100%;
            overflow: hidden;
        }
        .center-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 500px;
            overflow: hidden;
        }
        .spin-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            padding: 3rem;
            width: 100%;
        }
        .result-display {
            font-size: var(--result-font-size, 72px);
            font-weight: 800;
            color: var(--result-font-color, var(--text-primary));
            text-align: center;
            min-height: 1.5em;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .result-display.winner {
            color: var(--result-font-color, var(--raffle-gold));
            text-shadow: 0 0 40px rgba(251, 191, 36, 0.5);
            animation: winner-glow 2s ease-in-out infinite;
        }
        @keyframes winner-glow {
            0%, 100% { text-shadow: 0 0 40px rgba(251, 191, 36, 0.5); }
            50% { text-shadow: 0 0 80px rgba(251, 191, 36, 0.8); }
        }
        .prize-banner {
            font-size: 1.5rem;
            color: var(--raffle-gold);
            padding: 1rem 2rem;
            background: var(--card-bg);
            border: 2px solid var(--raffle-gold);
            border-radius: 20px;
            margin-bottom: 1rem;
        }
        .entries-card {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .entries-card .card-body {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            min-height: 0;
        }
        .entry-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--card-bg);
            border-radius: 10px;
            margin: 0.25rem;
            font-size: var(--entry-font-size, 0.9rem);
            transition: all 0.2s;
        }
        .entry-badge.highlight {
            background: var(--raffle-gold);
            color: #000;
            transform: scale(1.1);
        }
        .winner-log {
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .winner-log .number {
            width: 32px;
            height: 32px;
            background: var(--raffle-gold);
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .winner-log .name {
            flex: 1;
            font-weight: 600;
            font-size: var(--winner-font-size, 1rem);
        }
        .winner-log .prize-tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }
        .card-header-custom {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header-custom h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .count-badge {
            background: var(--raffle-primary);
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        /* Wheel container */
        .wheel-container {
            position: relative;
            width: 400px;
            height: 400px;
            display: none;
        }
        .wheel-container.active {
            display: block;
        }
        #wheel {
            width: 100%;
            height: 100%;
        }
        .wheel-pointer {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 40px solid var(--raffle-gold);
            z-index: 10;
        }
        /* Text roll container */
        .text-roll-container {
            width: 100%;
            max-width: 800px;
            height: 150px;
            overflow: hidden;
            position: relative;
        }
        .text-roll-container.hide {
            display: none;
        }
        .text-roll-item {
            position: absolute;
            width: 100%;
            text-align: center;
            font-size: var(--result-font-size, 72px);
            font-weight: 800;
            color: var(--result-font-color, inherit);
            opacity: 0;
        }
        .text-roll-item.active {
            opacity: 1;
            animation: text-roll 0.08s ease-out;
        }
        @keyframes text-roll {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        /* Button container */
        .btn-container {
            margin-top: 2rem;
        }
        .btn-container.hide {
            display: none;
        }
    </style>
</head>
<body class="raffle-app" data-theme="dark">
    <div class="raffle-bg-animated"></div>

    <!-- Header -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <img src="./assets/medias/logos/favicon-32x32.png" alt="Logo" class="rounded-circle" style="width: 40px; height: 40px;">
            <h1 class="page-header-title" id="eventTitle"><?= htmlspecialchars($raffleInfo['event_title']) ?></h1>
        </div>
        <div class="page-header-actions">
            <span class="badge bg-dark text-white p-2 rounded-pill">
                <i class="fa-solid fa-users me-1"></i>
                <span id="remainingCount"><?= count($entries) ?></span> รายชื่อ
            </span>
            <a href="./settings.php?share=<?= $raffle_key ?>" class="btn btn-raffle-ghost">
                <i class="fa-solid fa-gear"></i>
            </a>
            <button class="btn btn-raffle-ghost" onclick="toggleFullscreen()">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </div>

    <div class="main-layout">
        <!-- Left Panel - Entries -->
        <div class="side-panel">
            <div class="glass-card-static entries-card">
                <div class="card-header-custom">
                    <h3><i class="fa-solid fa-users me-2"></i>รายชื่อผู้เข้าร่วม</h3>
                    <span class="count-badge" id="entryCount"><?= count($entries) ?></span>
                </div>
                <div class="card-body" id="entriesList">
                    <?php foreach ($entries as $entry): ?>
                        <span class="entry-badge" data-id="<?= $entry['id'] ?>"><?= htmlspecialchars($entry['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Center Panel - Main Display -->
        <div class="center-panel">
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

                <!-- Wheel Animation (New Template) -->
                <div class="wheel-template-wrapper" id="wheelContainer" style="display: none;">
                    <div class="wheel-template-container">
                        <!-- Pink pointer at top -->
                        <div class="wheel-template-pointer"></div>
                        
                        <!-- The wheel -->
                        <canvas id="wheel" class="wheel-template-wheel" width="400" height="400"></canvas>
                        
                        <!-- Center button with spinning ring -->
                        <div class="wheel-center-btn-container">
                            <div class="wheel-center-btn-ring"></div>
                            <button class="wheel-center-btn" id="wheelSpinBtn" onclick="startSpin()">
                                START
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Result Display -->
                <div class="result-display" id="resultDisplay" style="display: none;"></div>

                <!-- Spin Button -->
                <div class="btn-container" id="btnContainer">
                    <button class="btn-spin-mega" id="spinBtn" onclick="startSpin()">
                        <i class="fa-solid fa-dice"></i>
                        <div class="mt-2">สุ่ม</div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel - Winners -->
        <div class="side-panel">
            <div class="glass-card-static entries-card">
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
                                    <span class="prize-tag" style="background: <?= $log['category_color'] ?>20; color: <?= $log['category_color'] ?>"><?= htmlspecialchars($log['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Confetti Canvas -->
    <canvas id="confetti-canvas"></canvas>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/confetti.js"></script>

    <script>
        const RAFFLE_KEY = '<?= $raffle_key ?>';
        let settings = {};
        let entries = <?= json_encode($entries) ?>;
        let currentState = 'idle';
        let spinInterval = null;
        let spinAudio = null;
        let winnerAudio = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            startPolling();
        });

        function loadSettings() {
            fetch(`./actions/get_settings.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        settings = data.settings;
                        applySettings();

                        // Load custom sounds
                        if (data.custom_sounds.spin) {
                            spinAudio = new Audio('./' + data.custom_sounds.spin.file_path);
                        }
                        if (data.custom_sounds.winner) {
                            winnerAudio = new Audio('./' + data.custom_sounds.winner.file_path);
                        }
                    }
                });
        }

        function applySettings() {
            // Theme
            document.body.setAttribute('data-theme', settings.theme || 'dark');

            // Font size
            document.documentElement.style.setProperty('--result-font-size', (settings.result_font_size || 72) + 'px');
            document.documentElement.style.setProperty('--result-font-color', settings.result_font_color || 'var(--text-primary)');
            document.documentElement.style.setProperty('--entry-font-size', (settings.entry_font_size || 16) + 'px');
            document.documentElement.style.setProperty('--winner-font-size', (settings.winner_font_size || 16) + 'px');

            // Show/hide button
            if (settings.show_main_button == 0) {
                document.getElementById('btnContainer').classList.add('hide');
            } else {
                document.getElementById('btnContainer').classList.remove('hide');
            }

            // Template
            if (settings.animation_template === 'wheel') {
                document.getElementById('wheelContainer').style.display = 'flex';
                document.getElementById('textRollContainer').classList.add('hide');
                document.getElementById('btnContainer').classList.add('hide');
                initWheel();
            } else {
                document.getElementById('wheelContainer').style.display = 'none';
                document.getElementById('textRollContainer').classList.remove('hide');
            }
        }

        let pollCount = 0;
        let lastWinnersCount = <?= count($logs) ?>;
        let lastEntriesCount = <?= count($entries) ?>;

        function startPolling() {
            setInterval(checkState, 500);
        }

        function checkState() {
            pollCount++;
            
            fetch(`./actions/get_state.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const remainingCount = data.entries.remaining;
                        document.getElementById('remainingCount').textContent = remainingCount;
                        document.getElementById('entryCount').textContent = remainingCount;

                        // Check if entries count changed (indicates undo or new winner)
                        if (remainingCount !== lastEntriesCount) {
                            lastEntriesCount = remainingCount;
                            loadEntries();
                            loadWinners();
                        }

                        // Update prize display
                        if (data.state.prize) {
                            document.getElementById('prizeBanner').style.display = 'block';
                            document.getElementById('currentPrizeName').textContent = data.state.prize.category_name;
                        }

                        const newState = data.state.action;
                        if (newState !== currentState) {
                            handleStateChange(newState, data.state);
                        }
                        currentState = newState;

                        // Periodic refresh every 10 polling cycles (5 seconds)
                        if (pollCount % 10 === 0) {
                            loadWinners();
                        }
                    }
                });
        }

        function handleStateChange(state, stateData) {
            const btn = document.getElementById('spinBtn');
            const wheelBtn = document.getElementById('wheelSpinBtn');
            const textRoll = document.getElementById('textRollContainer');
            const textRollDisplay = document.getElementById('textRollDisplay');
            const resultDisplay = document.getElementById('resultDisplay');
            const isWheelMode = settings.animation_template === 'wheel';

            switch(state) {
                case 'idle':
                    stopSpinAnimation();
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('spinning');
                        btn.innerHTML = '<i class="fa-solid fa-dice"></i><div class="mt-2">สุ่ม</div>';
                    }
                    if (wheelBtn) {
                        wheelBtn.disabled = false;
                        wheelBtn.classList.remove('spinning');
                        wheelBtn.textContent = 'START';
                    }
                    resultDisplay.style.display = 'none';
                    if (!isWheelMode) {
                        textRoll.classList.remove('hide');
                    }
                    textRollDisplay.textContent = '';
                    textRollDisplay.classList.remove('active');
                    break;

                case 'spinning':
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('spinning');
                        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-2">กำลังสุ่ม...</div>';
                    }
                    if (wheelBtn) {
                        wheelBtn.disabled = true;
                        wheelBtn.classList.add('spinning');
                    }
                    resultDisplay.style.display = 'none';
                    if (!isWheelMode) {
                        textRoll.classList.remove('hide');
                    }
                    startSpinAnimation();
                    
                    // Play spin sound
                    if (settings.enable_sound != 0 && spinAudio) {
                        spinAudio.loop = true;
                        spinAudio.play().catch(() => {});
                    }
                    break;

                case 'revealing':
                case 'confirmed':
                    stopSpinAnimation();
                    textRoll.classList.add('hide');
                    resultDisplay.style.display = 'flex';
                    resultDisplay.textContent = stateData.winner;
                    resultDisplay.classList.add('winner');
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('spinning');
                        btn.innerHTML = '<i class="fa-solid fa-rotate-right"></i><div class="mt-2">สุ่มต่อ</div>';
                    }
                    if (wheelBtn) {
                        wheelBtn.disabled = false;
                        wheelBtn.classList.remove('spinning');
                        wheelBtn.textContent = 'START';
                    }

                    // Stop spin sound, play winner sound
                    if (spinAudio) spinAudio.pause();
                    if (settings.enable_sound != 0 && winnerAudio) {
                        winnerAudio.play().catch(() => {});
                    }

                    // Confetti
                    if (settings.enable_confetti != 0 && typeof confetti !== 'undefined') {
                        confetti.start();
                        setTimeout(() => confetti.stop(), 3000);
                    }

                    // Reload entries and winners
                    loadEntries();
                    loadWinners();
                    break;

                case 'refresh':
                    // Refresh the page when commanded from remote
                    console.log('Refresh command received, reloading page...');
                    location.reload();
                    break;
            }
        }

        let spinTimeoutId = null; // Track timeout to clear on errors
        
        function startSpin() {
            if (currentState === 'confirmed' || currentState === 'revealing') {
                // Reset state first
                const formData = new FormData();
                formData.append('raffle_key', RAFFLE_KEY);
                formData.append('action', 'idle');
                fetch('./actions/set_state.php', { method: 'POST', body: formData });
                setTimeout(startSpin, 300);
                return;
            }

            // Clear any existing timeout
            if (spinTimeoutId) {
                clearTimeout(spinTimeoutId);
                spinTimeoutId = null;
            }

            if (entries.length === 0) {
                Swal.fire('ไม่มีรายชื่อ', 'กรุณาเพิ่มรายชื่อก่อนเริ่มสุ่ม', 'warning');
                return;
            }

            const btn = document.getElementById('spinBtn');
            btn.disabled = true;
            btn.classList.add('spinning');

            // Pick random winner
            fetch(`./actions/pick_random.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Update local entries from server response
                        if (data.entries) {
                            entries = data.entries;
                        }
                        
                        // Set spinning state
                        const formData = new FormData();
                        formData.append('raffle_key', RAFFLE_KEY);
                        formData.append('action', 'spinning');
                        formData.append('triggered_by', 'main');
                        fetch('./actions/set_state.php', { method: 'POST', body: formData });

                        // After duration, reveal and confirm
                        const duration = (parseInt(settings.spin_duration) || 5) * 1000;
                        console.log('Spin Duration:', duration, 'ms (from settings:', settings.spin_duration, ')');
                        
                        // Safety: Stop animation after duration even if state polling fails
                        spinTimeoutId = setTimeout(() => {
                            console.log('Spin timeout reached, stopping animation');
                            stopSpinAnimation();
                            spinTimeoutId = null;
                            
                            // Reveal
                            const revealData = new FormData();
                            revealData.append('raffle_key', RAFFLE_KEY);
                            revealData.append('action', 'revealing');
                            revealData.append('winner', data.winner.name);
                            revealData.append('winner_id', data.winner.id);
                            fetch('./actions/set_state.php', { method: 'POST', body: revealData });

                            // Auto-confirm after 300ms (faster response)
                            setTimeout(() => {
                                const confirmData = new FormData();
                                confirmData.append('raffle_key', RAFFLE_KEY);
                                confirmData.append('winner', data.winner.name);
                                confirmData.append('winner_id', data.winner.id);
                                fetch('./actions/confirm_winner.php', { method: 'POST', body: confirmData });
                            }, 300);
                        }, duration);
                    } else {
                        // Error case: stop everything and reset UI
                        stopSpinAnimation();
                        btn.disabled = false;
                        btn.classList.remove('spinning');
                        btn.innerHTML = '<i class="fa-solid fa-dice"></i><div class="mt-2">สุ่ม</div>';
                        
                        // Reset state to idle
                        const resetData = new FormData();
                        resetData.append('raffle_key', RAFFLE_KEY);
                        resetData.append('action', 'idle');
                        fetch('./actions/set_state.php', { method: 'POST', body: resetData });
                        
                        Swal.fire('ไม่มีรายชื่อ', data.error || 'ไม่มีรายชื่อให้สุ่มแล้ว', 'warning');
                    }
                })
                .catch(err => {
                    // Network error: stop everything
                    console.error('Spin error:', err);
                    stopSpinAnimation();
                    btn.disabled = false;
                    btn.classList.remove('spinning');
                    btn.innerHTML = '<i class="fa-solid fa-dice"></i><div class="mt-2">สุ่ม</div>';
                    Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                });
        }

        function startSpinAnimation() {
            if (entries.length === 0) return;

            // Text roll animation
            if (settings.animation_template !== 'wheel') {
                const textRollDisplay = document.getElementById('textRollDisplay');
                let index = 0;

                spinInterval = setInterval(() => {
                    textRollDisplay.classList.remove('active');
                    setTimeout(() => {
                        textRollDisplay.textContent = entries[index].name;
                        textRollDisplay.classList.add('active');
                        index = (index + 1) % entries.length;
                    }, 10);
                }, 80);
            } else {
                // Wheel spin animation
                startWheelSpin();
            }
        }

        function stopSpinAnimation() {
            if (spinInterval) {
                clearInterval(spinInterval);
                spinInterval = null;
            }
            if (spinAudio) {
                spinAudio.pause();
                spinAudio.currentTime = 0;
            }
            stopWheelSpin();
        }

        function loadEntries() {
            fetch(`./actions/get_entries.php?raffle_key=${RAFFLE_KEY}&filter=available`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        entries = data.entries;
                        const list = document.getElementById('entriesList');
                        list.innerHTML = entries.map(e => 
                            `<span class="entry-badge" data-id="${e.id}">${e.name}</span>`
                        ).join('');
                        document.getElementById('entryCount').textContent = entries.length;
                        document.getElementById('remainingCount').textContent = entries.length;
                    }
                });
        }

        function loadWinners() {
            fetch(`./actions/get_winners.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const list = document.getElementById('winnersList');
                        document.getElementById('winnerCount').textContent = data.logs.length;
                        
                        if (data.logs.length === 0) {
                            list.innerHTML = '<div class="text-center text-muted py-4">ยังไม่มีผู้โชคดี</div>';
                        } else {
                            list.innerHTML = data.logs.map((w, i) => {
                                const ts = new Date(w.created_at);
                                const timeStr = ts.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: '2-digit' });
                                return `
                                <div class="winner-log">
                                    <div class="number">${data.total - i}</div>
                                    <div class="name">
                                        ${w.winner_name}
                                        <div class="text-muted" style="font-size: 0.75rem; font-weight: 400;">${timeStr}</div>
                                    </div>
                                    ${w.prize_name ? `<span class="prize-tag" style="background: ${w.prize_color}20; color: ${w.prize_color}">${w.prize_name}</span>` : ''}
                                </div>
                            `}).join('');
                        }
                        list.scrollTop = 0;
                    }
                });
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }

        // Wheel animation variables
        let wheelCanvas = null;
        let wheelCtx = null;
        let wheelRotation = 0;
        let wheelSpinning = false;
        let wheelAnimationId = null;
        const WHEEL_COLORS = [
            '#6366f1', // Blue/Purple
            '#d4a574', // Tan/Gold  
            '#22c55e', // Green
            '#a855f7', // Purple
            '#67e8f9', // Cyan
            '#f472b6'  // Pink
        ];

        // Initialize wheel canvas
        function initWheel() {
            wheelCanvas = document.getElementById('wheel');
            if (!wheelCanvas) return;
            
            wheelCtx = wheelCanvas.getContext('2d');
            drawWheel();
        }

        function drawWheel() {
            if (!wheelCtx || entries.length === 0) return;
            
            const canvas = wheelCanvas;
            const ctx = wheelCtx;
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = Math.min(centerX, centerY) - 10;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw segments
            const numSegments = Math.min(entries.length, 8); // Max 8 visible segments
            const segmentAngle = (2 * Math.PI) / numSegments;
            
            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(wheelRotation);
            
            for (let i = 0; i < numSegments; i++) {
                const startAngle = i * segmentAngle - Math.PI / 2;
                const endAngle = (i + 1) * segmentAngle - Math.PI / 2;
                
                // Draw segment
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.arc(0, 0, radius, startAngle, endAngle);
                ctx.closePath();
                ctx.fillStyle = WHEEL_COLORS[i % WHEEL_COLORS.length];
                ctx.fill();
                ctx.strokeStyle = 'rgba(0,0,0,0.2)';
                ctx.lineWidth = 2;
                ctx.stroke();
                
                // Draw text (rotated to match segment)
                ctx.save();
                const textAngle = startAngle + segmentAngle / 2;
                ctx.rotate(textAngle + Math.PI / 2);
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px LINESeedSansTH, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                // Truncate text if too long
                const entryIndex = i % entries.length;
                let text = entries[entryIndex]?.name || '';
                if (text.length > 12) text = text.substring(0, 10) + '...';
                
                ctx.fillText(text, 0, -radius * 0.65);
                ctx.restore();
            }
            
            ctx.restore();
            
            // Draw center circle (behind the button)
            ctx.beginPath();
            ctx.arc(centerX, centerY, 55, 0, 2 * Math.PI);
            ctx.fillStyle = '#1e1e1e';
            ctx.fill();
        }

        function startWheelSpin() {
            if (wheelSpinning) return;
            wheelSpinning = true;
            
            const spinSpeed = 0.3; // radians per frame
            
            function animate() {
                if (!wheelSpinning) return;
                
                wheelRotation += spinSpeed;
                if (wheelRotation > 2 * Math.PI) {
                    wheelRotation -= 2 * Math.PI;
                }
                
                drawWheel();
                wheelAnimationId = requestAnimationFrame(animate);
            }
            
            animate();
        }

        function stopWheelSpin() {
            wheelSpinning = false;
            if (wheelAnimationId) {
                cancelAnimationFrame(wheelAnimationId);
                wheelAnimationId = null;
            }
        }
    </script>
</body>
</html>