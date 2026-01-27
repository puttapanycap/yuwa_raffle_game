<?php
/**
 * Set Raffle State API
 * Updates state for real-time sync (spinning, revealing, etc.)
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_POST['raffle_key']) || !isset($_POST['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key and action are required.']);
        exit;
    }

    $raffle_key = $_POST['raffle_key'];
    $action = $_POST['action'];
    $winner = isset($_POST['winner']) && $_POST['winner'] !== '' ? $_POST['winner'] : null;
    $winner_id = isset($_POST['winner_id']) && $_POST['winner_id'] !== '' ? (int)$_POST['winner_id'] : null;
    $prize_id = isset($_POST['prize_id']) && $_POST['prize_id'] !== '' ? (int)$_POST['prize_id'] : null;
    $triggered_by = isset($_POST['triggered_by']) && $_POST['triggered_by'] !== '' ? $_POST['triggered_by'] : 'main';

    // Validate action
    $validActions = ['idle', 'spinning', 'revealing', 'confirmed'];
    if (!in_array($action, $validActions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Must be one of: ' . implode(', ', $validActions)]);
        exit;
    }

    // Validate raffle key and check lock status
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Update or insert state
    $stmt = $pdo->prepare("
        INSERT INTO raffle_state (raffle_key, current_action, current_winner, current_winner_id, current_prize_id, triggered_by, last_updated)
        VALUES (:raffle_key, :action, :winner, :winner_id, :prize_id, :triggered_by, NOW())
        ON DUPLICATE KEY UPDATE 
            current_action = VALUES(current_action),
            current_winner = VALUES(current_winner),
            current_winner_id = VALUES(current_winner_id),
            current_prize_id = VALUES(current_prize_id),
            triggered_by = VALUES(triggered_by),
            last_updated = NOW()
    ");
    
    $stmt->execute([
        'raffle_key' => $raffle_key,
        'action' => $action,
        'winner' => $winner,
        'winner_id' => $winner_id,
        'prize_id' => $prize_id,
        'triggered_by' => $triggered_by
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'State updated successfully.',
        'state' => [
            'action' => $action,
            'winner' => $winner,
            'winner_id' => $winner_id,
            'prize_id' => $prize_id,
            'triggered_by' => $triggered_by
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
