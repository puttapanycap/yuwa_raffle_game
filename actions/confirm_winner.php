<?php
/**
 * Confirm Winner API
 * Confirms the current winner and logs to database
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_POST['raffle_key']) || !isset($_POST['winner'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key and winner are required.']);
        exit;
    }

    $raffle_key = $_POST['raffle_key'];
    $winner_name = $_POST['winner'];
    $winner_id = isset($_POST['winner_id']) ? (int)$_POST['winner_id'] : null;
    $prize_id = isset($_POST['prize_id']) ? (int)$_POST['prize_id'] : null;

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Mark entry as winner if we have the ID
        if ($winner_id) {
            $stmt = $pdo->prepare("UPDATE raffle_entries SET is_winner = 1 WHERE id = :id AND raffle_key = :raffle_key");
            $stmt->execute(['id' => $winner_id, 'raffle_key' => $raffle_key]);
        } else {
            // Find and mark by name (first match)
            $stmt = $pdo->prepare("UPDATE raffle_entries SET is_winner = 1 WHERE raffle_key = :raffle_key AND name = :name AND is_winner = 0 LIMIT 1");
            $stmt->execute(['raffle_key' => $raffle_key, 'name' => $winner_name]);
            
            // Get the updated entry ID
            $stmt = $pdo->prepare("SELECT id FROM raffle_entries WHERE raffle_key = :raffle_key AND name = :name AND is_winner = 1 ORDER BY id DESC LIMIT 1");
            $stmt->execute(['raffle_key' => $raffle_key, 'name' => $winner_name]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            $winner_id = $entry ? $entry['id'] : null;
        }

        // Log the winner
        $stmt = $pdo->prepare("
            INSERT INTO raffle_logs (raffle_key, entry_id, prize_category_id, log_message, created_at)
            VALUES (:raffle_key, :entry_id, :prize_id, :message, NOW())
        ");
        $stmt->execute([
            'raffle_key' => $raffle_key,
            'entry_id' => $winner_id,
            'prize_id' => $prize_id,
            'message' => $winner_name
        ]);
        $logId = $pdo->lastInsertId();

        // Update prize category winner count if applicable
        if ($prize_id) {
            $stmt = $pdo->prepare("UPDATE prize_categories SET winners_count = winners_count + 1 WHERE id = :id");
            $stmt->execute(['id' => $prize_id]);
        }

        // Update state to confirmed
        $stmt = $pdo->prepare("
            UPDATE raffle_state 
            SET current_action = 'confirmed', current_winner = :winner, current_winner_id = :winner_id
            WHERE raffle_key = :raffle_key
        ");
        $stmt->execute([
            'raffle_key' => $raffle_key,
            'winner' => $winner_name,
            'winner_id' => $winner_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Winner confirmed and logged.',
            'winner' => [
                'name' => $winner_name,
                'id' => $winner_id,
                'log_id' => $logId,
                'prize_id' => $prize_id
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
