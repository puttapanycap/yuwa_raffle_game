<?php
/**
 * Delete Entry Action
 * Removes a single entry from the raffle
 */
define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$raffle_key = $_POST['raffle_key'] ?? '';
$entry_id = $_POST['entry_id'] ?? '';

if (empty($raffle_key) || empty($entry_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Verify raffle key
$stmt = $pdo->prepare("SELECT raffle_key FROM raffle_keys WHERE raffle_key = :raffle_key");
$stmt->execute(['raffle_key' => $raffle_key]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid raffle key']);
    exit;
}

// Delete the entry
$stmt = $pdo->prepare("DELETE FROM raffle_entries WHERE id = :id AND raffle_key = :raffle_key");
$result = $stmt->execute([
    'id' => $entry_id,
    'raffle_key' => $raffle_key
]);

if ($result && $stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Entry deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Entry not found or already deleted']);
}
