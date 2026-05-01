<?php
/**
 * Discussion Board API
 *
 * RESTful API for CRUD operations on discussion topics and their replies.
 * Uses PDO to interact with the MySQL database defined in schema.sql.
 *
 * Database Tables (ground truth: schema.sql):
 *
 * Table: topics
 *   id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT
 *   subject    VARCHAR(255)  NOT NULL
 *   message    TEXT          NOT NULL
 *   author     VARCHAR(100)  NOT NULL
 *   created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
 *
 * Table: replies
 *   id         INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT
 *   topic_id   INT UNSIGNED  NOT NULL — FK → topics.id (ON DELETE CASCADE)
 *   text       TEXT          NOT NULL
 *   author     VARCHAR(100)  NOT NULL
 *   created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
 *
 * HTTP Methods Supported:
 *   GET    — Retrieve topic(s) or replies
 *   POST   — Create a new topic or reply
 *   PUT    — Update an existing topic
 *   DELETE — Delete a topic (cascade removes its replies) or a reply
 *
 * URL scheme (all requests go to index.php):
 *
 *   Topics:
 *     GET    ./api/index.php                  — list all topics
 *     GET    ./api/index.php?id={id}           — get one topic by integer id
 *     POST   ./api/index.php                  — create a new topic
 *     PUT    ./api/index.php                  — update a topic (id in JSON body)
 *     DELETE ./api/index.php?id={id}           — delete a topic
 *
 *   Replies (action parameter selects the replies sub-resource):
 *     GET    ./api/index.php?action=replies&topic_id={id}
 *                                             — list replies for a topic
 *     POST   ./api/index.php?action=reply     — create a reply
 *     DELETE ./api/index.php?action=delete_reply&id={id}
 *                                             — delete a single reply
 *
 * Query parameters for GET all topics:
 *   search — filter rows where subject LIKE or message LIKE or author LIKE
 *   sort   — column to sort by; allowed: subject, author, created_at
 *            (default: created_at)
 *   order  — sort direction; allowed: asc, desc (default: desc)
 *
 * Response format: JSON
 *   Success: { "success": true,  "data": ... }
 *   Error:   { "success": false, "message": "..." }
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS.
// Set Content-Type to application/json.

header("Content-Type: application/json");

// Allow cross-origin requests (CORS) if needed.

header('Access-Control-Allow-Origin: *'); 

// Allow HTTP methods: GET, POST, PUT, DELETE, OPTIONS.

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Allow headers: Content-Type, Authorization.

header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request.
// If the request method is OPTIONS, return HTTP 200 and exit.

if($_SERVER['REQUEST_METHOD']==='OPTIONS'){
    http_response_code(200);
    exit;
}

// TODO: Include the shared database connection file.
// require_once __DIR__ . '/../../common/db.php';

require_once __DIR__ . '/../../common/db.php';

// TODO: Get the PDO database connection.
// $db = getDBConnection();

$db = getDBConnection();

// TODO: Read the HTTP request method.
// $method = $_SERVER['REQUEST_METHOD'];

$method = $_SERVER['REQUEST_METHOD'];

// TODO: Read and decode the request body for POST and PUT requests.
// $rawData = file_get_contents('php://input');
// $data    = json_decode($rawData, true) ?? [];

$rawData = file_get_contents('php://input');
$data    = json_decode($rawData, true) ?? [];

// TODO: Read query parameters.
// $action  = $_GET['action']   ?? null;  // 'replies', 'reply', 'delete_reply'
// $id      = $_GET['id']       ?? null;  // integer topic or reply id
// $topicId = $_GET['topic_id'] ?? null;  // integer topic id for replies queries

$action  = $_GET['action']   ?? null;
$id      = $_GET['id']       ?? null;
$topicId = $_GET['topic_id'] ?? null;

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Get all topics (with optional search and sort).
 * Method: GET (no ?id or ?action parameter).
 *
 * Query parameters handled inside:
 *   search — filter by subject LIKE or message LIKE or author LIKE
 *   sort   — allowed: subject, author, created_at   (default: created_at)
 *   order  — allowed: asc, desc                     (default: desc)
 */
