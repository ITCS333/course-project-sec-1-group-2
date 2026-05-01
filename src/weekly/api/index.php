<?php

header("Content-Type: application/json; charset=UTF-8");

// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ================= DB CONNECTION =================

require_once __DIR__ . '/../../common/db.php';
$db = getDBConnection();

// ================= REQUEST INFO =================
$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true) ?? [];

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$weekId = $_GET['week_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;


// ==================================================
// HELPERS
// ==================================================
function sendResponse($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}


// ==================================================
// WEEKS
// ==================================================

function getAllWeeks($db)
{
    $sql = "SELECT * FROM weeks";
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    $sort = $_GET['sort'] ?? 'start_date';
    $order = strtolower($_GET['order'] ?? 'asc');

    if (!in_array($sort, ['title', 'start_date'])) $sort = 'start_date';
    if (!in_array($order, ['asc', 'desc'])) $order = 'asc';

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weeks as &$w) {
        $w['links'] = json_decode($w['links'], true) ?? [];
    }

    sendResponse(["success" => true, "data" => $weeks]);
}

function getWeekById($db, $id)
{
    if (!$id || !is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Invalid ID"], 400);
    }

    $stmt = $db->prepare("SELECT * FROM weeks WHERE id = ?");
    $stmt->execute([$id]);

    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
        sendResponse(["success" => false, "message" => "Not found"], 404);
    }

    $week['links'] = json_decode($week['links'], true) ?? [];

    sendResponse(["success" => true, "data" => $week]);
}

function createWeek($db, $data)
{
    if (empty($data['title']) || empty($data['start_date'])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $title = sanitizeInput($data['title']);
    $start = sanitizeInput($data['start_date']);
    $desc = sanitizeInput($data['description'] ?? '');

    if (!validateDate($start)) {
        sendResponse(["success" => false, "message" => "Invalid date"], 400);
    }

    $links = json_encode($data['links'] ?? []);

    $stmt = $db->prepare("INSERT INTO weeks (title,start_date,description,links) VALUES (?,?,?,?)");
    $stmt->execute([$title, $start, $desc, $links]);

    sendResponse([
        "success" => true,
        "message" => "Created",
        "id" => $db->lastInsertId()
    ], 201);
}

function updateWeek($db, $data)
{
    if (empty($data['id'])) {
        sendResponse(["success" => false, "message" => "ID required"], 400);
    }

    $id = $data['id'];

    $stmt = $db->prepare("SELECT * FROM weeks WHERE id=?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Not found"], 404);
    }

    $fields = [];
    $values = [];

    if (!empty($data['title'])) {
        $fields[] = "title=?";
        $values[] = sanitizeInput($data['title']);
    }

    if (!empty($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendResponse(["success" => false, "message" => "Invalid date"], 400);
        }
        $fields[] = "start_date=?";
        $values[] = $data['start_date'];
    }

    if (isset($data['description'])) {
        $fields[] = "description=?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['links'])) {
        $fields[] = "links=?";
        $values[] = json_encode($data['links']);
    }

    if (empty($fields)) {
        sendResponse(["success" => false, "message" => "Nothing to update"], 400);
    }

    $values[] = $id;

    $sql = "UPDATE weeks SET " . implode(",", $fields) . " WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    sendResponse(["success" => true, "message" => "Updated"]);
}

function deleteWeek($db, $id)
{
    if (!$id || !is_numeric($id)) {
        sendResponse(["success" => false, "message" => "Invalid ID"], 400);
    }

    $check = $db->prepare("SELECT id FROM weeks WHERE id=?");
    $check->execute([$id]);

    if (!$check->fetch()) {
        sendResponse(["success" => false, "message" => "Not found"], 404);
    }

    $stmt = $db->prepare("DELETE FROM weeks WHERE id=?");
    $stmt->execute([$id]);

    sendResponse(["success" => true, "message" => "Deleted"]);
}


// ==================================================
// COMMENTS
// ==================================================

function getCommentsByWeek($db, $weekId)
{
    if (!$weekId) sendResponse(["success" => false], 400);

    $stmt = $db->prepare("SELECT * FROM comments_week WHERE week_id=? ORDER BY created_at ASC");
    $stmt->execute([$weekId]);

    sendResponse(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function createComment($db, $data)
{
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $stmt = $db->prepare("SELECT id FROM weeks WHERE id=?");
    $stmt->execute([$data['week_id']]);

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Week not found"], 404);
    }

    $stmt = $db->prepare("INSERT INTO comments_week (week_id,author,text) VALUES (?,?,?)");
    $stmt->execute([$data['week_id'], sanitizeInput($data['author']), sanitizeInput($data['text'])]);

    sendResponse([
        "success" => true,
        "id" => $db->lastInsertId()
    ], 201);
}

function deleteComment($db, $commentId)
{
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(["success" => false], 400);
    }

    $check = $db->prepare("SELECT id FROM comments_week WHERE id=?");
    $check->execute([$commentId]);

    if (!$check->fetch()) {
        sendResponse(["success" => false, "message" => "Not found"], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments_week WHERE id=?");
    $stmt->execute([$commentId]);

    sendResponse(["success" => true]);
}


// ==================================================
// ROUTER
// ==================================================

try {

    if ($method === 'GET') {

        if ($action === 'comments') {
            getCommentsByWeek($db, $weekId);
        } elseif ($id) {
            getWeekById($db, $id);
        } else {
            getAllWeeks($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createWeek($db, $data);
        }

    } elseif ($method === 'PUT') {
        updateWeek($db, $data);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);
        } else {
            deleteWeek($db, $id);
        }

    } else {
        sendResponse(["success" => false, "message" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    sendResponse(["success" => false, "message" => "Server error"], 500);
}
