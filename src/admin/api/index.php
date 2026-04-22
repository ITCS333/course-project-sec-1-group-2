<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../common/db.php';

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;


/* ================= GET USERS ================= */
function getUsers($db) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort   = $_GET['sort'] ?? null;
    $order  = strtolower($_GET['order'] ?? 'asc');

    // STRICT order validation
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    $orderDir = strtoupper($order);

    $query = "SELECT id, name, email, is_admin, created_at FROM users";

    if ($search) {
        $query .= " WHERE name LIKE :search OR email LIKE :search";
    }

    $allowedSort = ['name', 'email', 'is_admin'];
    if (in_array($sort, $allowedSort)) {
        $query .= " ORDER BY $sort $orderDir";
    } else {
        $query .= " ORDER BY name ASC";
    }

    $stmt = $db->prepare($query);

    if ($search) {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
}


/* ================= GET USER ================= */
function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT id, name, email, is_admin, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) sendResponse("User not found", 404);

    sendResponse($user);
}


/* ================= CREATE ================= */
function createUser($db, $data) {
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        sendResponse("Missing fields", 400);
    }

    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    if (!validateEmail($email)) sendResponse("Invalid email", 400);
    if (strlen($password) < 8) sendResponse("Password must be at least 8 characters", 400);

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) sendResponse("Email already exists", 409);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $is_admin = isset($data['is_admin']) ? (int)$data['is_admin'] : 0;
    if (!in_array($is_admin, [0,1])) $is_admin = 0;

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$name, $email, $hash, $is_admin])) {
        sendResponse(['id' => $db->lastInsertId()], 201);
    }

    sendResponse("Insert failed", 500);
}


/* ================= UPDATE ================= */
function updateUser($db, $data) {
    if (empty($data['id'])) sendResponse("ID required", 400);

    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) sendResponse("User not found", 404);

    $fields = [];
    $values = [];

    if (!empty($data['name'])) {
        $fields[] = "name=?";
        $values[] = sanitizeInput($data['name']);
    }

    if (!empty($data['email'])) {
        if (!validateEmail($data['email'])) sendResponse("Invalid email", 400);

        $check = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $check->execute([$data['email'], $data['id']]);
        if ($check->fetch()) sendResponse("Email already in use", 409);

        $fields[] = "email=?";
        $values[] = sanitizeInput($data['email']);
    }

    if (isset($data['is_admin'])) {
        $is_admin = (int)$data['is_admin'];
        if (!in_array($is_admin, [0,1])) $is_admin = 0;

        $fields[] = "is_admin=?";
        $values[] = $is_admin;
    }

    if (empty($fields)) sendResponse("No fields to update", 400);

    $values[] = $data['id'];

    $query = "UPDATE users SET " . implode(",", $fields) . " WHERE id=?";
    $stmt = $db->prepare($query);

    $stmt->execute($values);

    // ALWAYS 200 (even if no rows changed)
    sendResponse("User updated", 200);
}


/* ================= DELETE ================= */
function deleteUser($db, $id) {
    if (!$id) sendResponse("ID required", 400);

    $stmt = $db->prepare("SELECT id FROM users WHERE id=?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) sendResponse("User not found", 404);

    $del = $db->prepare("DELETE FROM users WHERE id=?");
    if ($del->execute([$id])) {
        sendResponse("User deleted");
    }

    sendResponse("Delete failed", 500);
}


/* ================= CHANGE PASSWORD ================= */
function changePassword($db, $data) {
    if (empty($data['id']) || empty($data['current_password']) || empty($data['new_password'])) {
        sendResponse("Missing fields", 400);
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse("New password too short", 400);
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
    $stmt->execute([$data['id']]);
    $user = $stmt->fetch();

    if (!$user) sendResponse("User not found", 404);

    if (!password_verify($data['current_password'], $user['password'])) {
        sendResponse("Incorrect password", 401);
    }

    $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);

    $update = $db->prepare("UPDATE users SET password=? WHERE id=?");
    if ($update->execute([$newHash, $data['id']])) {
        sendResponse("Password updated");
    }

    sendResponse("Update failed", 500);
}


/* ================= ROUTER ================= */
try {

    if ($method === 'GET') {
        $id ? getUserById($db, $id) : getUsers($db);

    } elseif ($method === 'POST') {
        $action === 'change_password'
            ? changePassword($db, $data)
            : createUser($db, $data);

    } elseif ($method === 'PUT') {
        updateUser($db, $data);

    } elseif ($method === 'DELETE') {
        deleteUser($db, $id);

    } else {
        sendResponse("Method Not Allowed", 405);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    sendResponse("Database error", 500);

} catch (Exception $e) {
    sendResponse($e->getMessage(), 500);
}


/* ================= HELPERS ================= */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);

    if ($statusCode < 400) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => $data]);
    }
    exit();
}

function validateEmail($email) {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
?>
