<?php
// Database configuration
$host = 'localhost';
$db = 'yuwa-raffle-game';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if raffle_key is valid
    if (!isset($_POST['raffle_key']) || !isset($_POST['names'])) {
        http_response_code(400);
        die("Invalid request");
    }

    $raffle_key = $_POST['raffle_key'];
    $names = $_POST['names'];

    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        die("Invalid raffle key");
    }

    // Insert names into the database
    $stmt = $pdo->prepare("INSERT INTO raffle_entries (raffle_key, name) VALUES (:raffle_key, :name)");
    foreach ($names as $name) {
        $stmt->execute(['raffle_key' => $raffle_key, 'name' => $name]);
    }

    echo "บันทึกรายชื่อสำเร็จ!";
} catch (PDOException $e) {
    http_response_code(500);
    die("Database error: " . $e->getMessage());
}
?>