function getAllTopics(PDO $db): void
{
    // TODO: Build the base SELECT query.
    // SELECT id, subject, message, author, created_at FROM topics

    $query='SELECT id, subject, message, author, created_at FROM topics';
    $parameters=[];
    // TODO: If $_GET['search'] is provided and non-empty, append:
    // WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search
    // Bind '%' . $search . '%' to :search.

    if(!empty($_GET['search'])){
        $query= $query.' WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search';
        $parameters['search'] = '%' . trim($_GET['search']) . '%';
    }

    // TODO: Validate $_GET['sort'] against the whitelist
    // [subject, author, created_at].
    // Default to 'created_at' if missing or invalid.

    $whitelist=['subject', 'author', 'created_at'];
    $sort = $_GET['sort'] ?? 'created_at';
    if(!in_array($sort,$whitelist,true)){
        $sort ='created_at';
    }


    // TODO: Validate $_GET['order'] against [asc, desc].
    // Default to 'desc' if missing or invalid.

    $order = strtolower($_GET['order'] ?? 'desc');
    if(!in_array($order,['asc','desc'],true)){
        $order ='desc';
    }

    // TODO: Append ORDER BY {sort} {order} to the query.
    $query= $query." ORDER BY $sort $order";

    // TODO: Prepare, bind (if searching), and execute the statement.
    $stmt=$db->prepare($query);
    $stmt->execute($parameters);

    // TODO: Fetch all rows as an associative array.
    $rows_topic= $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Call sendResponse(['success' => true, 'data' => $topics]);
    sendResponse(['success' => true, 'data' => $rows_topic]);
}


/**
 * Get a single topic by its integer primary key.
 * Method: GET with ?id={id}.
 *
 * Response (found):
 *   { "success": true, "data": { id, subject, message, author, created_at } }
 * Response (not found): HTTP 404.
 */
function getTopicById(PDO $db, $id): void
{
    // TODO: Validate that $id is provided and numeric.
    // If not, sendResponse HTTP 400.
    if(!$id||!is_numeric($id)){
        sendResponse(['success' => false, 'message' => 'invalid id for the topic' ],400);
    }
    // TODO: SELECT id, subject, message, author, created_at
    //       FROM topics WHERE id = ?
    $query='SELECT id, subject, message, author, created_at FROM topics WHERE id = ?';
    $stmt=$db->prepare($query);
    $stmt->execute([$id]);
    // TODO: Fetch one row.
    // If found, sendResponse success with the topic.
    // If not found, sendResponse HTTP 404.

    $row=$stmt->fetch(PDO::FETCH_ASSOC);

    if($row){
        sendResponse(['success' => true, 'data' => $row]);
    }else{
        sendResponse(['success' => false, 'message' => 'Topic not found.'], 404);
    }
}


/**
 * Create a new topic.
 * Method: POST (no ?action parameter).
 *
 * Required JSON body fields:
 *   subject — string (required)
 *   message — string (required)
 *   author  — string (required)
 *
 * Response (success): HTTP 201 — { success, message, id }
 * Response (missing fields): HTTP 400.
 *
 * Note: id and created_at are handled automatically by MySQL.
 */
function createTopic(PDO $db, array $data): void
{
    // TODO: Validate that subject, message, and author are present and
    // non-empty. If missing, sendResponse HTTP 400.
    if(empty($data['subject'])||empty($data['message'])||empty($data['author'])){
        sendResponse(['success' => false, 'message' => 'some required fields are missing'],400);
    }

    // TODO: Trim subject, message, and author.

    $subject=sanitizeInput($data['subject']);
    $message=sanitizeInput($data['message']);
    $author=sanitizeInput($data['author']);

    if ($subject === '' || $message === '' || $author === '') {
        sendResponse(['success' => false, 'message' => 'Fields cannot be empty.'], 400);
    }

    // TODO: INSERT INTO topics (subject, message, author) VALUES (?, ?, ?)
    // Note: id and created_at are set automatically by MySQL.
    $stmt=$db->prepare('INSERT INTO topics (subject, message, author) VALUES (?, ?, ?)');
    $stmt->execute([$subject,$message,$author]);

    // TODO: If rowCount() > 0, sendResponse HTTP 201 with the new integer
    // id from $db->lastInsertId().
    // Otherwise sendResponse HTTP 500.

    if($stmt->rowCount()>0){
        $lastId=(int)$db->lastInsertId();
        sendResponse(['success' => true, 'message' => 'topic created','id'=>$lastId],201);
    }else{
        sendResponse(['success' => false, 'message' => 'topic not created(failed)'],500);
    }
    
}


