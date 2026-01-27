<?php
/**
 * Get Entries API
 * Returns all entries for a raffle session
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
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, available, winners

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // Build query based on filter
    $whereClause = "";
    if ($filter === 'available') {
        $whereClause = " AND is_winner = 0";
    } elseif ($filter === 'winners') {
        $whereClause = " AND is_winner = 1";
    }

    $stmt = $pdo->prepare("
        SELECT id, name, is_winner, created_at 
        FROM raffle_entries 
        WHERE raffle_key = :raffle_key $whereClause
        ORDER BY id ASC
    ");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_winner = 0 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as winners
        FROM raffle_entries 
        WHERE raffle_key = :raffle_key
    ");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'counts' => [
            'total' => (int)$counts['total'],
            'available' => (int)$counts['available'],
            'winners' => (int)$counts['winners']
        ],
        'filter' => $filter
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
