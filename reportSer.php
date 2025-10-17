<?php
// reportSer.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "g14";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        "status" => "error", 
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Determine request type
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Receive data from fetch
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!isset($data['equipment']) || !isset($data['status']) || !isset($data['condition'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    // Validate field lengths
    if (strlen($data['equipment']) > 100) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Equipment name too long"]);
        exit;
    }

    if (strlen($data['condition']) < 10) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Condition description too short (minimum 10 characters)"]);
        exit;
    }

    if (strlen($data['condition']) > 500) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Condition description too long (maximum 500 characters)"]);
        exit;
    }

    // Sanitize inputs
    $equipment = trim($data['equipment']);
    $status = trim($data['status']);
    $condition = trim($data['condition']);

    // Validate status
    $validStatuses = ['Good', 'Needs Repair', 'Needs Replacement', 'Damaged', 'Under Maintenance'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid status value"]);
        exit;
    }

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO reports_tbl (equipment, status, condition_text) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $equipment, $status, $condition);

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success", 
            "message" => "Report added successfully",
            "id" => $conn->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to add report: " . $stmt->error
        ]);
    }

    $stmt->close();

} elseif ($method === 'GET') {
    // Fetch all reports with optional filtering
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;

    $sql = "SELECT * FROM reports_tbl WHERE 1=1";
    $params = [];
    $types = "";

    if ($statusFilter) {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if ($searchTerm) {
        $sql .= " AND (equipment LIKE ? OR condition_text LIKE ?)";
        $searchParam = "%" . $searchTerm . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    $sql .= " ORDER BY created_at DESC";

    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    http_response_code(200);
    echo json_encode($reports);

    if (isset($stmt)) {
        $stmt->close();
    }

} elseif ($method === 'DELETE') {
    // Delete a report
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing report ID"]);
        exit;
    }

    $id = intval($data['id']);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid report ID"]);
        exit;
    }

    // Use prepared statement
    $stmt = $conn->prepare("DELETE FROM reports_tbl WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            http_response_code(200);
            echo json_encode([
                "status" => "success", 
                "message" => "Report deleted successfully"
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "status" => "error", 
                "message" => "Report not found"
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Failed to delete report: " . $stmt->error
        ]);
    }

    $stmt->close();

} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error", 
        "message" => "Method not allowed"
    ]);
}

$conn->close();
?>