/**
 * Update an existing topic.
 * Method: PUT.
 *
 * Required JSON body:
 *   id — integer primary key of the topic to update (required).
 * Optional JSON body fields (at least one must be present):
 *   subject, message.
 *
 * Response (success): HTTP 200.
 * Response (not found): HTTP 404.
 */
function updateTopic(PDO $db, array $data): void
{
    // TODO: Validate that $data['id'] is present.
    // If not, sendResponse HTTP 400.
    if(empty($data['id'])){
        sendResponse(['success' => false, 'message' => 'required topic id not present'],400);        
    }

    // TODO: Check that a topic with this id exists.
    // If not, sendResponse HTTP 404.
    $stmt=$db->prepare('SELECT * FROM topics WHERE id = ?');
    $stmt->execute([$data['id']]);

    if(!$stmt->fetch(PDO::FETCH_ASSOC)){
        sendResponse(['success' => false, 'message' => 'Topic not present.'], 404);
    }
    // TODO: Dynamically build the SET clause for whichever of
    // subject, message are present in $data.

    $query_fields=[];
    $query_inputs=[];

    if(isset($data['subject'])&&trim($data['subject'])!==''){
        $query_fields[]="subject = ?";
        $query_inputs[]=sanitizeInput($data['subject']);
    }

    
    if(isset($data['message'])&&trim($data['message'])!==''){
        $query_fields[]="message = ?";
        $query_inputs[]=sanitizeInput($data['message']);
    }

    // TODO: If no updatable fields are present, sendResponse HTTP 400.
    if(empty($query_fields)){
        sendResponse(['success' => false, 'message' => 'update fields not present.'], 400);
    }
    // TODO: Build: UPDATE topics SET {clauses} WHERE id = ?
    // Prepare, bind all SET values, then bind id, and execute.

    $query_inputs[]=$data['id'];

    $query="UPDATE topics SET " . implode(", ", $query_fields) . " WHERE id = ?";

    $stmt=$db->prepare($query);

    // TODO: sendResponse HTTP 200 on success, HTTP 500 on failure.
    if($stmt->execute($query_inputs)){
       sendResponse(['success' => true, 'message' => 'the topic has been updated successfully']); 
    }else{
        sendResponse(['success' => false, 'message' => 'topic update failed'],500);
    }
}


/**
 * Delete a topic by integer id.
 * Method: DELETE with ?id={id}.
 *
 * The ON DELETE CASCADE constraint on replies.topic_id automatically
 * removes all replies for this topic — no manual deletion of replies
 * is needed.
 *
 * Response (success): HTTP 200.
 * Response (not found): HTTP 404.
 */
