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
        html, body {
            height: 100%;
            overflow: hidden;
        }
        .vip-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
        }
        .event-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .btn-spin-vip {
            width: 70vmin;
            height: 70vmin;
            max-width: 400px;
            max-height: 400px;
            border-radius: 50%;
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(145deg, #ff6b6b, #ee5a24);
            border: none;
            color: #fff;
            cursor: pointer;
            box-shadow: 
                0 20px 60px rgba(238, 90, 36, 0.5),
                inset 0 -10px 0 rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-spin-vip::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            transition: 0.5s;
        }
        .btn-spin-vip:not(:disabled):hover::before {
            left: 100%;
        }
        .btn-spin-vip:not(:disabled):hover {
            transform: scale(1.05);
            box-shadow: 
                0 30px 80px rgba(238, 90, 36, 0.6),
                inset 0 -10px 0 rgba(0, 0, 0, 0.2);
        }
        .btn-spin-vip:not(:disabled):active {
            transform: scale(0.95);
            box-shadow: 
                0 10px 30px rgba(238, 90, 36, 0.4),
                inset 0 -5px 0 rgba(0, 0, 0, 0.2);
        }
        .btn-spin-vip:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .btn-spin-vip.spinning {
            animation: pulse-button 0.5s ease-in-out infinite;
            background: linear-gradient(145deg, #fbbf24, #f59e0b);
        }
        @keyframes pulse-button {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }
        .status-text {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-top: 2rem;
        }
        .winner-reveal {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .winner-reveal.show {
            display: flex;
        }
        .winner-label {
            font-size: 1.5rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .winner-name-display {
            font-size: 4rem;
            font-weight: 800;
            color: var(--result-font-color, var(--raffle-gold));
            text-shadow: 0 0 40px rgba(251, 191, 36, 0.5);
            animation: winner-pulse 2s ease-in-out infinite;
        }
        @keyframes winner-pulse {
            0%, 100% { transform: scale(1); text-shadow: 0 0 40px rgba(251, 191, 36, 0.5); }
            50% { transform: scale(1.02); text-shadow: 0 0 60px rgba(251, 191, 36, 0.8); }
        }
        .spin-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .spin-container.hide {
            display: none;
        }
        .connection-status {
            position: fixed;
            top: 1rem;
            right: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--card-bg);
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .prize-section {
            margin-bottom: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .prize-select {
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--card-bg);
            border: 2px solid var(--card-border);
            border-radius: 16px;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .prize-select:focus {
            outline: none;
            border-color: var(--raffle-primary);
        }
    </style>
</head>
<body class="raffle-app" data-theme="dark">
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
    <canvas id="confetti-canvas"></canvas>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="./js/confetti.js"></script>

    <script>
        const RAFFLE_KEY = '<?= $raffle_key ?>';
        let currentState = 'idle';
        let settings = {};
        let prizeCategories = [];
        let pollInterval = null;

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
                        prizeCategories = data.prize_categories || [];
                        
                        // Apply theme
                        document.body.setAttribute('data-theme', settings.theme || 'dark');
                        document.documentElement.style.setProperty('--result-font-color', settings.result_font_color || 'var(--raffle-gold)');

                        // Show prize selection if enabled
                        if (settings.enable_prize_categories == 1 && prizeCategories.length > 0) {
                            document.getElementById('prizeSection').style.display = 'block';
                            const select = document.getElementById('prizeSelect');
                            // Save current selection before rebuilding
                            const currentSelection = select.value;
                            select.innerHTML = '<option value="">🎯 ไม่ระบุประเภท</option>';
                            prizeCategories.forEach(p => {
                                const remaining = p.quantity - p.winners_count;
                                const disabled = remaining <= 0 ? 'disabled' : '';
                                const suffix = remaining <= 0 ? ' (หมดแล้ว)' : ` (เหลือ ${remaining}/${p.quantity})`;
                                select.innerHTML += `<option value="${p.id}" ${disabled}>${p.category_name}${suffix}</option>`;
                            });
                            // Restore previous selection if still valid
                            if (currentSelection && select.querySelector(`option[value="${currentSelection}"]:not([disabled])`)) {
                                select.value = currentSelection;
                            }
                        }

                    }
                });
        }

        function startPolling() {
            pollInterval = setInterval(checkState, 500);
        }

        function checkState() {
            fetch(`./actions/get_state.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const newState = data.state.action;
                        
                        // Update connection status
                        document.getElementById('statusDot').classList.add('online');
                        document.getElementById('statusText').textContent = `${data.entries.remaining} รายชื่อคงเหลือ`;



                        // Handle state changes
                        if (newState !== currentState) {
                            handleStateChange(newState, data.state);
                        }
                        currentState = newState;
                    }
                })
                .catch(err => {
                    document.getElementById('statusDot').classList.remove('online');
                    document.getElementById('statusText').textContent = 'ขาดการเชื่อมต่อ';
                });
        }

        function handleStateChange(state, stateData) {
            const btn = document.getElementById('spinBtn');
            const spinContainer = document.getElementById('spinContainer');
            const winnerReveal = document.getElementById('winnerReveal');
            const prizeSelect = document.getElementById('prizeSelect');

            switch(state) {
                case 'idle':
                    btn.disabled = false;
                    btn.classList.remove('spinning');
                    btn.innerHTML = '<i class="fa-solid fa-dice fa-bounce"></i><div class="mt-3">สุ่ม</div>';
                    document.getElementById('statusMessage').textContent = 'แตะปุ่มเพื่อสุ่มรายชื่อ';
                    spinContainer.classList.remove('hide');
                    winnerReveal.classList.remove('show');
                    // Enable prize selection
                    if (prizeSelect) prizeSelect.disabled = false;
                    break;

                case 'spinning':
                    btn.disabled = true;
                    btn.classList.add('spinning');
                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-3">กำลังสุ่ม...</div>';
                    document.getElementById('statusMessage').textContent = 'รอผลการสุ่ม...';
                    // Disable prize selection during spinning
                    if (prizeSelect) prizeSelect.disabled = true;
                    break;

                case 'revealing':
                case 'confirmed':
                    spinContainer.classList.add('hide');
                    winnerReveal.classList.add('show');
                    document.getElementById('winnerName').textContent = stateData.winner || 'ผู้โชคดี';
                    // Keep prize selection disabled while showing winner
                    if (prizeSelect) prizeSelect.disabled = true;
                    
                    // Play confetti if enabled
                    if (settings.enable_confetti != 0 && typeof confetti !== 'undefined') {
                        confetti.start();
                        setTimeout(() => confetti.stop(), 3000);
                    }
                    break;
            }
        }

        function triggerSpin() {
            const btn = document.getElementById('spinBtn');
            const prizeId = document.getElementById('prizeSelect')?.value || '';

            // Check if selected prize category has remaining quantity
            if (prizeId !== '') {
                const selectedPrize = prizeCategories.find(p => p.id == prizeId);
                if (selectedPrize) {
                    const remaining = selectedPrize.quantity - selectedPrize.winners_count;
                    if (remaining <= 0) {
                        Swal.fire('หมดแล้ว', `รางวัล "${selectedPrize.category_name}" หมดแล้ว กรุณาเลือกประเภทอื่น`, 'warning');
                        return;
                    }
                }
            }

            btn.disabled = true;
            btn.classList.add('spinning');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><div class="mt-3">กำลังสุ่ม...</div>';

            // Pick random winner first (like index.php)
            fetch(`./actions/pick_random.php?raffle_key=${RAFFLE_KEY}&prize_id=${prizeId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Set spinning state
                        const formData = new FormData();
                        formData.append('raffle_key', RAFFLE_KEY);
                        formData.append('action', 'spinning');
                        formData.append('triggered_by', 'vip');
                        fetch('./actions/set_state.php', { method: 'POST', body: formData });

                        // After duration, reveal and confirm
                        const duration = (parseInt(settings.spin_duration) || 5) * 1000;
                        console.log('VIP Spin Duration:', duration, 'ms');
                        
                        setTimeout(() => {
                            // Reveal
                            const revealData = new FormData();
                            revealData.append('raffle_key', RAFFLE_KEY);
                            revealData.append('action', 'revealing');
                            revealData.append('winner', data.winner.name);
                            revealData.append('winner_id', data.winner.id);
                            revealData.append('prize_id', prizeId);
                            fetch('./actions/set_state.php', { method: 'POST', body: revealData });

                            // Auto-confirm after 300ms (faster response)
                            setTimeout(() => {
                                const confirmData = new FormData();
                                confirmData.append('raffle_key', RAFFLE_KEY);
                                confirmData.append('winner', data.winner.name);
                                confirmData.append('winner_id', data.winner.id);
                                confirmData.append('prize_id', prizeId);
                                fetch('./actions/confirm_winner.php', { method: 'POST', body: confirmData })
                                    .then(r => r.json())
                                    .then(() => {
                                        // Reload settings to update prize counts after confirm completes
                                        loadSettings();
                                    });
                            }, 300);
                        }, duration);
                    } else {
                        Swal.fire('ไม่มีรายชื่อ', data.error || 'ไม่มีรายชื่อให้สุ่มแล้ว', 'warning');
                        btn.disabled = false;
                        btn.classList.remove('spinning');
                        btn.innerHTML = '<i class="fa-solid fa-dice fa-bounce"></i><div class="mt-3">สุ่ม</div>';
                    }
                })
                .catch(err => {
                    console.error('VIP Spin error:', err);
                    Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                    btn.disabled = false;
                    btn.classList.remove('spinning');
                    btn.innerHTML = '<i class="fa-solid fa-dice fa-bounce"></i><div class="mt-3">สุ่ม</div>';
                });
        }

        function resetForNext() {
            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('action', 'idle');

            fetch('./actions/set_state.php', {
                method: 'POST',
                body: formData
            });
        }
    </script>
</body>
</html>
