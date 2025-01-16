<?php
// Database configuration
define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

try {

    // Check POST data
    if (!isset($_POST['raffle_key'])) {
        http_response_code(400);
        echo "Invalid request.";
        exit;
    }

    $raffle_key = $_POST['raffle_key'];

    if (!isset($_POST['names'])) {
        $stmt = $pdo->prepare("DELETE FROM raffle_entries WHERE raffle_key = :raffle_key");
        $stmt->execute(['raffle_key' => $raffle_key]);
        echo "Deleted Entries All.";
        exit;
    }

    $names = $_POST['names'];

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo "Invalid raffle key.";
        exit;
    }

    // Remove existing entries for this raffle key
    $stmt = $pdo->prepare("DELETE FROM raffle_entries WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);

    // Insert new entries
    $stmt = $pdo->prepare("INSERT INTO raffle_entries (raffle_key, name) VALUES (:raffle_key, :name)");
    foreach ($names as $name) {
        if (trim($name) !== '') {
            $stmt->execute(['raffle_key' => $raffle_key, 'name' => $name]);
        }
    }

    echo "Entries saved successfully.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
?>