function deleteTopic(PDO $db, $id): void
{
    // TODO: Validate that $id is provided and numeric.
    // If not, sendResponse HTTP 400.
    if(!is_numeric($id)||!$id){
        sendResponse(['success' => false, 'message' => 'id not present'], 400);
    }
    // TODO: Check that a topic with this id exists.
    // If not, sendResponse HTTP 404.

    $stmt=$db->prepare("SELECT id FROM topics WHERE id = ?");
    $stmt->execute([$id]);

    if(!$stmt->fetch(PDO::FETCH_ASSOC)){
        sendResponse(['success' => false, 'message' => 'no such topic exists'], 404);
    }
    // TODO: DELETE FROM topics WHERE id = ?
    // (replies rows are removed automatically by ON DELETE CASCADE.)

    $stmt = $db->prepare("DELETE FROM topics WHERE id = ?");
    $stmt->execute([$id]);


    // TODO: If rowCount() > 0, sendResponse HTTP 200.
    // Otherwise sendResponse HTTP 500.
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Topic deleted.']);
    } else {
        sendResponse(['success' => false, 'message' => 'topic deletion failed.'], 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Get all replies for a specific topic.
 * Method: GET with ?action=replies&topic_id={id}.
 *
 * Reads from the replies table.
 * Returns an empty data array if no replies exist — not an error.
 *
 * Each reply object: { id, topic_id, text, author, created_at }
 */
function getRepliesByTopicId(PDO $db, $topicId): void
{
    // TODO: Validate that $topicId is provided and numeric.
    // If not, sendResponse HTTP 400.
    if(!$topicId||!is_numeric($topicId)){
        sendResponse(['success' => false, 'message' => 'topic id not valid.'], 400);
    }
    // TODO: SELECT id, topic_id, text, author, created_at
    //       FROM replies
    //       WHERE topic_id = ?
    //       ORDER BY created_at ASC

    $stmt=$db->prepare('SELECT id, topic_id, text, author, created_at FROM replies WHERE topic_id = ? ORDER BY created_at ASC');
    $stmt->execute([$topicId]);
    // TODO: Fetch all rows. Return sendResponse with the array
    //       (empty array is valid).
    $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse(['success' => true, 'data' => $rows]);
}


/**
 * Create a new reply.
 * Method: POST with ?action=reply.
 *
 * Required JSON body:
 *   topic_id — integer FK into topics.id (required)
 *   text     — string (required, must be non-empty after trim)
 *   author   — string (required)
 *
 * Response (success): HTTP 201 — { success, message, id, data: reply }
 * Response (topic not found): HTTP 404.
 * Response (missing fields): HTTP 400.
 *
 * Note: id and created_at are handled automatically by MySQL.
 */
function createReply(PDO $db, array $data): void
{
    // TODO: Validate that topic_id, text, and author are all present and
    // non-empty after trimming. If any are missing, sendResponse HTTP 400.

    if(empty($data['topic_id'])||empty($data['text'])||empty($data['author'])){
        sendResponse(['success' => false, 'message' => 'required inputs are missing.'], 400);
    }
    if(trim($data['text'])===''||trim($data['author'])===''){
        sendResponse(['success' => false, 'message' => 'some fields are empty.'], 400);
    }
    // TODO: Validate that topic_id is numeric.
    if(!is_numeric($data['topic_id'])){
        sendResponse(['success' => false, 'message' => 'topic id is not valid.'], 400);
    }
    
    $topicId = (int)$data['topic_id'];
    $text    = sanitizeInput($data['text']);
    $author  = sanitizeInput($data['author']);

    
    // TODO: Check that a topic with this id exists in the topics table.
    // If not, sendResponse HTTP 404.
    $stmt = $db->prepare("SELECT id FROM topics WHERE id = ?");
    $stmt->execute([$topicId]);

    if(!$stmt->fetch()){
        sendResponse(['success' => false, 'message' => 'no such topic exists.'], 404);
    }

    // TODO: INSERT INTO replies (topic_id, text, author) VALUES (?, ?, ?)
    // Note: id and created_at are set automatically by MySQL.
    $stmt = $db->prepare("INSERT INTO replies (topic_id, text, author) VALUES (?, ?, ?)");
    $stmt->execute([$topicId, $text, $author]);

    // TODO: If rowCount() > 0, sendResponse HTTP 201 with the new id
    //       and the full new reply object.
    // Otherwise sendResponse HTTP 500.
    if($stmt->rowCount()>0){
        $lastId=(int)$db->lastInsertId();

        $stmt = $db->prepare(" SELECT id, topic_id, text, author, created_at FROM replies WHERE id = ?");
        $stmt->execute([$lastId]);

        $row=$stmt->fetch(PDO::FETCH_ASSOC);

        sendResponse(['success' => true, 'message' => 'reply created.','id'=>$lastId,'data'=>$row], 201);

    }else{
      sendResponse(['success' => false, 'message' => 'reply creation failed.'], 500);  
    }

}


/**
 * Delete a single reply.
 * Method: DELETE with ?action=delete_reply&id={id}.
 *
 * Response (success): HTTP 200.
 * Response (not found): HTTP 404.
 */
function deleteReply(PDO $db, $replyId): void
{
    // TODO: Validate that $replyId is provided and numeric.
    // If not, sendResponse HTTP 400.

    if(!$replyId||!is_numeric($replyId)){
       sendResponse(['success' => false, 'message' => 'id not valid.'], 400); 
    }

    // TODO: Check that the reply exists in the replies table.
    // If not, sendResponse HTTP 404.
    $stmt = $db->prepare("SELECT id FROM replies WHERE id = ?");
    $stmt->execute([$replyId]);

    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Reply not found.'], 404);
    }
    // TODO: DELETE FROM replies WHERE id = ?
    $stmt = $db->prepare("DELETE FROM replies WHERE id = ?");
    $stmt->execute([$replyId]);

    // TODO: If rowCount() > 0, sendResponse HTTP 200.
    // Otherwise sendResponse HTTP 500.
    if($stmt->rowCount() > 0){
        sendResponse(['success' => true, 'message' => 'Reply is deleted.']);
    }else{
        sendResponse(['success' => false, 'message' => 'reply deletion failed.'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === 'GET') {

        // ?action=replies&topic_id={id} → list replies for a topic
        // TODO: if $action === 'replies', call getRepliesByTopicId($db, $topicId)
        if($action==='replies'){
          getRepliesByTopicId($db, $topicId);  
        

         // ?id={id} → single topic
         // TODO: elseif $id is set, call getTopicById($db, $id)
        }else if($id){
            getTopicById($db, $id);
        
         // no parameters → all topics (supports ?search, ?sort, ?order)
         // TODO: else call getAllTopics($db)
        }else{
            getAllTopics($db);
        }
        
    } elseif ($method === 'POST') {

        // ?action=reply → create a reply in the replies table
        // TODO: if $action === 'reply', call createReply($db, $data)
        if($action==='reply'){
            createReply($db, $data);
        

         // no action → create a new topic
         // TODO: else call createTopic($db, $data)
        }else{
            createTopic($db, $data);   
        }
        
    } elseif ($method === 'PUT') {

        // Update a topic; id comes from the JSON body
        // TODO: call updateTopic($db, $data)
        updateTopic($db, $data);
        
    } elseif ($method === 'DELETE') {

        // ?action=delete_reply&id={id} → delete one reply
        // TODO: if $action === 'delete_reply', call deleteReply($db, $id)
        if($action === 'delete_reply'){
            deleteReply($db, $id);
        
         // ?id={id} → delete a topic (and its replies via CASCADE)
         // TODO: else call deleteTopic($db, $id)
        }else{
            deleteTopic($db, $id);   
        }
    } else {
        // TODO: sendResponse HTTP 405 Method Not Allowed.
        sendResponse(['success' => false, 'message' => 'Method is not allowed.'], 405);
    }

} catch (PDOException $e) {
    // TODO: Log the error with error_log().
    // Return a generic HTTP 500 — do NOT expose $e->getMessage() to clients.
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Internal server error.'], 500);

} catch (Exception $e) {
    // TODO: Log the error with error_log().
    // Return HTTP 500 using sendResponse().
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Method is not allowed.'], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send a JSON response and stop execution.
 *
 * @param array $data        Must include a 'success' key.
 * @param int   $statusCode  HTTP status code (default 200).
 */
function sendResponse(array $data, int $statusCode = 200): void
{
    // TODO: http_response_code($statusCode);
    // TODO: echo json_encode($data, JSON_PRETTY_PRINT);
    // TODO: exit;

    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}


/**
 * Sanitize a string input.
 *
 * @param  string $data
 * @return string  Trimmed, tag-stripped, HTML-encoded string.
 */
function sanitizeInput(string $data): string
{
    // TODO: return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}
