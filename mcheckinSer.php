<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

ob_start();

try {
    $conn = new mysqli("localhost", "root", "", "g14");

    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $conn->set_charset("utf8mb4");

    $rawInput = file_get_contents("php://input");
    $data = json_decode($rawInput);

    if (!isset($data->member_id) || empty(trim($data->member_id))) {
        throw new Exception("Member ID or Walk-in ID is required");
    }

    $member_id_input = $conn->real_escape_string(trim($data->member_id));
    $member_id = $member_id_input;
    
    $memberName = "";
    $memberFound = false;
    $memberType = "";
    $searchName = '%' . $member_id_input . '%';
    $isDeleted = false;

    // 1. Check members_tbl first (Regular Members)
    $stmt = $conn->prepare("SELECT member_id, CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) as name, IFNULL(is_deleted, 0) as is_deleted, expiry_date, status FROM members_tbl WHERE member_id = ? OR CONCAT(first_name, ' ', last_name) LIKE ? LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $member_id_input, $searchName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $memberData = $result->fetch_assoc();
        $member_id = $memberData['member_id'];
        $memberName = trim($memberData['name']);
        $memberFound = true;
        $memberType = "Regular Member";
        $isDeleted = $memberData['is_deleted'] == 1;
    }
    $stmt->close();

    // 2. If not found, check walkin_members_tbl (Walk-in Members)
    if (!$memberFound) {
        $stmt = $conn->prepare("SELECT member_id, CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) as name FROM walkin_members WHERE member_id = ? OR CONCAT(first_name, ' ', last_name) LIKE ? LIMIT 1");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $member_id_input, $searchName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $walkinData = $result->fetch_assoc();
            $member_id = $walkinData['member_id'];
            $memberName = trim($walkinData['name']);
            $memberFound = true;
            $memberType = "Walk-in Member";
            $isDeleted = false;
        }
        $stmt->close();
    }

    // 3. Handle a new walk-in by name
    if (!$memberFound) {
        if (!preg_match('/^[A-Z]{1,2}\d+$/i', $member_id_input)) {
            $member_id = "WID_" . hash('crc32', $member_id_input);
            $memberName = $member_id_input;
            $memberType = "New Walk-in (Name Only)";
            $memberFound = true;
            $isDeleted = false;
        } else {
             throw new Exception("Member ID or Name '$member_id_input' not found in our system");
        }
    }

    // CHECK IF MEMBER IS DELETED - PREVENT CHECK IN/OUT
    if ($isDeleted) {
        throw new Exception("This member account has been deleted and cannot check in or out. Please contact the administrator to restore the account.");
    }

    if (empty($memberName)) {
        $memberName = "Unknown Member";
    }

    // Determine Check In/Check Out Action
    $stmt = $conn->prepare("SELECT action FROM memattendance_tbl WHERE member_id = ? AND date = CURDATE() ORDER BY id DESC LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $member_id);
    $stmt->execute();
    $latestResult = $stmt->get_result();
    
    $latestAction = null;
    if ($latestResult->num_rows > 0) {
        $latestAction = $latestResult->fetch_assoc()['action'];
    }
    $stmt->close();

    if ($latestAction === null || $latestAction === "Check Out") {
        $actionText = "Check In";
        $welcomeText = "Welcome";
    } else {
        $actionText = "Check Out";
        $welcomeText = "Goodbye";
    }

    // Insert Attendance Record
    $stmt = $conn->prepare("INSERT INTO memattendance_tbl (member_id, name, action, date, time) VALUES (?, ?, ?, CURDATE(), CURTIME())");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sss", $member_id, $memberName, $actionText);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to record attendance: " . $stmt->error);
    }
    $stmt->close();

    $response = [
        "status" => "success",
        "message" => "$welcomeText, $memberName ($memberType)! You have successfully **$actionText**.",
        "member_id" => $member_id,
        "name" => $memberName,
        "member_type" => $memberType,
        "action" => $actionText,
        "timestamp" => date("Y-m-d H:i:s")
    ];

    $conn->close();

} catch (Exception $e) {
    $response = [
        "status" => "error",
        "message" => "❌ Error: " . $e->getMessage(),
        "error_detail" => $e->getMessage()
    ];
}

ob_end_clean();
echo json_encode($response);
exit;