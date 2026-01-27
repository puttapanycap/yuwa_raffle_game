<?php
/**
 * Get Raffle State API
 * Returns current state for real-time sync
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_GET['raffle_key'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key is required.']);
        exit;
    }

    $raffle_key = $_GET['raffle_key'];

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }
    $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current state (create if not exists)
    $stmt = $pdo->prepare("SELECT * FROM raffle_state WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    
    if ($stmt->rowCount() === 0) {
        // Create initial state
        $stmt = $pdo->prepare("INSERT INTO raffle_state (raffle_key, current_action) VALUES (:raffle_key, 'idle')");
        $stmt->execute(['raffle_key' => $raffle_key]);
        
        $state = [
            'current_action' => 'idle',
            'current_prize_id' => null,
            'current_winner' => null,
            'current_winner_id' => null,
            'triggered_by' => 'main',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    } else {
        $state = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get entry count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_winner = 0 THEN 1 ELSE 0 END) as remaining FROM raffle_entries WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $entryCounts = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get current prize category if set
    $currentPrize = null;
    if ($state['current_prize_id']) {
        $stmt = $pdo->prepare("SELECT * FROM prize_categories WHERE id = :id");
        $stmt->execute(['id' => $state['current_prize_id']]);
        $currentPrize = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'state' => [
            'action' => $state['current_action'],
            'winner' => $state['current_winner'],
            'winner_id' => $state['current_winner_id'],
            'prize_id' => $state['current_prize_id'],
            'prize' => $currentPrize,
            'triggered_by' => $state['triggered_by'],
            'last_updated' => $state['last_updated']
        ],
        'entries' => [
            'total' => (int)$entryCounts['total'],
            'remaining' => (int)$entryCounts['remaining']
        ],
        'is_locked' => (bool)$raffleInfo['is_locked'],
        'event_title' => $raffleInfo['event_title']
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
