<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "g14";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
}

// ===== LOOKUP MEMBER FOR RENEWAL =====
if (isset($_GET['action']) && $_GET['action'] === 'lookupMember') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = $input['member_id'] ?? '';
    
    if (empty($member_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Member ID is required']);
        $conn->close();
        exit;
    }
    
    $stmt = $conn->prepare("SELECT member_id, first_name, last_name, membership_type, expiry_date, registration_date, IFNULL(is_deleted, 0) as is_deleted, status FROM members_tbl WHERE member_id = ? LIMIT 1");
    $stmt->bind_param("s", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $member = $result->fetch_assoc();
    $stmt->close();
    
    // Debug info
    error_log("Lookup Member: " . json_encode($member));
    
    // Determine current status
    $is_deleted = $member['is_deleted'] == 1;
    $expiry_date = $member['expiry_date'];
    $current_date = date('Y-m-d');
    $registration_date = $member['registration_date'];
    $inactive_threshold = date('Y-m-d', strtotime('-5 months'));
    
    // Status determination logic
    if ($is_deleted) {
        $member_status = 'Deleted';
    } elseif ($expiry_date < $current_date && $registration_date < $inactive_threshold) {
        $member_status = 'Inactive';
    } elseif ($expiry_date < $current_date) {
        $member_status = 'Expired';
    } else {
        $member_status = 'Active';
    }
    
    echo json_encode([
        'status' => 'success',
        'member_id' => $member['member_id'],
        'member_name' => $member['first_name'] . ' ' . $member['last_name'],
        'membership_type' => ucfirst($member['membership_type']),
        'expiry_date' => $member['expiry_date'],
        'member_status' => $member_status
    ]);
    $conn->close();
    exit;
}
// Current: Only logs 5 fields
$log_sql = "INSERT INTO renewal_log (member_id, renewal_date, new_expiry_date, membership_type, payment_method)";

// Should include all fields from your renewal_log table:
// - old_expiry_date (for comparison)
// - height, weight, bmi (physical metrics)
// - renewed_by (staff member)
// - first_name, last_name, email (snapshot)
// ===== PROCESS RENEWAL =====

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    $member_id = $_POST['member_id'] ?? '';
    
    if (empty($member_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Member ID is required']);
        $conn->close();
        exit;
    }
    
    // Fetch member details
    $stmt = $conn->prepare("SELECT * FROM members_tbl WHERE member_id = ? LIMIT 1");
    $stmt->bind_param("s", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $member = $result->fetch_assoc();
    $stmt->close();
    
    // CHECK IF MEMBER IS DELETED - PREVENT RENEWAL
    $is_deleted = isset($member['is_deleted']) && $member['is_deleted'] == 1;
    if ($is_deleted) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot renew a deleted member account. Please contact an administrator to restore the account first.']);
        $conn->close();
        exit;
    }
    
    // Get form data
    $height = !empty($_POST['height']) ? (int)$_POST['height'] : null;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $membership_type = $_POST['membershipType'] ?? $member['membership_type'];
    $payment_method = $_POST['paymentMethod'] ?? $member['payment_method'];
    
    // Calculate BMI
    $bmi = null;
    if ($height && $weight) {
        $bmi = $weight / (($height / 100) ** 2);
        $bmi = round($bmi, 2);
    }
    
    // Determine new expiry date (1 month from now)
    $new_expiry_date = date('Y-m-d', strtotime('+1 month'));
    
    try {
        // Update member with renewal information
        $update_sql = "UPDATE members_tbl SET 
                        height = ?, 
                        weight = ?, 
                        membership_type = ?, 
                        payment_method = ?, 
                        expiry_date = ?,
                        status = 'Active'
                       WHERE member_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isssss", $height, $weight, $membership_type, $payment_method, $new_expiry_date, $member_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Log renewal activity (optional)
        $renewal_date = date('Y-m-d H:i:s');
        $log_sql = "INSERT INTO renewal_log (member_id, renewal_date, new_expiry_date, membership_type, payment_method) 
                    VALUES (?, ?, ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        if ($log_stmt) {
            $log_stmt->bind_param("sssss", $member_id, $renewal_date, $new_expiry_date, $membership_type, $payment_method);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Membership renewed successfully! New expiry date: ' . $new_expiry_date,
            'bmi' => $bmi ?? 'N/A',
            'new_expiry_date' => $new_expiry_date,
            'member_id' => $member_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Renewal failed: ' . $e->getMessage()]);
    }
    
    $conn->close();
    exit;
}

$conn->close();
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);

$member_id = isset($_POST['member_id']) ? trim($_POST['member_id']) : '';
if (empty($member_id) || strlen($member_id) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid member ID']);
    exit;
}