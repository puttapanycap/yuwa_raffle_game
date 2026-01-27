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
    <style>
        .remote-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 1rem;
        }
        .control-section {
            margin-bottom: 1.5rem;
        }
        .control-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .main-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .control-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .control-btn i {
            font-size: 2rem;
        }
        .control-btn:hover {
            background: var(--card-bg-hover);
            transform: translateY(-2px);
        }
        .control-btn:active {
            transform: translateY(0);
        }
        .control-btn.spin-btn {
            grid-column: span 2;
            background: linear-gradient(135deg, var(--raffle-primary) 0%, var(--raffle-primary-dark) 100%);
            border: none;
            padding: 2rem;
        }
        .control-btn.spin-btn i {
            font-size: 3rem;
        }
        .control-btn.spin-btn.spinning {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            animation: pulse-spin 0.5s ease-in-out infinite;
        }
        .control-btn.danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .control-btn.danger:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        .control-btn.success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.3);
        }
        .control-btn.success:hover {
            background: rgba(34, 197, 94, 0.2);
        }
        .status-card {
            padding: 1rem 1.5rem;
            background: var(--card-bg);
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .status-label {
            color: var(--text-secondary);
        }
        .status-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .status-value.winner {
            color: var(--result-font-color, var(--raffle-gold));
        }
        .prize-select {
            width: 100%;
            padding: 1rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 1rem;
        }
        .entries-list {
            max-height: 300px;
            overflow-y: auto;
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1rem;
        }
        .entry-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .entry-item:last-child {
            border-bottom: none;
        }
        .entry-item.winner {
            background: rgba(34, 197, 94, 0.1);
            border-radius: 8px;
            border-bottom: none;
            margin-bottom: 4px;
        }
        .winner-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 0.5rem;
        }
        .winner-item .prize {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            padding-left: 2.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--raffle-primary);
        }
        .search-input::placeholder {
            color: var(--text-secondary);
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            margin-top: -0.375rem;
        }
        .filter-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        .filter-row .search-wrapper {
            flex: 1;
        }
        .filter-row .search-input {
            margin-bottom: 0;
        }
        .filter-select {
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.85rem;
            min-width: 140px;
            cursor: pointer;
        }
        .filter-select:focus {
            outline: none;
            border-color: var(--raffle-primary);
        }
        .entry-item .delete-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            transition: all 0.2s;
            opacity: 0.5;
        }
        .entry-item:hover .delete-btn {
            opacity: 1;
        }
        .entry-item .delete-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .tab-btn {
            flex: 1;
            padding: 0.75rem;
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--raffle-primary);
            color: #fff;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        @keyframes pulse-spin {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    </style>
