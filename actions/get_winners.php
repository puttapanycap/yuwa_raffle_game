<?php
/**
 * Get Winners (Logs) API
 * Returns all winners/logs for a raffle session
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
    $includeUndone = isset($_GET['include_undone']) && $_GET['include_undone'] === '1';

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Get logs with optional prize category join
    $undoneClause = $includeUndone ? "" : "AND l.is_undone = 0";
    $stmt = $pdo->prepare("
        SELECT 
            l.id,
            l.log_message as winner_name,
            l.entry_id,
            l.prize_category_id,
            l.is_undone,
            l.created_at,
            p.category_name as prize_name,
            p.category_color as prize_color
        FROM raffle_logs l
        LEFT JOIN prize_categories p ON l.prize_category_id = p.id
        WHERE l.raffle_key = :raffle_key $undoneClause
        ORDER BY l.created_at DESC
    ");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by prize category
    $byCategory = [];
    foreach ($logs as $log) {
        $catId = $log['prize_category_id'] ?? 'no_category';
        if (!isset($byCategory[$catId])) {
            $byCategory[$catId] = [
                'category_name' => $log['prize_name'] ?? 'ไม่ระบุประเภท',
                'category_color' => $log['prize_color'] ?? '#666666',
                'winners' => []
            ];
        }
        $byCategory[$catId]['winners'][] = $log;
    }

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'by_category' => $byCategory,
        'total' => count($logs)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
