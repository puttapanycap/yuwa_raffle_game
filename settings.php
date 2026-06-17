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
    <!-- Critical inline CSS (above-the-fold) -->
    <style>
      .raffle-app[data-theme="aurora"] { background: #050816; }
      .settings-container { animation: fadeInUp 0.4s var(--ease-out-soft); }
      @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="raffle-app" data-theme="aurora">
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
            <span class="badge p-2 rounded-pill">
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
                        <span class="badge" id="namesCount">0 รายชื่อ</span>
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
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js" defer></script>
    <script src="./js/raffle-settings.js" defer></script>

    <script>
      // Bootstrap data for deferred settings module
      window.SETTINGS_CONFIG = {
        key: <?= json_encode($raffle_key) ?>,
        baseUrl: window.location.origin + window.location.pathname.replace('settings.php', ''),
        initialLocked: <?= $raffleInfo['is_locked'] ? 'true' : 'false' ?>
      };
    </script>
</body>
</html>