</head>
<body class="raffle-app" data-theme="dark">
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

    <script>
        const RAFFLE_KEY = '<?= $raffle_key ?>';
        let currentState = 'idle';
        let settings = {};
        let prizeCategories = [];
        let pollInterval = null;
        let currentWinner = null;
        let currentWinnerId = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            loadEntries();
            loadWinners();
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
                            select.innerHTML = '<option value="">ไม่ระบุประเภท</option>';
                            prizeCategories.forEach(p => {
                                const remaining = p.quantity - p.winners_count;
                                select.innerHTML += `<option value="${p.id}" style="color: ${p.category_color}">${p.category_name} (เหลือ ${remaining}/${p.quantity})</option>`;
                            });
                        }

                        // Populate prize filter dropdown
                        const prizeFilter = document.getElementById('prizeFilter');
                        prizeFilter.innerHTML = '<option value="">ทุกรางวัล</option>';
                        prizeFilter.innerHTML += '<option value="none">ไม่ระบุรางวัล</option>';
                        prizeCategories.forEach(p => {
                            prizeFilter.innerHTML += `<option value="${p.id}">${p.category_name}</option>`;
                        });
                    }
                });
        }

        let allEntries = []; // Store all entries for filtering

        function loadEntries() {
            fetch(`./actions/get_entries.php?raffle_key=${RAFFLE_KEY}&filter=available`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allEntries = data.entries;
                        renderEntries(data.entries);
                        document.getElementById('remainingCount').textContent = data.counts.available;
                    }
                });
        }

        function renderEntries(entries) {
            const list = document.getElementById('entriesList');
            if (entries.length === 0) {
                list.innerHTML = '<div class="text-center text-muted py-4">ไม่พบรายชื่อ</div>';
            } else {
                list.innerHTML = entries.map(e => `
                    <div class="entry-item" data-name="${e.name.toLowerCase()}">
                        <span>${e.name}</span>
                        <button class="delete-btn" onclick="deleteEntry(${e.id}, '${e.name.replace(/'/g, "\\'")}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                `).join('');
            }
        }

        function filterEntries() {
            const searchTerm = document.getElementById('entrySearch').value.toLowerCase().trim();
            if (searchTerm === '') {
                renderEntries(allEntries);
            } else {
                const filtered = allEntries.filter(e => 
                    e.name.toLowerCase().includes(searchTerm)
                );
                renderEntries(filtered);
            }
        }

        function deleteEntry(entryId, entryName) {
            Swal.fire({
                title: 'ลบรายชื่อ?',
                text: `ต้องการลบ "${entryName}" ออกจากรายชื่อหรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ef4444'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('raffle_key', RAFFLE_KEY);
                    formData.append('entry_id', entryId);

                    fetch('./actions/delete_entry.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบแล้ว!',
                                text: `${entryName} ถูกลบออกจากรายชื่อ`,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadEntries();
                        } else {
                            Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                        }
                    });
                }
            });
        }

        let allWinners = []; // Store all winners for filtering
        let winnersTotal = 0;

        function loadWinners() {
            fetch(`./actions/get_winners.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allWinners = data.logs;
                        winnersTotal = data.total;
                        renderWinners(data.logs, data.total);

                        // Update last winner
                        if (data.logs.length > 0) {
                            document.getElementById('lastWinner').textContent = data.logs[data.logs.length - 1].winner_name;
                        }
                    }
                });
        }

        function renderWinners(winners, total) {
            const list = document.getElementById('winnersList');
            if (winners.length === 0) {
                list.innerHTML = '<div class="text-center text-muted py-4">ไม่พบรายชื่อ</div>';
            } else {
                list.innerHTML = winners.map((w, i) => {
                    const ts = new Date(w.created_at);
                    const timeStr = ts.toLocaleString('th-TH', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: '2-digit' });
                    // Find original index for correct numbering
                    const originalIndex = allWinners.findIndex(aw => aw.id === w.id);
                    return `
                    <div class="winner-item" data-name="${w.winner_name.toLowerCase()}">
                        <div>
                            <span class="text-muted me-2">#${winnersTotal - originalIndex}</span>
                            <strong>${w.winner_name}</strong>
                            <div class="text-muted" style="font-size: 0.75rem;">${timeStr}</div>
                        </div>
                        ${w.prize_name ? `<span class="prize" style="background: ${w.prize_color}20; color: ${w.prize_color}">${w.prize_name}</span>` : ''}
                    </div>
                `}).join('');
            }
        }

        function filterWinners() {
            const searchTerm = document.getElementById('winnerSearch').value.toLowerCase().trim();
            const prizeFilterValue = document.getElementById('prizeFilter').value;
            
            let filtered = allWinners;

            // Filter by search term
            if (searchTerm !== '') {
                filtered = filtered.filter(w => 
                    w.winner_name.toLowerCase().includes(searchTerm)
                );
            }

            // Filter by prize category
            if (prizeFilterValue !== '') {
                if (prizeFilterValue === 'none') {
                    // Show only winners without a prize
                    filtered = filtered.filter(w => !w.prize_id);
                } else {
                    // Show winners with specific prize category
                    filtered = filtered.filter(w => w.prize_id == prizeFilterValue);
                }
            }

            renderWinners(filtered, winnersTotal);
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
                        document.getElementById('remainingCount').textContent = data.entries.remaining;

                        // Check for stale spinning state (stuck for more than 30 seconds)
                        if (newState === 'spinning' && data.state.last_updated) {
                            const lastUpdated = new Date(data.state.last_updated.replace(' ', 'T') + '+07:00');
                            const now = new Date();
                            const staleSeconds = (now - lastUpdated) / 1000;
                            console.log('Spinning state age:', staleSeconds, 'seconds');
                            
                            if (staleSeconds > 30) {
                                console.warn('Stale spinning state detected! Resetting to idle...');
                                // Reset state to idle
                                const resetData = new FormData();
                                resetData.append('raffle_key', RAFFLE_KEY);
                                resetData.append('action', 'idle');
                                fetch('./actions/set_state.php', { method: 'POST', body: resetData });
                                return; // Exit and let next poll pick up the new state
                            }
                        }

                        if (newState !== currentState) {
                            handleStateChange(newState, data.state);
                        }
                        currentState = newState;
                    }
                });
        }

        function handleStateChange(state, stateData) {
            const spinBtn = document.getElementById('spinBtn');
            const confirmBtn = document.getElementById('confirmBtn');
            const rerollBtn = document.getElementById('rerollBtn');

            switch(state) {
                case 'idle':
                    spinBtn.disabled = false;
                    spinBtn.classList.remove('spinning');
                    spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่ม</span>';
                    confirmBtn.disabled = true;
                    rerollBtn.disabled = true;
                    document.getElementById('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>พร้อม';
                    currentWinner = null;
                    currentWinnerId = null;
                    break;

                case 'spinning':
                    spinBtn.disabled = true;
                    spinBtn.classList.add('spinning');
                    spinBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>กำลังสุ่ม...</span>';
                    confirmBtn.disabled = true;
                    rerollBtn.disabled = true;
                    document.getElementById('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>กำลังสุ่ม...';
                    break;

                case 'revealing':
                    spinBtn.disabled = true;
                    spinBtn.classList.remove('spinning');
                    confirmBtn.disabled = false;
                    rerollBtn.disabled = false;
                    currentWinner = stateData.winner;
                    currentWinnerId = stateData.winner_id;
                    document.getElementById('currentStatus').innerHTML = `<span class="status-dot online me-2"></span>ผู้โชคดี: <strong class="text-warning">${stateData.winner}</strong>`;
                    spinBtn.innerHTML = `<i class="fa-solid fa-star text-warning"></i><span>${stateData.winner}</span>`;
                    break;

                case 'confirmed':
                    spinBtn.disabled = false;
                    spinBtn.classList.remove('spinning');
                    spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่มรายการถัดไป</span>';
                    confirmBtn.disabled = true;
                    rerollBtn.disabled = true;
                    document.getElementById('currentStatus').innerHTML = '<span class="status-dot online me-2"></span>ยืนยันแล้ว';
                    loadEntries();
                    loadWinners();
                    break;
            }
        }

        let spinTimeoutId = null; // Track timeout to clear on errors
        
        function triggerSpin() {
            // If confirmed state, reset first
            if (currentState === 'confirmed') {
                resetState();
                setTimeout(triggerSpin, 300);
                return;
            }

            // Clear any existing timeout
            if (spinTimeoutId) {
                clearTimeout(spinTimeoutId);
                spinTimeoutId = null;
            }

            // Check if selected prize category has remaining quantity
            const prizeId = document.getElementById('prizeSelect')?.value || '';
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

            const spinBtn = document.getElementById('spinBtn');
            spinBtn.disabled = true;
            spinBtn.classList.add('spinning');
            spinBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i><span>กำลังสุ่ม...</span>';
            
            fetch(`./actions/pick_random.php?raffle_key=${RAFFLE_KEY}&prize_id=${prizeId}`)
                .then(r => {
                    console.log('pick_random response status:', r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('pick_random data:', data);
                    if (data.success) {
                        currentWinner = data.winner.name;
                        currentWinnerId = data.winner.id;

                        // Set spinning state
                        const formData = new FormData();
                        formData.append('raffle_key', RAFFLE_KEY);
                        formData.append('action', 'spinning');
                        formData.append('triggered_by', 'remote');

                        fetch('./actions/set_state.php', {
                            method: 'POST',
                            body: formData
                        });

                        // After spin duration, reveal winner
                        const duration = (parseInt(settings.spin_duration) || 5) * 1000;
                        console.log('Spin Duration:', duration, 'ms (from settings:', settings.spin_duration, ')');
                        spinTimeoutId = setTimeout(() => {
                            spinTimeoutId = null;
                            const revealData = new FormData();
                            revealData.append('raffle_key', RAFFLE_KEY);
                            revealData.append('action', 'revealing');
                            revealData.append('winner', currentWinner);
                            revealData.append('winner_id', currentWinnerId);
                            revealData.append('prize_id', prizeId);
                            
                            fetch('./actions/set_state.php', {
                                method: 'POST',
                                body: revealData
                            });
                        }, duration);

                    } else {
                        // Error case: reset UI and state
                        console.error('pick_random failed:', data.error);
                        spinBtn.disabled = false;
                        spinBtn.classList.remove('spinning');
                        spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่ม</span>';
                        
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
                    spinBtn.disabled = false;
                    spinBtn.classList.remove('spinning');
                    spinBtn.innerHTML = '<i class="fa-solid fa-dice"></i><span>สุ่ม</span>';
                    
                    // Reset state to idle
                    const resetData = new FormData();
                    resetData.append('raffle_key', RAFFLE_KEY);
                    resetData.append('action', 'idle');
                    fetch('./actions/set_state.php', { method: 'POST', body: resetData });
                    
                    Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                });
        }

        function confirmWinner() {
            if (!currentWinner) return;

            const prizeId = document.getElementById('prizeSelect')?.value || '';
            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('winner', currentWinner);
            formData.append('winner_id', currentWinnerId);
            formData.append('prize_id', prizeId);

            fetch('./actions/confirm_winner.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Set confirmed state
                    const stateData = new FormData();
                    stateData.append('raffle_key', RAFFLE_KEY);
                    stateData.append('action', 'confirmed');
                    stateData.append('winner', currentWinner);
                    stateData.append('winner_id', currentWinnerId);
                    
                    fetch('./actions/set_state.php', {
                        method: 'POST',
                        body: stateData
                    });

                    // Reload settings to update prize categories counts
                    loadSettings();

                    Swal.fire({
                        icon: 'success',
                        title: 'ยืนยันแล้ว!',
                        text: `${currentWinner} ได้รับรางวัล`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            });
        }

        function reroll() {
            // Reset to idle and spin again
            resetState();
            setTimeout(triggerSpin, 300);
        }

        function resetState() {
            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('action', 'idle');

            fetch('./actions/set_state.php', {
                method: 'POST',
                body: formData
            });
        }

        function refreshMainPage() {
            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('action', 'refresh');

            fetch('./actions/set_state.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'สั่ง Refresh แล้ว!',
                    text: 'หน้าหลักจะ Refresh ภายในไม่กี่วินาที',
                    timer: 1500,
                    showConfirmButton: false
                });
                // Reset back to idle after a short delay
                setTimeout(() => {
                    resetState();
                }, 1000);
            });
        }

        function undoWinner() {
            Swal.fire({
                title: 'ยกเลิกผู้โชคดีล่าสุด?',
                text: 'ผู้โชคดีล่าสุดจะถูกนำกลับเข้ารายชื่อ',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('raffle_key', RAFFLE_KEY);

                    fetch('./actions/undo_winner.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ยกเลิกแล้ว!',
                                text: `${data.undone_winner} ถูกนำกลับเข้ารายชื่อ`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadEntries();
                            loadWinners();
                        } else {
                            Swal.fire('Error', data.error || 'ไม่มีรายการให้ยกเลิก', 'error');
                        }
                    });
                }
            });
        }

        function showTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');

            if (tab === 'entries') loadEntries();
            if (tab === 'winners') loadWinners();
        }
    </script>
</body>
</html>
