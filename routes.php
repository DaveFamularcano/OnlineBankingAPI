<?php
// Import required files
require_once "./config/database.php";
require_once "./modules/Get.php";
require_once "./modules/Post.php";
require_once "./modules/Patch.php"; 
require_once "./modules/Delete.php";
require_once "./modules/auth.php";

// Initialize database connection
try {
    $db = new Connection();
    $pdo = $db->connect();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

// Instantiate classes
$get = new Get($pdo);
$post = new Post($pdo);
$patch = new Patch($pdo);
$delete = new Delete($pdo);
$auth = new Authentication($pdo);

$request = isset($_REQUEST['request']) ? explode("/", trim($_REQUEST['request'], "/")) : [];
$method = $_SERVER['REQUEST_METHOD'];

if (empty($request)) {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found."]);
    exit;
}

$endpoint = $request[0];
$id = $request[1] ?? null;

try {
    switch ($method) {
        case "GET":
            handleGet($endpoint, $id, $get);  // No authentication needed here
            break;
        case "POST":
            $body = getRequestBody();
            handlePost($endpoint, $body, $post, $auth);  // Token validation still required here
            break;
        case "PATCH":
            $body = getRequestBody();
            handlePatch($endpoint, $id, $body, $patch);  // No authentication needed here
            break;
        case "DELETE":
            handleDelete($endpoint, $id, $delete);  // No authentication needed here
            break;
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed."]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "An error occurred: " . $e->getMessage()]);
}

// Function to handle GET requests
function handleGet($endpoint, $id, $get) {
    // No token validation here, allowing public access
    if ($endpoint == "accounts") {
        echo json_encode($id ? $get->getAccount($id) : $get->getAllAccounts());
    } elseif ($endpoint == "transactions") {
        echo json_encode($id ? $get->getTransaction($id) : $get->getAllTransactions());
    } elseif ($endpoint == "users") {
        echo json_encode($id ? $get->getUser($id) : $get->getAllUsers());
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
    }
}

// Function to handle POST requests
function handlePost($endpoint, $body, $post, $auth) {
    // Allow registration and login without authentication
    if ($endpoint == "register") {
        echo json_encode($post->addUser($body));
        return;
    }

    if ($endpoint == "login") {
        echo json_encode($post->login($body)); 
        return;
    }


    // Proceed with other POST operations (transactions, etc.)
    if ($endpoint == "transactions") {
        echo json_encode($post->createTransaction($body['account_id'], $body['transaction_type'], $body['amount'], $body['recipient_account_id'] ?? null));
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
    }
}

// Function to handle PATCH requests
function handlePatch($endpoint, $id, $body, $patch) {
    // No token validation here, allowing public access
    if ($endpoint == "accounts") {
        echo json_encode($patch->updateAccount($body, $id));
    } elseif ($endpoint == "transactions") {
        echo json_encode($patch->updateTransaction($body, $id));
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
    }
}

// Function to handle DELETE requests
function handleDelete($endpoint, $id, $delete) {
    // No token validation here, allowing public access
    if ($endpoint == "accounts") {
        echo json_encode($delete->deleteAccount($id));
    } elseif ($endpoint == "transactions") {
        echo json_encode($delete->deleteTransaction($id));
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found."]);
    }
}

// Helper function to get request body
function getRequestBody() {
    return json_decode(file_get_contents('php://input'), true);
}
