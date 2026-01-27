<?php
/**
 * Undo Winner API
 * Reverts the last winner, returning them to the entry pool
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
    $log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : null;

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Get the last winner log (or specific log if provided)
    if ($log_id) {
        $stmt = $pdo->prepare("SELECT * FROM raffle_logs WHERE id = :id AND raffle_key = :raffle_key AND is_undone = 0");
        $stmt->execute(['id' => $log_id, 'raffle_key' => $raffle_key]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM raffle_logs WHERE raffle_key = :raffle_key AND is_undone = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute(['raffle_key' => $raffle_key]);
    }

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'No winner to undo.']);
        exit;
    }

    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    $winnerName = $log['log_message'];
    $entryId = $log['entry_id'];

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Mark log as undone
        $stmt = $pdo->prepare("UPDATE raffle_logs SET is_undone = 1 WHERE id = :id");
        $stmt->execute(['id' => $log['id']]);

        // If we have entry_id, unmark as winner
        if ($entryId) {
            $stmt = $pdo->prepare("UPDATE raffle_entries SET is_winner = 0 WHERE id = :id");
            $stmt->execute(['id' => $entryId]);
        } else {
            // Otherwise, re-add the entry
            $stmt = $pdo->prepare("INSERT INTO raffle_entries (raffle_key, name, is_winner) VALUES (:raffle_key, :name, 0)");
            $stmt->execute(['raffle_key' => $raffle_key, 'name' => $winnerName]);
        }

        // Update prize category winner count if applicable
        if ($log['prize_category_id']) {
            $stmt = $pdo->prepare("UPDATE prize_categories SET winners_count = GREATEST(0, winners_count - 1) WHERE id = :id");
            $stmt->execute(['id' => $log['prize_category_id']]);
        }

        // Reset state to idle
        $stmt = $pdo->prepare("UPDATE raffle_state SET current_action = 'idle', current_winner = NULL, current_winner_id = NULL WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Winner '$winnerName' has been returned to the pool.",
            'undone_winner' => $winnerName,
            'log_id' => $log['id']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
