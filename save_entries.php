<?php
// Database configuration
$host = 'localhost';
$db = 'yuwa-raffle-game';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check POST data
    if (!isset($_POST['raffle_key']) || !isset($_POST['names'])) {
        http_response_code(400);
        echo "Invalid request.";
        exit;
    }

    $raffle_key = $_POST['raffle_key'];
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
