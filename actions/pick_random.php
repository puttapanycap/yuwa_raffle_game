<?php
/**
 * Pick Random Entry API
 * Picks a random entry from the pool (not yet a winner)
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {
    if (!isset($_GET['raffle_key'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request. raffle_key is required.']);
        exit;
    }

    $raffle_key = $_GET['raffle_key'];
    $prize_id = isset($_GET['prize_id']) ? (int)$_GET['prize_id'] : null;

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invalid raffle key.']);
        exit;
    }

    // Get random entry that hasn't won yet
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM raffle_entries 
        WHERE raffle_key = :raffle_key AND is_winner = 0 
        ORDER BY RAND() 
        LIMIT 1
    ");
    $stmt->execute(['raffle_key' => $raffle_key]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'No entries available.', 'empty' => true]);
        exit;
    }

    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all remaining entries for animation
    $stmt = $pdo->prepare("SELECT id, name FROM raffle_entries WHERE raffle_key = :raffle_key AND is_winner = 0");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $allEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get prize info if provided and check remaining quantity
    $prizeInfo = null;
    if ($prize_id) {
        $stmt = $pdo->prepare("SELECT * FROM prize_categories WHERE id = :id");
        $stmt->execute(['id' => $prize_id]);
        $prizeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if prize category has remaining quantity
        if ($prizeInfo) {
            $remaining = $prizeInfo['quantity'] - $prizeInfo['winners_count'];
            if ($remaining <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'รางวัล "' . $prizeInfo['category_name'] . '" หมดแล้ว', 
                    'prize_exhausted' => true
                ]);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'winner' => [
            'id' => $entry['id'],
            'name' => $entry['name']
        ],
        'prize' => $prizeInfo,
        'entries' => $allEntries,
        'remaining_count' => count($allEntries)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
