<?php
/**
 * Get Settings API
 * Returns all settings for a raffle session
 *
 * Performance: file cache (30s) + ETag.
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';
require_once _WEBROOT_PATH_ . 'helpers/cache.php';

// Default settings
$defaultSettings = [
    'event_title' => 'Raffle Game',
    'event_logo' => '',
    'result_font_size' => 72,
    'spin_duration' => 5,
    'show_main_button' => 1,
    'animation_template' => 'text_roll',
    'theme' => 'dark',
    'enable_confetti' => 1,
    'enable_sound' => 1,
    'sound_spin' => '',
    'sound_winner' => '',
    'enable_prize_categories' => 0,
    'auto_number_enabled' => 0,
    'auto_number_prefix' => '',
    'auto_number_start' => 1,
    'auto_number_end' => 100
];

try {
    if (!isset($_GET['raffle_key'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key is required.']);
        exit;
    }

    $raffle_key = $_GET['raffle_key'];
    $cacheKey = 'settings:' . $raffle_key;

    // === ETag short-circuit ===
    $cached = raffle_cache_get($cacheKey);
    if ($cached && isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $cached['hash'] . '"') {
        http_response_code(304);
        header('ETag: "' . $cached['hash'] . '"');
        exit;
    }
    if ($cached) {
        header('ETag: "' . $cached['hash'] . '"');
        echo $cached['payload'];
        exit;
    }

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT event_title, is_locked FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$raffleInfo) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Get all settings
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM raffle_settings WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Merge with defaults
    $settings = $defaultSettings;
    $settings['event_title'] = $raffleInfo['event_title'] ?? $defaultSettings['event_title'];

    foreach ($settingsRows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Convert numeric strings to proper types
    $numericKeys = ['result_font_size', 'spin_duration', 'show_main_button', 'enable_confetti', 'enable_sound', 'enable_prize_categories', 'auto_number_enabled', 'auto_number_start', 'auto_number_end'];
    foreach ($numericKeys as $key) {
        if (isset($settings[$key])) {
            $settings[$key] = (int)$settings[$key];
        }
    }

    // Get prize categories if enabled
    $prizeCategories = [];
    if ($settings['enable_prize_categories']) {
        $stmt = $pdo->prepare("SELECT * FROM prize_categories WHERE raffle_key = :raffle_key ORDER BY category_order ASC");
        $stmt->execute(['raffle_key' => $raffle_key]);
        $prizeCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get custom sounds
    $customSounds = [];
    $stmt = $pdo->prepare("SELECT * FROM custom_sounds WHERE raffle_key = :raffle_key AND is_active = 1");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $soundRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($soundRows as $sound) {
        $customSounds[$sound['sound_type']] = $sound;
    }

    $payload = json_encode([
        'success' => true,
        'settings' => $settings,
        'prize_categories' => $prizeCategories,
        'custom_sounds' => $customSounds,
        'is_locked' => (bool)$raffleInfo['is_locked']
    ]);

    $hash = substr(md5($payload), 0, 16);
    header('ETag: "' . $hash . '"');
    raffle_cache_set($cacheKey, ['hash' => $hash, 'payload' => $payload], 30);

    echo $payload;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
