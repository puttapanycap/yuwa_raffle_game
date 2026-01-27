<?php
/**
 * Raffle Game Pro - Settings Page
 * Configure all raffle settings
 */
define("_WEBROOT_PATH_", "./");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

// Get or create raffle key
if (!isset($_GET['share'])) {
    $raffle_key = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO raffle_keys (raffle_key) VALUES (:raffle_key)");
    $stmt->execute(['raffle_key' => $raffle_key]);
    header("Location: ./settings.php?share=$raffle_key");
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <link href="./css/raffle-theme.css" rel="stylesheet" type="text/css" />
    <title>⚙️ ตั้งค่า | Raffle Game Pro</title>
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }
        .settings-grid {
            display: grid;
            gap: 1.5rem;
        }
        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--card-border);
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-label {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .setting-label strong {
            color: var(--text-primary);
            font-size: 1rem;
        }
        .setting-label small {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .form-control-raffle {
            min-width: 200px;
        }
        .textarea-prizes {
            width: 100%;
            min-height: 120px;
            resize: vertical;
        }
        .number-range-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .number-range-group input {
            width: 100px;
        }
        .upload-area {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .upload-btn {
            position: relative;
            overflow: hidden;
        }
        .upload-btn input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .current-sound {
            padding: 0.5rem 1rem;
            background: var(--card-bg);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .link-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 12px;
            margin-top: 0.5rem;
        }
        .link-box input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        .qr-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .qr-modal.show {
            display: flex;
        }
        .qr-modal-content {
            background: #fff;
            padding: 2rem;
            border-radius: 24px;
            text-align: center;
        }
        .qr-modal-content h3 {
            color: #333;
            margin-bottom: 1rem;
        }
        .lock-warning {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }
        .lock-warning i {
            font-size: 1.5rem;
            color: var(--raffle-danger);
        }
    </style>
</head>
<body class="raffle-app" data-theme="dark">
    <div class="raffle-bg-animated"></div>
    
    <!-- Header -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <a href="./index.php?share=<?= $raffle_key ?>" class="btn btn-raffle-ghost">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h1 class="page-header-title">
                <i class="fa-solid fa-gear me-2"></i>ตั้งค่า
            </h1>
        </div>
        <div class="page-header-actions">
            <span class="badge bg-dark text-white p-2 rounded-pill">
                <span class="status-dot online me-1"></span>
                Session Active
            </span>
        </div>
    </div>

    <div class="settings-container">
        <!-- Lock Warning -->
        <div class="lock-warning" id="lockWarning" style="display: none;">
            <i class="fa-solid fa-lock"></i>
            <div>
                <strong>Session ถูกล็อค</strong>
                <p class="mb-0 small">ไม่สามารถแก้ไขการตั้งค่าได้ กรุณาปลดล็อคก่อน</p>
            </div>
            <button class="btn btn-raffle-danger ms-auto" onclick="toggleLock()">
                <i class="fa-solid fa-unlock me-1"></i>ปลดล็อค
            </button>
        </div>

        <div class="settings-grid">
            <!-- General Settings -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-sliders"></i>
                    ตั้งค่าทั่วไป
                </h3>
                
                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ชื่องาน / Event Title</strong>
                        <small>แสดงบน Header ของหน้าหลัก</small>
                    </div>
                    <input type="text" class="form-control form-control-raffle" id="eventTitle" 
                           value="<?= htmlspecialchars($raffleInfo['event_title'] ?? 'Raffle Game') ?>"
                           placeholder="ชื่องาน">
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ขนาดตัวหนังสือ (ผลการสุ่ม)</strong>
                        <small>ปรับขนาด font สำหรับแสดงผู้โชคดี (px)</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" class="form-range" id="fontSizeRange" min="36" max="200" value="72" style="width: 150px;">
                        <input type="number" class="form-control form-control-raffle" id="fontSize" 
                               value="72" min="36" max="200" style="width: 80px;">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>สีตัวหนังสือ (ผลการสุ่ม)</strong>
                        <small>เลือกสีสำหรับแสดงผลผู้โชคดี</small>
                    </div>
                    <input type="color" class="form-control form-control-color" id="resultFontColor" value="#ffffff" title="เลือกสี" style="width: 80px;">
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ขนาดตัวหนังสือ (รายชื่อผู้เข้าร่วม)</strong>
                        <small>ปรับขนาด font สำหรับแสดงรายชื่อผู้เข้าร่วม (px)</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" class="form-range" id="entryFontSizeRange" min="12" max="48" value="16" style="width: 150px;">
                        <input type="number" class="form-control form-control-raffle" id="entryFontSize" 
                               value="16" min="12" max="48" style="width: 80px;">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ขนาดตัวหนังสือ (ผู้โชคดี)</strong>
                        <small>ปรับขนาด font สำหรับแสดงรายชื่อผู้โชคดี (px)</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" class="form-range" id="winnerFontSizeRange" min="12" max="48" value="16" style="width: 150px;">
                        <input type="number" class="form-control form-control-raffle" id="winnerFontSize" 
                               value="16" min="12" max="48" style="width: 80px;">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เวลาสุ่มต่อครั้ง (วินาที)</strong>
                        <small>ระยะเวลา Animation ก่อนแสดงผลผู้โชคดี</small>
                    </div>
                    <input type="number" class="form-control form-control-raffle" id="spinDuration" 
                           value="5" min="1" max="30" style="width: 100px;">
                </div>
            </div>

            <!-- Display Settings -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-display"></i>
                    ตั้งค่า Display
                </h3>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>แสดงปุ่มสุ่มหน้าหลัก</strong>
                        <small>แสดงปุ่มสุ่มบนหน้า Main Display</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="showMainButton">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>Template Animation</strong>
                        <small>รูปแบบการแสดงผลตอนสุ่ม</small>
                    </div>
                    <div class="d-flex gap-2">
                        <label class="radio-card" id="templateTextRoll">
                            <input type="radio" name="template" value="text_roll" checked>
                            <i class="fa-solid fa-text-height me-2"></i>
                            ตัวหนังสือว่องไว
                        </label>
                        <label class="radio-card" id="templateWheel">
                            <input type="radio" name="template" value="wheel">
                            <i class="fa-solid fa-circle-notch me-2"></i>
                            วงล้อ
                        </label>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>Theme</strong>
                        <small>รูปแบบสี</small>
                    </div>
                    <div class="d-flex gap-2">
                        <label class="radio-card" id="themeDark">
                            <input type="radio" name="theme" value="dark" checked>
                            <i class="fa-solid fa-moon me-2"></i>
                            Dark
                        </label>
                        <label class="radio-card" id="themeLight">
                            <input type="radio" name="theme" value="light">
                            <i class="fa-solid fa-sun me-2"></i>
                            Light
                        </label>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เปิด/ปิด Effect พลุ (Confetti)</strong>
                        <small>แสดง Confetti เมื่อมีผู้โชคดี</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enableConfetti" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Sound Settings -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-volume-high"></i>
                    ตั้งค่าเสียง
                </h3>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เปิด/ปิด Sound Effects</strong>
                        <small>เล่นเสียงตอนสุ่มและประกาศผล</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enableSound" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เสียงตอนสุ่ม (Spinning)</strong>
                        <small>อัพโหลดไฟล์เสียง .mp3, .wav, .ogg (max 5MB)</small>
                    </div>
                    <div class="upload-area">
                        <span class="current-sound" id="currentSpinSound">ใช้เสียงเริ่มต้น</span>
                        <label class="btn btn-raffle upload-btn">
                            <i class="fa-solid fa-upload me-1"></i>อัพโหลด
                            <input type="file" accept=".mp3,.wav,.ogg" onchange="uploadSound('spin', this)">
                        </label>
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เสียงตอนประกาศผล (Winner)</strong>
                        <small>อัพโหลดไฟล์เสียง .mp3, .wav, .ogg (max 5MB)</small>
                    </div>
                    <div class="upload-area">
                        <span class="current-sound" id="currentWinnerSound">ใช้เสียงเริ่มต้น</span>
                        <label class="btn btn-raffle upload-btn">
                            <i class="fa-solid fa-upload me-1"></i>อัพโหลด
                            <input type="file" accept=".mp3,.wav,.ogg" onchange="uploadSound('winner', this)">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Auto Generate Numbers -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-list-ol"></i>
                    สร้างฉลากตัวเลขอัตโนมัติ
                </h3>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>Prefix (นำหน้า)</strong>
                        <small>ข้อความนำหน้าตัวเลข เช่น "No." หรือ "หมายเลข "</small>
                    </div>
                    <input type="text" class="form-control form-control-raffle" id="autoPrefix" 
                           placeholder="No." style="width: 120px;">
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ช่วงตัวเลข</strong>
                        <small>กำหนดเลขเริ่มต้นและสิ้นสุด</small>
                    </div>
                    <div class="number-range-group">
                        <input type="number" class="form-control form-control-raffle" id="autoStart" 
                               value="1" min="0" placeholder="เริ่มต้น">
                        <span class="text-muted">ถึง</span>
                        <input type="number" class="form-control form-control-raffle" id="autoEnd" 
                               value="100" min="0" placeholder="สิ้นสุด">
                    </div>
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ยกเว้นตัวเลข</strong>
                        <small>ระบุตัวเลขที่ไม่ต้องการสร้าง คั่นด้วย , หรือช่วง เช่น 1,5,10-15,20</small>
                    </div>
                    <input type="text" class="form-control form-control-raffle" id="excludeNumbers" 
                           placeholder="เช่น 1,5,10-15,20" style="width: 200px;">
                </div>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ล้างรายชื่อเดิม</strong>
                        <small>ลบรายชื่อเดิมก่อนสร้างใหม่</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="clearExisting" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="text-end mt-3">
                    <button class="btn btn-raffle-gold" onclick="generateNumbers()">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                        สร้างฉลาก
                    </button>
                </div>
            </div>

            <!-- Edit Names -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-users"></i>
                    แก้ไขรายชื่อ
                </h3>

                <div class="setting-row" style="flex-direction: column; align-items: stretch;">
                    <div class="setting-label mb-2">
                        <strong>รายชื่อทั้งหมด</strong>
                        <small>กรอกรายชื่อบรรทัดละ 1 ชื่อ</small>
                    </div>
                    <textarea class="form-control form-control-raffle textarea-prizes" id="namesList" 
                              style="min-height: 200px;"
                              placeholder="กรอกรายชื่อ บรรทัดละ 1 ชื่อ
เช่น:
สมชาย ใจดี
สมหญิง รักเรียน
No.001
No.002"></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span class="badge bg-info text-white" id="namesCount">0 รายชื่อ</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-raffle-ghost" onclick="loadNames()">
                            <i class="fa-solid fa-rotate me-1"></i>
                            โหลดใหม่
                        </button>
                        <button class="btn btn-raffle-gold" onclick="saveNames()">
                            <i class="fa-solid fa-save me-1"></i>
                            บันทึกรายชื่อ
                        </button>
                    </div>
                </div>
            </div>

            <!-- Prize Categories -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-trophy"></i>
                    ประเภทรางวัล
                </h3>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>เปิดใช้งานประเภทรางวัล</strong>
                        <small>แบ่งเป็นรางวัลหลายประเภท</small>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enablePrizeCategories">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div id="prizeCategoriesSection" style="display: none;">
                    <div class="setting-label mb-2">
                        <strong>รายการประเภทรางวัล</strong>
                        <small>กรอกบรรทัดละ 1 ประเภท (รูปแบบ: ชื่อรางวัล | จำนวน | สี)</small>
                    </div>
                    <textarea class="form-control form-control-raffle textarea-prizes" id="prizeList" 
                              placeholder="รางวัลใหญ่ | 1 | #FFD700
รางวัลที่ 1 | 3 | #C0C0C0
รางวัลที่ 2 | 5 | #CD7F32
รางวัลปลอบใจ | 10 | #6366f1"></textarea>
                </div>
            </div>

            <!-- Session Lock -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-shield-halved"></i>
                    ล็อค Session
                </h3>

                <div class="setting-row">
                    <div class="setting-label">
                        <strong>ล็อค Session ระหว่างงาน</strong>
                        <small>ป้องกันการแก้ไขโดยไม่ได้ตั้งใจ</small>
                    </div>
                    <button class="btn btn-raffle-danger" id="lockBtn" onclick="toggleLock()">
                        <i class="fa-solid fa-lock me-1"></i>ล็อค
                    </button>
                </div>
            </div>

            <!-- Links -->
            <div class="glass-card-static p-4">
                <h3 class="settings-section-title">
                    <i class="fa-solid fa-link"></i>
                    Links สำหรับแชร์
                </h3>

                <div class="setting-label mb-2">
                    <strong>📺 Main Display</strong>
                    <small>หน้าแสดงผลหลัก</small>
                </div>
                <div class="link-box">
                    <input type="text" id="linkMain" readonly value="">
                    <button class="btn btn-raffle-ghost btn-sm" onclick="copyLink('linkMain')" title="คัดลอก">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="openLink('linkMain')" title="เปิด Tab ใหม่">
                        <i class="fa-solid fa-external-link-alt"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="showQR('Main Display', 'linkMain')" title="QR Code">
                        <i class="fa-solid fa-qrcode"></i>
                    </button>
                </div>

                <div class="setting-label mb-2 mt-3">
                    <strong>🔘 VIP Button</strong>
                    <small>หน้าปุ่มสุ่มสำหรับ VIP</small>
                </div>
                <div class="link-box">
                    <input type="text" id="linkVIP" readonly value="">
                    <button class="btn btn-raffle-ghost btn-sm" onclick="copyLink('linkVIP')" title="คัดลอก">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="openLink('linkVIP')" title="เปิด Tab ใหม่">
                        <i class="fa-solid fa-external-link-alt"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="showQR('VIP Button', 'linkVIP')" title="QR Code">
                        <i class="fa-solid fa-qrcode"></i>
                    </button>
                </div>

                <div class="setting-label mb-2 mt-3">
                    <strong>🎮 Remote Control</strong>
                    <small>หน้าควบคุมระยะไกล</small>
                </div>
                <div class="link-box">
                    <input type="text" id="linkRemote" readonly value="">
                    <button class="btn btn-raffle-ghost btn-sm" onclick="copyLink('linkRemote')" title="คัดลอก">
                        <i class="fa-solid fa-copy"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="openLink('linkRemote')" title="เปิด Tab ใหม่">
                        <i class="fa-solid fa-external-link-alt"></i>
                    </button>
                    <button class="btn btn-raffle-ghost btn-sm" onclick="showQR('Remote Control', 'linkRemote')" title="QR Code">
                        <i class="fa-solid fa-qrcode"></i>
                    </button>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-center py-4">
                <button class="btn btn-raffle btn-raffle-lg" onclick="saveSettings()">
                    <i class="fa-solid fa-floppy-disk me-2"></i>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div class="qr-modal" id="qrModal" onclick="closeQR(event)">
        <div class="qr-modal-content" onclick="event.stopPropagation()">
            <h3 id="qrTitle">QR Code</h3>
            <div id="qrCode"></div>
            <p class="text-muted mt-2" id="qrLink"></p>
            <button class="btn btn-raffle mt-3" onclick="closeQR()">ปิด</button>
        </div>
    </div>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>

    <script>
        const RAFFLE_KEY = '<?= $raffle_key ?>';
        const BASE_URL = window.location.origin + window.location.pathname.replace('settings.php', '');
        let isLocked = <?= $raffleInfo['is_locked'] ? 'true' : 'false' ?>;

        // Set links
        document.getElementById('linkMain').value = BASE_URL + 'index.php?share=' + RAFFLE_KEY;
        document.getElementById('linkVIP').value = BASE_URL + 'button.php?share=' + RAFFLE_KEY;
        document.getElementById('linkRemote').value = BASE_URL + 'remote.php?share=' + RAFFLE_KEY;

        // Load settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            updateLockUI();

            // Sync range and number input
            const range = document.getElementById('fontSizeRange');
            const number = document.getElementById('fontSize');
            range.addEventListener('input', () => number.value = range.value);
            number.addEventListener('input', () => range.value = number.value);

            // Sync entry font size range and number input
            const entryRange = document.getElementById('entryFontSizeRange');
            const entryNumber = document.getElementById('entryFontSize');
            entryRange.addEventListener('input', () => entryNumber.value = entryRange.value);
            entryNumber.addEventListener('input', () => entryRange.value = entryNumber.value);

            // Sync winner font size range and number input
            const winnerRange = document.getElementById('winnerFontSizeRange');
            const winnerNumber = document.getElementById('winnerFontSize');
            winnerRange.addEventListener('input', () => winnerNumber.value = winnerRange.value);
            winnerNumber.addEventListener('input', () => winnerRange.value = winnerNumber.value);

            // Radio card selection
            document.querySelectorAll('.radio-card').forEach(card => {
                card.addEventListener('click', function() {
                    const name = this.querySelector('input').name;
                    document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                        r.closest('.radio-card').classList.remove('selected');
                    });
                    this.classList.add('selected');
                    this.querySelector('input').checked = true;
                });
            });

            // Toggle prize categories section
            document.getElementById('enablePrizeCategories').addEventListener('change', function() {
                document.getElementById('prizeCategoriesSection').style.display = this.checked ? 'block' : 'none';
            });

            // Load names on page load
            loadNames();

            // Update names count when typing
            document.getElementById('namesList').addEventListener('input', updateNamesCount);
        });

        function loadSettings() {
            fetch(`./actions/get_settings.php?raffle_key=${RAFFLE_KEY}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const s = data.settings;
                        
                        document.getElementById('eventTitle').value = s.event_title || 'Raffle Game';
                        document.getElementById('fontSize').value = s.result_font_size || 72;
                        document.getElementById('fontSizeRange').value = s.result_font_size || 72;
                        document.getElementById('resultFontColor').value = s.result_font_color || '#ffffff';
                        document.getElementById('entryFontSize').value = s.entry_font_size || 16;
                        document.getElementById('entryFontSizeRange').value = s.entry_font_size || 16;
                        document.getElementById('winnerFontSize').value = s.winner_font_size || 16;
                        document.getElementById('winnerFontSizeRange').value = s.winner_font_size || 16;
                        document.getElementById('spinDuration').value = s.spin_duration || 5;
                        document.getElementById('showMainButton').checked = s.show_main_button == 1;
                        document.getElementById('enableConfetti').checked = s.enable_confetti == 1;
                        document.getElementById('enableSound').checked = s.enable_sound == 1;
                        document.getElementById('enablePrizeCategories').checked = s.enable_prize_categories == 1;

                        // Template
                        if (s.animation_template === 'wheel') {
                            document.querySelector('input[value="wheel"]').checked = true;
                            document.getElementById('templateWheel').classList.add('selected');
                        } else {
                            document.querySelector('input[value="text_roll"]').checked = true;
                            document.getElementById('templateTextRoll').classList.add('selected');
                        }

                        // Theme
                        if (s.theme === 'light') {
                            document.querySelector('input[value="light"]').checked = true;
                            document.getElementById('themeLight').classList.add('selected');
                            document.body.setAttribute('data-theme', 'light');
                        } else {
                            document.querySelector('input[value="dark"]').checked = true;
                            document.getElementById('themeDark').classList.add('selected');
                        }

                        // Prize categories
                        if (s.enable_prize_categories == 1) {
                            document.getElementById('prizeCategoriesSection').style.display = 'block';
                        }

                        // Custom sounds
                        if (data.custom_sounds.spin) {
                            document.getElementById('currentSpinSound').textContent = data.custom_sounds.spin.file_name;
                        }
                        if (data.custom_sounds.winner) {
                            document.getElementById('currentWinnerSound').textContent = data.custom_sounds.winner.file_name;
                        }

                        // Prize categories text
                        if (data.prize_categories.length > 0) {
                            const lines = data.prize_categories.map(c => 
                                `${c.category_name} | ${c.quantity} | ${c.category_color}`
                            );
                            document.getElementById('prizeList').value = lines.join('\n');
                        }

                        isLocked = data.is_locked;
                        updateLockUI();
                    }
                });
        }

        function saveSettings() {
            if (isLocked) {
                Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('event_title', document.getElementById('eventTitle').value);
            formData.append('result_font_size', document.getElementById('fontSize').value);
            formData.append('result_font_color', document.getElementById('resultFontColor').value);
            formData.append('entry_font_size', document.getElementById('entryFontSize').value);
            formData.append('winner_font_size', document.getElementById('winnerFontSize').value);
            formData.append('spin_duration', document.getElementById('spinDuration').value);
            formData.append('show_main_button', document.getElementById('showMainButton').checked ? 1 : 0);
            formData.append('animation_template', document.querySelector('input[name="template"]:checked').value);
            formData.append('theme', document.querySelector('input[name="theme"]:checked').value);
            formData.append('enable_confetti', document.getElementById('enableConfetti').checked ? 1 : 0);
            formData.append('enable_sound', document.getElementById('enableSound').checked ? 1 : 0);
            formData.append('enable_prize_categories', document.getElementById('enablePrizeCategories').checked ? 1 : 0);

            // Parse prize categories
            if (document.getElementById('enablePrizeCategories').checked) {
                const lines = document.getElementById('prizeList').value.split('\n').filter(l => l.trim());
                const categories = lines.map(line => {
                    const parts = line.split('|').map(p => p.trim());
                    return {
                        name: parts[0] || 'Prize',
                        quantity: parseInt(parts[1]) || 1,
                        color: parts[2] || '#FFD700'
                    };
                });
                formData.append('prize_categories', JSON.stringify(categories));
            }

            fetch('./actions/save_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        text: 'การตั้งค่าถูกบันทึกแล้ว',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            });
        }

        function loadNames() {
            fetch(`./actions/get_entries.php?raffle_key=${RAFFLE_KEY}&filter=all`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.entries) {
                        const names = data.entries.map(e => e.name);
                        document.getElementById('namesList').value = names.join('\n');
                        updateNamesCount();
                    }
                })
                .catch(err => {
                    console.error('Error loading names:', err);
                });
        }

        function saveNames() {
            if (isLocked) {
                Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
                return;
            }

            const namesText = document.getElementById('namesList').value;
            const names = namesText.split('\n').map(n => n.trim()).filter(n => n !== '');

            Swal.fire({
                title: 'ยืนยันการบันทึก?',
                html: `จะบันทึก ${names.length} รายชื่อ<br><small class="text-muted">รายชื่อเดิมจะถูกแทนที่ทั้งหมด</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('raffle_key', RAFFLE_KEY);
                    names.forEach(name => {
                        formData.append('names[]', name);
                    });

                    Swal.fire({
                        title: 'กำลังบันทึก...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('./actions/save_entries.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.text())
                    .then(data => {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ!',
                            text: `บันทึก ${names.length} รายชื่อแล้ว`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        updateNamesCount();
                    })
                    .catch(err => {
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
                        console.error(err);
                    });
                }
            });
        }

        function updateNamesCount() {
            const namesText = document.getElementById('namesList').value;
            const names = namesText.split('\n').filter(n => n.trim() !== '');
            document.getElementById('namesCount').textContent = `${names.length} รายชื่อ`;
        }

        // Parse exclude numbers string into an array of numbers
        function parseExcludeNumbers(excludeStr) {
            if (!excludeStr || excludeStr.trim() === '') return [];
            
            const excludeSet = new Set();
            const parts = excludeStr.split(',');
            
            parts.forEach(part => {
                part = part.trim();
                if (part.includes('-')) {
                    // Range format: 10-15
                    const rangeParts = part.split('-');
                    if (rangeParts.length === 2) {
                        const rangeStart = parseInt(rangeParts[0].trim());
                        const rangeEnd = parseInt(rangeParts[1].trim());
                        if (!isNaN(rangeStart) && !isNaN(rangeEnd)) {
                            for (let i = Math.min(rangeStart, rangeEnd); i <= Math.max(rangeStart, rangeEnd); i++) {
                                excludeSet.add(i);
                            }
                        }
                    }
                } else {
                    // Single number
                    const num = parseInt(part);
                    if (!isNaN(num)) {
                        excludeSet.add(num);
                    }
                }
            });
            
            return Array.from(excludeSet);
        }

        function generateNumbers() {
            if (isLocked) {
                Swal.fire('Session ถูกล็อค', 'กรุณาปลดล็อคก่อนแก้ไข', 'warning');
                return;
            }

            const start = parseInt(document.getElementById('autoStart').value) || 1;
            const end = parseInt(document.getElementById('autoEnd').value) || 100;
            const prefix = document.getElementById('autoPrefix').value;
            const clearExisting = document.getElementById('clearExisting').checked;
            const excludeStr = document.getElementById('excludeNumbers').value;
            const excludeNumbers = parseExcludeNumbers(excludeStr);
            
            // Calculate actual count after exclusions
            let actualCount = 0;
            for (let i = start; i <= end; i++) {
                if (!excludeNumbers.includes(i)) {
                    actualCount++;
                }
            }

            if (actualCount > 10000) {
                Swal.fire('Error', 'สร้างได้สูงสุด 10,000 ฉลากต่อครั้ง', 'error');
                return;
            }

            let confirmHtml = `จะสร้างฉลาก ${prefix}${start} ถึง ${prefix}${end}`;
            if (excludeNumbers.length > 0) {
                confirmHtml += `<br><small class="text-warning">ยกเว้น ${excludeNumbers.length} ตัวเลข</small>`;
            }
            confirmHtml += `<br>(${actualCount} ฉลาก)`;

            Swal.fire({
                title: 'ยืนยันการสร้างฉลาก?',
                html: confirmHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'สร้างเลย',
                cancelButtonText: 'ยกเลิก'
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('raffle_key', RAFFLE_KEY);
                    formData.append('start', start);
                    formData.append('end', end);
                    formData.append('prefix', prefix);
                    formData.append('clear_existing', clearExisting ? '1' : '0');
                    formData.append('exclude_numbers', JSON.stringify(excludeNumbers));

                    Swal.fire({
                        title: 'กำลังสร้าง...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch('./actions/generate_numbers.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'สร้างสำเร็จ!',
                                text: `สร้าง ${data.generated} ฉลากแล้ว`,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                        }
                    });
                }
            });
        }

        function uploadSound(type, input) {
            if (!input.files.length) return;

            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append('sound_type', type);
            formData.append('sound_file', input.files[0]);

            Swal.fire({
                title: 'กำลังอัพโหลด...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('./actions/upload_sound.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'อัพโหลดสำเร็จ!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    if (type === 'spin') {
                        document.getElementById('currentSpinSound').textContent = data.sound.filename;
                    } else {
                        document.getElementById('currentWinnerSound').textContent = data.sound.filename;
                    }
                } else {
                    Swal.fire('Error', data.error || 'เกิดข้อผิดพลาด', 'error');
                }
            });

            input.value = '';
        }

        function toggleLock() {
            const formData = new FormData();
            formData.append('raffle_key', RAFFLE_KEY);
            formData.append(isLocked ? 'unlock' : 'lock', '1');

            fetch('./actions/save_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    isLocked = !isLocked;
                    updateLockUI();
                    Swal.fire({
                        icon: 'success',
                        title: isLocked ? 'ล็อคแล้ว' : 'ปลดล็อคแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }

        function updateLockUI() {
            const btn = document.getElementById('lockBtn');
            const warning = document.getElementById('lockWarning');
            
            if (isLocked) {
                btn.innerHTML = '<i class="fa-solid fa-unlock me-1"></i>ปลดล็อค';
                btn.classList.remove('btn-raffle-danger');
                btn.classList.add('btn-raffle-success');
                warning.style.display = 'flex';
            } else {
                btn.innerHTML = '<i class="fa-solid fa-lock me-1"></i>ล็อค';
                btn.classList.remove('btn-raffle-success');
                btn.classList.add('btn-raffle-danger');
                warning.style.display = 'none';
            }
        }

        function copyLink(inputId) {
            const input = document.getElementById(inputId);
            const textToCopy = input.value;

            // ใช้ Clipboard API ถ้าสามารถใช้ได้
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showCopySuccess();
                }).catch(() => {
                    fallbackCopy(textToCopy);
                });
            } else {
                // Fallback สำหรับ HTTP หรือ browser ที่ไม่รองรับ
                fallbackCopy(textToCopy);
            }
        }

        function fallbackCopy(text) {
            // สร้าง textarea ชั่วคราวเพื่อ copy
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.style.top = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถคัดลอกได้',
                    text: 'กรุณาคัดลอกด้วยตนเอง (Ctrl+C)',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            document.body.removeChild(textArea);
        }

        function showCopySuccess() {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกแล้ว!',
                timer: 1000,
                showConfirmButton: false
            });
        }

        function openLink(inputId) {
            const input = document.getElementById(inputId);
            window.open(input.value, '_blank');
        }

        function showQR(title, inputId) {
            const url = document.getElementById(inputId).value;
            const qr = qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            
            document.getElementById('qrTitle').textContent = title;
            document.getElementById('qrCode').innerHTML = qr.createImgTag(6);
            document.getElementById('qrLink').textContent = url;
            document.getElementById('qrModal').classList.add('show');
        }

        function closeQR(e) {
            if (!e || e.target === document.getElementById('qrModal')) {
                document.getElementById('qrModal').classList.remove('show');
            }
        }

        // Theme change listener
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.body.setAttribute('data-theme', this.value);
            });
        });
    </script>
</body>
</html>
