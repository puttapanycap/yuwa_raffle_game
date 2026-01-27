<?php
/**
 * Generate Numbers API
 * Auto-generates numbered entries for raffle
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_POST['raffle_key']) || !isset($_POST['start']) || !isset($_POST['end'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key, start, and end are required.']);
        exit;
    }

    $raffle_key = $_POST['raffle_key'];
    $start = (int)$_POST['start'];
    $end = (int)$_POST['end'];
    $prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
    $clearExisting = isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1';
    
    // Parse excluded numbers
    $excludeNumbers = [];
    if (isset($_POST['exclude_numbers']) && !empty($_POST['exclude_numbers'])) {
        $excludeNumbers = json_decode($_POST['exclude_numbers'], true);
        if (!is_array($excludeNumbers)) {
            $excludeNumbers = [];
        }
    }

    // Validate range
    if ($start < 0 || $end < $start) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid range. End must be greater than or equal to start.']);
        exit;
    }

    // Calculate actual count after exclusions
    $actualCount = 0;
    for ($i = $start; $i <= $end; $i++) {
        if (!in_array($i, $excludeNumbers)) {
            $actualCount++;
        }
    }

    // Limit to 10000 entries max
    if ($actualCount > 10000) {
        http_response_code(400);
        echo json_encode(['error' => 'Maximum 10,000 entries allowed per generation.']);
        exit;
    }

    // Validate raffle key and check lock
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }
    $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($raffleInfo['is_locked']) {
        http_response_code(403);
        echo json_encode(['error' => 'Session is locked. Cannot generate entries.']);
        exit;
    }

    // Clear existing entries if requested
    if ($clearExisting) {
        $stmt = $pdo->prepare("DELETE FROM raffle_entries WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);
    }

    // Generate entries (skipping excluded numbers)
    $stmt = $pdo->prepare("INSERT INTO raffle_entries (raffle_key, name) VALUES (:raffle_key, :name)");
    $generated = 0;
    $excluded = 0;

    for ($i = $start; $i <= $end; $i++) {
        if (in_array($i, $excludeNumbers)) {
            $excluded++;
            continue;
        }
        $name = $prefix . $i;
        $stmt->execute(['raffle_key' => $raffle_key, 'name' => $name]);
        $generated++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Generated $generated entries.",
        'generated' => $generated,
        'excluded' => $excluded,
        'range' => ['start' => $start, 'end' => $end],
        'prefix' => $prefix
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
