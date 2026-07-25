<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$pdo = new PDO('mysql:host=localhost;dbname=YOUR_DB_NAME;charset=utf8mb4', 'YOUR_DB_USER', 'YOUR_DB_PASS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

try {
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, created_at) VALUES (:email, NOW())
                            ON DUPLICATE KEY UPDATE created_at = created_at");
    $stmt->execute(['email' => $email]);
    echo json_encode(['success' => true, 'message' => "You're subscribed! We'll email you monthly compliance updates."]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}