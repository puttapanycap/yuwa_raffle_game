<?php
/**
 * Upload Sound API
 * Handles custom sound file uploads
 */
header('Content-Type: application/json');

define("_WEBROOT_PATH_", "../");
require_once _WEBROOT_PATH_ . 'helpers/load_env.php';
require_once _WEBROOT_PATH_ . 'helpers/load_connection.php';

// Allowed audio formats
$allowedTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/x-wav'];
$allowedExtensions = ['mp3', 'wav', 'ogg'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

try {
    if (!isset($_POST['raffle_key']) || !isset($_POST['sound_type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request. raffle_key and sound_type are required.']);
        exit;
    }

    if (!isset($_FILES['sound_file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded.']);
        exit;
    }

    $raffle_key = $_POST['raffle_key'];
    $sound_type = $_POST['sound_type'];

    // Validate sound type
    $validSoundTypes = ['spin', 'winner', 'click'];
    if (!in_array($sound_type, $validSoundTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid sound type. Must be one of: ' . implode(', ', $validSoundTypes)]);
        exit;
    }

    // Validate raffle key
    $stmt = $pdo->prepare("SELECT * FROM raffle_keys WHERE raffle_key = :raffle_key");
    $stmt->execute(['raffle_key' => $raffle_key]);
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid raffle key.']);
        exit;
    }

    $file = $_FILES['sound_file'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload error: ' . $file['error']]);
        exit;
    }

    if ($file['size'] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large. Maximum size is 5MB.']);
        exit;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)]);
        exit;
    }

    // Create upload directory if not exists
    $uploadDir = _WEBROOT_PATH_ . 'uploads/sounds/' . $raffle_key . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $newFilename = $sound_type . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $newFilename;
    $relativePath = 'uploads/sounds/' . $raffle_key . '/' . $newFilename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file.']);
        exit;
    }

    // Deactivate old sounds of same type
    $stmt = $pdo->prepare("UPDATE custom_sounds SET is_active = 0 WHERE raffle_key = :raffle_key AND sound_type = :sound_type");
    $stmt->execute(['raffle_key' => $raffle_key, 'sound_type' => $sound_type]);

    // Save to database
    $stmt = $pdo->prepare("
        INSERT INTO custom_sounds (raffle_key, sound_type, file_name, file_path, is_active)
        VALUES (:raffle_key, :sound_type, :file_name, :file_path, 1)
    ");
    $stmt->execute([
        'raffle_key' => $raffle_key,
        'sound_type' => $sound_type,
        'file_name' => $file['name'],
        'file_path' => $relativePath
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Sound uploaded successfully.',
        'sound' => [
            'type' => $sound_type,
            'filename' => $file['name'],
            'path' => $relativePath
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
