<?php
/**
 * Get Raffle State API
 * Returns current state for real-time sync.
 *
 * Performance: merged into a single query, supports ETag/304.
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';
require_once _WEBROOT_PATH_ . 'helpers/cache.php';

try {
    if (!isset($_GET['raffle_key'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key is required.']);
        exit;
    }

    $raffle_key = $_GET['raffle_key'];

    // === ETag support (skip DB hit if client has fresh copy) ===
    $etag_key = 'state:' . $raffle_key;
    $cached = raffle_cache_get($etag_key);
    if ($cached && isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $cached['hash'] . '"') {
        http_response_code(304);
        header('ETag: "' . $cached['hash'] . '"');
        exit;
    }

    // === Validate raffle key (1 query) ===
    $stmt = $pdo->prepare("SELECT id, event_title, is_locked FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    $raffleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$raffleInfo) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    // === Single query: state + counts + current prize (JOIN) ===
    $stmt = $pdo->prepare("
        SELECT
            s.current_action        AS action,
            s.current_winner        AS winner,
            s.current_winner_id     AS winner_id,
            s.current_prize_id      AS prize_id,
            s.triggered_by,
            s.last_updated,
            p.category_name         AS prize_name,
            p.category_color        AS prize_color,
            (SELECT COUNT(*) FROM raffle_entries WHERE raffle_key = :rk1 AND is_winner = 0) AS remaining,
            (SELECT COUNT(*) FROM raffle_entries WHERE raffle_key = :rk2) AS total
        FROM raffle_state s
        LEFT JOIN prize_categories p ON p.id = s.current_prize_id
        WHERE s.raffle_key = :rk3
    ");
    $stmt->execute([
        'rk1' => $raffle_key,
        'rk2' => $raffle_key,
        'rk3' => $raffle_key,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Initial state — auto-create
        $ins = $pdo->prepare("INSERT INTO raffle_state (raffle_key, current_action) VALUES (:rk, 'idle')");
        $ins->execute(['rk' => $raffle_key]);
        $row = [
            'action'       => 'idle',
            'winner'       => null,
            'winner_id'    => null,
            'prize_id'     => null,
            'triggered_by' => 'main',
            'last_updated' => date('Y-m-d H:i:s'),
            'prize_name'   => null,
            'prize_color'  => null,
            'remaining'    => 0,
            'total'        => 0,
        ];
    }

    $prize = ($row['prize_id'])
        ? ['category_name' => $row['prize_name'], 'category_color' => $row['prize_color']]
        : null;

    $payload = [
        'success' => true,
        'state' => [
            'action'       => $row['action'],
            'winner'       => $row['winner'],
            'winner_id'    => $row['winner_id'],
            'prize_id'     => $row['prize_id'],
            'prize'        => $prize,
            'triggered_by' => $row['triggered_by'],
            'last_updated' => $row['last_updated'],
        ],
        'entries' => [
            'total'     => (int)$row['total'],
            'remaining' => (int)$row['remaining'],
        ],
        'is_locked'    => (bool)$raffleInfo['is_locked'],
        'event_title'  => $raffleInfo['event_title'],
    ];

    // === ETag: short cache (2s) to allow rapid polling but skip identical responses ===
    $hash = substr(md5(json_encode($payload)), 0, 16);
    header('ETag: "' . $hash . '"');
    raffle_cache_set($etag_key, ['hash' => $hash, 'payload' => $payload], 2);

    echo json_encode($payload);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
