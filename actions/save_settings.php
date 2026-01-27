<?php
/**
 * Save Settings API
 * Saves settings for a raffle session
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_POST['raffle_key'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key is required.']);
        exit;
    }

    $raffle_key = $_POST['raffle_key'];

    // Validate raffle key and check lock
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }
    $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if locked (only allow unlock action)
    if ($raffleInfo['is_locked'] && !isset($_POST['unlock'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Session is locked. Unlock first to make changes.']);
        exit;
    }

    // Handle lock/unlock
    if (isset($_POST['lock'])) {
        $stmt = $pdo->prepare("UPDATE raffle_keys SET is_locked = 1 WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);
        echo json_encode(['success' => true, 'message' => 'Session locked.']);
        exit;
    }

    if (isset($_POST['unlock'])) {
        $stmt = $pdo->prepare("UPDATE raffle_keys SET is_locked = 0 WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);
        echo json_encode(['success' => true, 'message' => 'Session unlocked.']);
        exit;
    }

    // Update event title in raffle_keys if provided
    if (isset($_POST['event_title'])) {
        $stmt = $pdo->prepare("UPDATE raffle_keys SET event_title = :title WHERE raffle_key = :raffle_key");
        $stmt->execute(['title' => $_POST['event_title'], 'raffle_key' => $raffle_key]);
    }

    // Valid setting keys
    $validKeys = [
        'event_logo', 'result_font_size', 'result_font_color', 'entry_font_size', 'winner_font_size', 'spin_duration', 'show_main_button',
        'animation_template', 'theme', 'enable_confetti', 'enable_sound',
        'sound_spin', 'sound_winner', 'enable_prize_categories',
        'auto_number_enabled', 'auto_number_prefix', 'auto_number_start', 'auto_number_end'
    ];

    // Save each setting
    $savedKeys = [];
    $stmt = $pdo->prepare("
        INSERT INTO raffle_settings (raffle_key, setting_key, setting_value)
        VALUES (:raffle_key, :setting_key, :setting_value)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");

    foreach ($validKeys as $key) {
        if (isset($_POST[$key])) {
            $stmt->execute([
                'raffle_key' => $raffle_key,
                'setting_key' => $key,
                'setting_value' => $_POST[$key]
            ]);
            $savedKeys[] = $key;
        }
    }

    // Handle prize categories if provided
    if (isset($_POST['prize_categories'])) {
        $categories = json_decode($_POST['prize_categories'], true);
        if (is_array($categories)) {
            // Delete existing categories
            $stmt = $pdo->prepare("DELETE FROM prize_categories WHERE raffle_key = :raffle_key");
            $stmt->execute(['raffle_key' => $raffle_key]);

            // Insert new categories
            $stmt = $pdo->prepare("
                INSERT INTO prize_categories (raffle_key, category_name, category_color, category_order, quantity)
                VALUES (:raffle_key, :name, :color, :order, :quantity)
            ");
            
            $order = 0;
            foreach ($categories as $cat) {
                $stmt->execute([
                    'raffle_key' => $raffle_key,
                    'name' => $cat['name'] ?? 'Prize',
                    'color' => $cat['color'] ?? '#FFD700',
                    'order' => $order++,
                    'quantity' => $cat['quantity'] ?? 1
                ]);
            }
            $savedKeys[] = 'prize_categories';
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Settings saved successfully.',
        'saved_keys' => $savedKeys
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
