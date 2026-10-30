<?php
/**
 * Yuwa Raffle — Settings ("game options" menu)
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
$eventTitle = $raffleInfo['event_title'] ?? 'Raffle Game';
$keyAttr = htmlspecialchars($raffle_key, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <?php require_once _WEBROOT_PATH_ . 'components/head.php' ?>
    <title>ตั้งค่า | <?= htmlspecialchars($eventTitle) ?></title>
</head>
<body class="raffle-app" data-theme="dark"
      data-raffle-key="<?= $keyAttr ?>" data-raffle-title="<?= htmlspecialchars($eventTitle, ENT_QUOTES) ?>">
    <?php require _WEBROOT_PATH_ . 'components/game-bg.php' ?>
    <a class="skip-link" href="#settingsMain">ข้ามไปที่การตั้งค่า</a>

    <header class="hud">
        <div class="hud-brand">
            <a href="./index.php?share=<?= $keyAttr ?>" class="btn-raffle-ghost btn-icon" aria-label="กลับไปจอหลัก" title="กลับไปจอหลัก">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>
            <h1 class="hud-title"><i class="fa-solid fa-gear" aria-hidden="true"></i>ตั้งค่าเกม</h1>
        </div>
        <div class="hud-actions">
            <span class="badge-pill"><span class="status-dot online" aria-hidden="true"></span>เซสชันพร้อมใช้งาน</span>
            <button type="button" class="btn-raffle-gold btn-icon" data-pwa-install hidden aria-label="ติดตั้งแอป" title="ติดตั้งแอป">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <main class="page-wrap is-wide" id="settingsMain">
        <nav class="settings-nav" aria-label="หมวดการตั้งค่า">
            <a href="#secGeneral"><i class="fa-solid fa-sliders" aria-hidden="true"></i>ทั่วไป</a>
            <a href="#secDisplay"><i class="fa-solid fa-display" aria-hidden="true"></i>การแสดงผล</a>
            <a href="#secSound"><i class="fa-solid fa-volume-high" aria-hidden="true"></i>เสียง</a>
            <a href="#secNames"><i class="fa-solid fa-users" aria-hidden="true"></i>รายชื่อ</a>
            <a href="#secGenerate"><i class="fa-solid fa-list-ol" aria-hidden="true"></i>ฉลากตัวเลข</a>
            <a href="#secPrizes"><i class="fa-solid fa-trophy" aria-hidden="true"></i>รางวัล</a>
            <a href="#secLock"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>ล็อค</a>
            <a href="#secLinks"><i class="fa-solid fa-link" aria-hidden="true"></i>ลิงก์แชร์</a>
        </nav>

        <div class="lock-warning" id="lockWarning" style="display: none;" role="alert">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <div>
                <strong>เซสชันถูกล็อค</strong>
                <p class="mb-0 small">ไม่สามารถแก้ไขการตั้งค่าได้ กรุณาปลดล็อคก่อน</p>
            </div>
            <button type="button" class="btn-raffle-danger ms-auto" onclick="toggleLock()">
                <i class="fa-solid fa-unlock" aria-hidden="true"></i>ปลดล็อค
            </button>
        </div>

        <div class="settings-grid">
            <!-- General -->
            <section class="game-panel" id="secGeneral" aria-labelledby="hGeneral">
                <header class="panel-head">
                    <h2 id="hGeneral"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>ตั้งค่าทั่วไป</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row is-stack">
                        <label class="setting-label" for="eventTitle">
                            <strong>ชื่องาน</strong>
                            <small>แสดงบนป้ายด้านบนของจอหลัก</small>
                        </label>
                        <input type="text" class="form-control-raffle" id="eventTitle"
                               value="<?= htmlspecialchars($eventTitle) ?>" placeholder="เช่น งานเลี้ยงปีใหม่ 2570">
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="fontSize">
                            <strong>ขนาดตัวอักษรผลการสุ่ม</strong>
                            <small>ชื่อผู้โชคดีบนเวที (px)</small>
                        </label>
                        <div class="setting-control">
                            <input type="range" class="form-range" id="fontSizeRange" min="36" max="200" value="72" aria-label="ขนาดตัวอักษรผลการสุ่ม (แถบเลื่อน)">
                            <input type="number" class="form-control-raffle input-xs" id="fontSize" value="72" min="36" max="200" inputmode="numeric">
                        </div>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="resultFontColor">
                            <strong>สีตัวอักษรผลการสุ่ม</strong>
                            <small>แนะนำสีสว่าง เพราะจอเวทีเป็นพื้นเข้ม</small>
                        </label>
                        <input type="color" class="form-control-color" id="resultFontColor" value="#ffffff">
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="entryFontSize">
                            <strong>ขนาดตัวอักษรรายชื่อผู้เล่น</strong>
                            <small>รายชื่อด้านซ้ายของจอหลัก (px)</small>
                        </label>
                        <div class="setting-control">
                            <input type="range" class="form-range" id="entryFontSizeRange" min="12" max="48" value="16" aria-label="ขนาดตัวอักษรรายชื่อผู้เล่น (แถบเลื่อน)">
                            <input type="number" class="form-control-raffle input-xs" id="entryFontSize" value="16" min="12" max="48" inputmode="numeric">
                        </div>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="winnerFontSize">
                            <strong>ขนาดตัวอักษรผู้โชคดี</strong>
                            <small>รายการผู้โชคดีด้านขวาของจอหลัก (px)</small>
                        </label>
                        <div class="setting-control">
                            <input type="range" class="form-range" id="winnerFontSizeRange" min="12" max="48" value="16" aria-label="ขนาดตัวอักษรผู้โชคดี (แถบเลื่อน)">
                            <input type="number" class="form-control-raffle input-xs" id="winnerFontSize" value="16" min="12" max="48" inputmode="numeric">
                        </div>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="spinDuration">
                            <strong>เวลาสุ่มต่อครั้ง (วินาที)</strong>
                            <small>ระยะเวลาแอนิเมชันก่อนประกาศผล</small>
                        </label>
                        <input type="number" class="form-control-raffle input-xs" id="spinDuration" value="5" min="1" max="30" inputmode="numeric">
                    </div>
                </div>
            </section>

            <!-- Display -->
            <section class="game-panel" id="secDisplay" aria-labelledby="hDisplay">
                <header class="panel-head is-sky">
                    <h2 id="hDisplay"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-display"></i></span>การแสดงผล</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row">
                        <label class="setting-label" for="showMainButton">
                            <strong>แสดงปุ่มสุ่มบนจอหลัก</strong>
                            <small>ปิดไว้ถ้าใช้รีโมตหรือปุ่ม VIP สุ่มแทน</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="showMainButton">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="setting-row is-stack">
                        <div class="setting-label" id="lblTemplate">
                            <strong>รูปแบบแอนิเมชัน</strong>
                            <small>ลักษณะการสุ่มบนเวที</small>
                        </div>
                        <div class="radio-group" role="radiogroup" aria-labelledby="lblTemplate">
                            <label class="radio-card" id="templateTextRoll">
                                <input type="radio" name="template" value="text_roll" checked>
                                <i class="fa-solid fa-text-height" aria-hidden="true"></i>ตัวหนังสือวิ่ง
                            </label>
                            <label class="radio-card" id="templateWheel">
                                <input type="radio" name="template" value="wheel">
                                <i class="fa-solid fa-circle-notch" aria-hidden="true"></i>วงล้อ
                            </label>
                        </div>
                    </div>

                    <div class="setting-row is-stack">
                        <div class="setting-label" id="lblTheme">
                            <strong>ธีม</strong>
                            <small>กลางคืน (อาร์เคด) หรือ กลางวัน (ฟ้าสดใส)</small>
                        </div>
                        <div class="radio-group" role="radiogroup" aria-labelledby="lblTheme">
                            <label class="radio-card" id="themeDark">
                                <input type="radio" name="theme" value="dark" checked>
                                <i class="fa-solid fa-moon" aria-hidden="true"></i>กลางคืน
                            </label>
                            <label class="radio-card" id="themeLight">
                                <input type="radio" name="theme" value="light">
                                <i class="fa-solid fa-sun" aria-hidden="true"></i>กลางวัน
                            </label>
                        </div>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="enableConfetti">
                            <strong>เอฟเฟกต์พลุกระดาษ</strong>
                            <small>โปรยพลุเมื่อประกาศผู้โชคดี</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="enableConfetti" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </section>

            <!-- Sound -->
            <section class="game-panel" id="secSound" aria-labelledby="hSound">
                <header class="panel-head is-bubble">
                    <h2 id="hSound"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-volume-high"></i></span>เสียง</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row">
                        <label class="setting-label" for="enableSound">
                            <strong>เปิดเสียงเอฟเฟกต์</strong>
                            <small>เล่นเสียงตอนสุ่มและตอนประกาศผล</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="enableSound" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <strong>เสียงตอนสุ่ม</strong>
                            <small>.mp3, .wav, .ogg ไม่เกิน 5MB</small>
                        </div>
                        <div class="upload-area">
                            <span class="current-sound" id="currentSpinSound">ใช้เสียงเริ่มต้น</span>
                            <label class="btn-raffle btn-sm upload-btn">
                                <i class="fa-solid fa-upload" aria-hidden="true"></i>อัปโหลด
                                <input type="file" accept=".mp3,.wav,.ogg" onchange="uploadSound('spin', this)" aria-label="อัปโหลดเสียงตอนสุ่ม">
                            </label>
                        </div>
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <strong>เสียงตอนประกาศผล</strong>
                            <small>.mp3, .wav, .ogg ไม่เกิน 5MB</small>
                        </div>
                        <div class="upload-area">
                            <span class="current-sound" id="currentWinnerSound">ใช้เสียงเริ่มต้น</span>
                            <label class="btn-raffle btn-sm upload-btn">
                                <i class="fa-solid fa-upload" aria-hidden="true"></i>อัปโหลด
                                <input type="file" accept=".mp3,.wav,.ogg" onchange="uploadSound('winner', this)" aria-label="อัปโหลดเสียงตอนประกาศผล">
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Lock -->
            <section class="game-panel" id="secLock" aria-labelledby="hLock">
                <header class="panel-head is-cherry">
                    <h2 id="hLock"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>ล็อคเซสชัน</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row">
                        <div class="setting-label">
                            <strong>ล็อคระหว่างงาน</strong>
                            <small>กันการแก้ไขการตั้งค่าโดยไม่ได้ตั้งใจ</small>
                        </div>
                        <button type="button" class="btn-raffle-danger" id="lockBtn" onclick="toggleLock()">
                            <i class="fa-solid fa-lock me-1" aria-hidden="true"></i>ล็อค
                        </button>
                    </div>
                </div>
            </section>

            <!-- Names -->
            <section class="game-panel span-2" id="secNames" aria-labelledby="hNames">
                <header class="panel-head is-mint">
                    <h2 id="hNames"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-users"></i></span>แก้ไขรายชื่อ</h2>
                </header>
                <div class="panel-body">
                    <label class="setting-label mb-2" for="namesList">
                        <strong>รายชื่อทั้งหมด</strong>
                        <small>กรอกบรรทัดละ 1 ชื่อ — การบันทึกจะแทนที่รายชื่อเดิมทั้งหมด</small>
                    </label>
                    <textarea class="form-control-raffle textarea-prizes" id="namesList" style="min-height: 220px;"
                              placeholder="สมชาย ใจดี&#10;สมหญิง รักเรียน&#10;No.001&#10;No.002"></textarea>
                    <div class="panel-actions">
                        <span class="badge-pill" id="namesCount" aria-live="polite">0 รายชื่อ</span>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn-raffle-ghost" onclick="loadNames()">
                                <i class="fa-solid fa-rotate" aria-hidden="true"></i>โหลดใหม่
                            </button>
                            <button type="button" class="btn-raffle-gold" onclick="saveNames()">
                                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>บันทึกรายชื่อ
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Auto numbers -->
            <section class="game-panel" id="secGenerate" aria-labelledby="hGenerate">
                <header class="panel-head is-tangerine">
                    <h2 id="hGenerate"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-list-ol"></i></span>สร้างฉลากตัวเลขอัตโนมัติ</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row">
                        <label class="setting-label" for="autoPrefix">
                            <strong>ข้อความนำหน้า</strong>
                            <small>เช่น "No." หรือ "หมายเลข "</small>
                        </label>
                        <input type="text" class="form-control-raffle input-sm" id="autoPrefix" placeholder="No.">
                    </div>

                    <div class="setting-row">
                        <div class="setting-label">
                            <strong>ช่วงตัวเลข</strong>
                            <small>เลขเริ่มต้นและเลขสุดท้าย</small>
                        </div>
                        <div class="number-range-group">
                            <input type="number" class="form-control-raffle" id="autoStart" value="1" min="0" aria-label="เลขเริ่มต้น" inputmode="numeric">
                            <span class="text-muted">ถึง</span>
                            <input type="number" class="form-control-raffle" id="autoEnd" value="100" min="0" aria-label="เลขสุดท้าย" inputmode="numeric">
                        </div>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="excludeNumbers">
                            <strong>ยกเว้นตัวเลข</strong>
                            <small>คั่นด้วย , หรือใส่ช่วง เช่น 1,5,10-15,20</small>
                        </label>
                        <input type="text" class="form-control-raffle input-md" id="excludeNumbers" placeholder="1,5,10-15,20">
                    </div>

                    <div class="setting-row">
                        <label class="setting-label" for="clearExisting">
                            <strong>ล้างรายชื่อเดิม</strong>
                            <small>ลบรายชื่อเดิมก่อนสร้างฉลากใหม่</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="clearExisting" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="panel-actions" style="justify-content: flex-end;">
                        <button type="button" class="btn-raffle-gold" onclick="generateNumbers()">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>สร้างฉลาก
                        </button>
                    </div>
                </div>
            </section>

            <!-- Prize categories -->
            <section class="game-panel" id="secPrizes" aria-labelledby="hPrizes">
                <header class="panel-head is-sun">
                    <h2 id="hPrizes"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>ประเภทรางวัล</h2>
                </header>
                <div class="panel-body">
                    <div class="setting-row">
                        <label class="setting-label" for="enablePrizeCategories">
                            <strong>เปิดใช้ประเภทรางวัล</strong>
                            <small>แบ่งรางวัลเป็นหลายระดับ พร้อมจำนวนและสี</small>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="enablePrizeCategories">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div id="prizeCategoriesSection" style="display: none;">
                        <label class="setting-label mb-2" for="prizeList">
                            <strong>รายการประเภทรางวัล</strong>
                            <small>บรรทัดละ 1 ประเภท รูปแบบ: ชื่อรางวัล | จำนวน | สี</small>
                        </label>
                        <textarea class="form-control-raffle textarea-prizes" id="prizeList"
                                  placeholder="รางวัลใหญ่ | 1 | #FFD700&#10;รางวัลที่ 1 | 3 | #C0C0C0&#10;รางวัลที่ 2 | 5 | #CD7F32&#10;รางวัลปลอบใจ | 10 | #6366f1"></textarea>
                    </div>
                </div>
            </section>

            <!-- Share links -->
            <section class="game-panel span-2" id="secLinks" aria-labelledby="hLinks">
                <header class="panel-head is-sky">
                    <h2 id="hLinks"><span class="panel-head-icon" aria-hidden="true"><i class="fa-solid fa-link"></i></span>ลิงก์สำหรับแชร์</h2>
                </header>
                <div class="panel-body">
                    <?php
                    $links = [
                        ['linkMain',   'fa-tv',             'จอหลัก (Main Display)', 'หน้าเวทีสำหรับฉายขึ้นจอ'],
                        ['linkVIP',    'fa-hand-pointer',   'ปุ่ม VIP',               'ปุ่มใหญ่ให้ผู้บริหารกดสุ่ม'],
                        ['linkRemote', 'fa-gamepad-modern', 'รีโมตควบคุม',            'หน้าควบคุมสำหรับทีมงานหลังเวที'],
                    ];
                    foreach ($links as [$id, $icon, $name, $desc]): ?>
                        <div class="link-group">
                            <div class="setting-label">
                                <strong><i class="fa-solid <?= $icon ?> text-info" aria-hidden="true"></i><?= $name ?></strong>
                                <small><?= $desc ?></small>
                            </div>
                            <div class="link-box">
                                <input type="text" id="<?= $id ?>" readonly value="" aria-label="ลิงก์ <?= $name ?>">
                                <button type="button" class="btn-raffle-ghost btn-sm btn-icon" onclick="copyLink('<?= $id ?>')" aria-label="คัดลอกลิงก์ <?= $name ?>" title="คัดลอก">
                                    <i class="fa-solid fa-copy" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn-raffle-ghost btn-sm btn-icon" onclick="openLink('<?= $id ?>')" aria-label="เปิด <?= $name ?> ในแท็บใหม่" title="เปิดแท็บใหม่">
                                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn-raffle-ghost btn-sm btn-icon" onclick="showQR('<?= $name ?>', '<?= $id ?>')" aria-label="แสดง QR Code ของ <?= $name ?>" title="QR Code">
                                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="save-bar">
            <button type="button" class="btn-raffle-gold btn-raffle-lg" onclick="saveSettings()">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </main>

    <!-- QR Modal -->
    <div class="qr-modal" id="qrModal" onclick="closeQR(event)">
        <div class="qr-modal-content" role="dialog" aria-modal="true" aria-labelledby="qrTitle" onclick="event.stopPropagation()">
            <h3 id="qrTitle">QR Code</h3>
            <div id="qrCode"></div>
            <p class="text-muted mt-2" id="qrLink"></p>
            <button type="button" class="btn-raffle-gold mt-3" onclick="closeQR()">ปิด</button>
        </div>
    </div>

    <?php require_once _WEBROOT_PATH_ . 'components/footer.php' ?>
    <?php require_once _WEBROOT_PATH_ . 'components/script.php' ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js" defer></script>
    <script src="./js/raffle-settings.js" defer></script>

    <script>
      window.SETTINGS_CONFIG = {
        key: <?= json_encode($raffle_key) ?>,
        baseUrl: window.location.origin + window.location.pathname.replace('settings.php', ''),
        initialLocked: <?= !empty($raffleInfo['is_locked']) ? 'true' : 'false' ?>
      };
    </script>
</body>
</html>
