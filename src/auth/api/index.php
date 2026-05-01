<?php
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode([
        'success' => false
    ]);
    exit;
}

try {
    $db = getDBConnection();

    $stmt = $db->prepare("
        SELECT id, name, email, password, is_admin
        FROM users
        WHERE email = :email
    ");

    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            'success' => false
        ]);
        exit;
    }

    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false
        ]);
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = $user['is_admin'];
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'is_admin' => $user['is_admin']
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    echo json_encode([
        'success' => false
    ]);
    exit;
}
?>