<?php

declare(strict_types=1);

session_start();

header("Content-Type: application/json");

require_once "../../common/db.php";

$pdo = getDBConnection();

$method = $_SERVER["REQUEST_METHOD"];

$action = $_GET["action"] ?? "";

function send_json(array $data, int $status = 200): void {

    http_response_code($status);

    echo json_encode($data);

    exit;

}

function get_json_input(): array {

    $raw = file_get_contents("php://input");

    $data = json_decode($raw, true);

    if (is_array($data)) {

        return $data;

    }

    return $_POST;

}

function valid_url(string $url): bool {

    return filter_var($url, FILTER_VALIDATE_URL) !== false;

}

function resource_exists(PDO $pdo, int $id): bool {

    $stmt = $pdo->prepare("SELECT id FROM resources WHERE id = ?");

    $stmt->execute([$id]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);

}

try {

    if ($method === "GET") {

        if ($action === "comments") {

            $resourceId = isset($_GET["resource_id"]) ? (int) $_GET["resource_id"] : 0;

            if ($resourceId <= 0) {

                send_json([

                    "success" => false,

                    "message" => "resource_id is required"

                ], 400);

            }

            $stmt = $pdo->prepare("

                SELECT

                    id,

                    resource_id,

                    author,

                    text,

                    created_at

                FROM comments_resource

                WHERE resource_id = ?

                ORDER BY id DESC

            ");

            $stmt->execute([$resourceId]);

            send_json([

                "success" => true,

                "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)

            ]);

        }

        if (isset($_GET["id"])) {

            $id = (int) $_GET["id"];

            $stmt = $pdo->prepare("

                SELECT id, title, description, link, created_at

                FROM resources

                WHERE id = ?

            ");

            $stmt->execute([$id]);

            $resource = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resource) {

                send_json([

                    "success" => false,

                    "message" => "Resource not found"

                ], 404);

            }

            send_json([

                "success" => true,

                "data" => $resource

            ]);

        }

        if (isset($_GET["search"])) {

            $search = "%" . $_GET["search"] . "%";

            $stmt = $pdo->prepare("

                SELECT id, title, description, link, created_at

                FROM resources

                WHERE title LIKE ? OR description LIKE ?

                ORDER BY id DESC

            ");

            $stmt->execute([$search, $search]);

            send_json([

                "success" => true,

                "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)

            ]);

        }

        $stmt = $pdo->query("

            SELECT id, title, description, link, created_at

            FROM resources

            ORDER BY id DESC

        ");

        send_json([

            "success" => true,

            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)

        ]);

    }

    if ($method === "POST") {

        $data = get_json_input();

        if ($action === "comment") {

            $resourceId = isset($data["resource_id"]) ? (int) $data["resource_id"] : 0;

            $author = trim($data["author"] ?? "Student");

            $text = trim($data["text"] ?? $data["comment"] ?? "");

            if ($resourceId <= 0 || $text === "") {

                send_json([

                    "success" => false,

                    "message" => "resource_id and text are required"

                ], 400);

            }

            if (!resource_exists($pdo, $resourceId)) {

                send_json([

                    "success" => false,

                    "message" => "Resource not found"

                ], 404);

            }

            $stmt = $pdo->prepare("

                INSERT INTO comments_resource (resource_id, author, text)

                VALUES (?, ?, ?)

            ");

            $stmt->execute([$resourceId, $author, $text]);

            $id = (int) $pdo->lastInsertId();

            send_json([

                "success" => true,

                "id" => $id,

                "data" => [

                    "id" => $id,

                    "resource_id" => $resourceId,

                    "author" => $author,

                    "text" => $text,

                    "created_at" => ""

                ]

            ], 201);

        }

        $title = trim($data["title"] ?? "");

        $description = trim($data["description"] ?? "");

        $link = trim($data["link"] ?? "");

        if ($title === "" || $link === "" || !valid_url($link)) {

            send_json([

                "success" => false,

                "message" => "Valid title and link are required"

            ], 400);

        }

        $stmt = $pdo->prepare("

            INSERT INTO resources (title, description, link)

            VALUES (?, ?, ?)

        ");

        $stmt->execute([$title, $description, $link]);

        $id = (int) $pdo->lastInsertId();

        send_json([

            "success" => true,

            "id" => $id,

            "data" => [

                "id" => $id,

                "title" => $title,

                "description" => $description,

                "link" => $link,

                "created_at" => ""

            ]

        ], 201);

    }

    if ($method === "PUT") {

        $data = get_json_input();

        $id = isset($data["id"]) ? (int) $data["id"] : 0;

        if ($id <= 0) {

            send_json([

                "success" => false,

                "message" => "id is required"

            ], 400);

        }

        if (!resource_exists($pdo, $id)) {

            send_json([

                "success" => false,

                "message" => "Resource not found"

            ], 404);

        }

        if (isset($data["link"]) && !valid_url(trim($data["link"]))) {

            send_json([

                "success" => false,

                "message" => "Invalid link"

            ], 400);

        }

        $fields = [];

        $values = [];

        foreach (["title", "description", "link"] as $field) {

            if (array_key_exists($field, $data)) {

                $fields[] = "$field = ?";

                $values[] = trim((string) $data[$field]);

            }

        }

        if (empty($fields)) {

            send_json([

                "success" => false,

                "message" => "No fields to update"

            ], 400);

        }

        $values[] = $id;

        $stmt = $pdo->prepare("

            UPDATE resources

            SET " . implode(", ", $fields) . "

            WHERE id = ?

        ");

        $stmt->execute($values);

        send_json([

            "success" => true

        ]);

    }

    if ($method === "DELETE") {

        if ($action === "delete_comment") {

            $commentId = isset($_GET["comment_id"]) ? (int) $_GET["comment_id"] : 0;

            if ($commentId <= 0) {

                send_json([

                    "success" => false,

                    "message" => "comment_id is required"

                ], 400);

            }

            $stmt = $pdo->prepare("SELECT id FROM comments_resource WHERE id = ?");

            $stmt->execute([$commentId]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

                send_json([

                    "success" => false,

                    "message" => "Comment not found"

                ], 404);

            }

            $delete = $pdo->prepare("DELETE FROM comments_resource WHERE id = ?");

            $delete->execute([$commentId]);

            send_json([

                "success" => true

            ]);

        }

        $id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

        if ($id <= 0) {

            send_json([

                "success" => false,

                "message" => "id is required"

            ], 400);

        }

        if (!resource_exists($pdo, $id)) {

            send_json([

                "success" => false,

                "message" => "Resource not found"

            ], 404);

        }

        $stmt = $pdo->prepare("DELETE FROM comments_resource WHERE resource_id = ?");

        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");

        $stmt->execute([$id]);

        send_json([

            "success" => true

        ]);

    }

    send_json([

        "success" => false,

        "message" => "Method not allowed"

    ], 405);

} catch (Throwable $e) {

    send_json([

        "success" => false,

        "message" => "Server error"

    ], 500);

}

?>
