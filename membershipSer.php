<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/Exception.php';
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "g14";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$gmail_config = [
    'sender_email' => 'f1jichu@gmail.com',
    'app_password' => 'yghb edfr nibw dett',
    'sender_name'  => 'G14 Fitness Centre'
];

$qr_dir = __DIR__ . '/qrcodes/';
if (!is_dir($qr_dir)) {
    mkdir($qr_dir, 0755, true);
}

function clean($v) {
    return htmlspecialchars(trim($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function normalizeMembershipType($raw) {
    $s = strtolower(trim($raw));
    if ($s === '') return 'standard';
    if (strpos($s, 'student') !== false || strpos($s, 'stu') !== false) return 'student';
    if (strpos($s, 'premium') !== false || strpos($s, 'pre') !== false) return 'premium';
    if (strpos($s, 'non member') !== false || strpos($s, 'non-member') !== false || strpos($s, 'nm') !== false) return 'non member';
    if (strpos($s, 'standard') !== false) return 'standard';
    return 'standard';
}

function prefix_for($type) {
    switch (strtolower($type)) {
        case 'premium': return 'PRE';
        case 'student': return 'STU';
        case 'non member': return 'NM';
        default: return 'STD';
    }
}

function generateMembershipId($conn, $membership_type) {
    $prefix = prefix_for($membership_type);
    $sql = "SELECT member_id FROM members_tbl WHERE member_id LIKE :pfx ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':pfx' => $prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        $num = (int) preg_replace('/\D/', '', substr($last, strlen($prefix)));
        return $prefix . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        return $prefix . "0001";
    }
}

function generateQRCode($text, $filename, $qr_dir) {
    $filepath = $qr_dir . $filename;
    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($text);
    try {
        $qr_image = @file_get_contents($qr_api_url);
        if ($qr_image === false) return false;
        file_put_contents($filepath, $qr_image);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendEmailWithAttachment($to_email, $to_name, $member_id, $qr_filepath, $gmail_config) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $gmail_config['sender_email'];
        $mail->Password   = $gmail_config['app_password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom($gmail_config['sender_email'], $gmail_config['sender_name']);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = "Your G14 Membership & QR Code - $member_id";

        $mail->Body = "
            <div style='font-family: Arial, sans-serif;'>
                <h2 style='color:#667eea;'>Welcome to G14 Fitness Centre</h2>
                <p>Hello <strong>$to_name</strong>,</p>
                <p>Your membership registration is successful! Below is your QR code for ID: <strong>$member_id</strong>.</p>
                <br>
                <img src='cid:qr_code' alt='QR Code' style='width:250px;height:250px;border:2px solid #667eea;border-radius:8px;'>
                <br><br>
                <p>Please show this QR code at the reception when you visit.</p>
                <p>Generated on: " . date('Y-m-d H:i:s') . "</p>
                <p>— <strong>G14 Fitness Centre Team</strong></p>
            </div>
        ";

        if (file_exists($qr_filepath)) {
            $mail->addEmbeddedImage($qr_filepath, 'qr_code', 'QR_' . $member_id . '.png');
            $mail->addAttachment($qr_filepath, 'QR_' . $member_id . '.png');
        } else {
            return ['success' => false, 'message' => 'QR file not found'];
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully!'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Email failed: ' . $mail->ErrorInfo];
    }
}

// ===== RESTORE MEMBER HANDLER =====
if (isset($_GET['action']) && $_GET['action'] === 'restoreMember') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = $input['member_id'] ?? '';
    
    if (empty($member_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Member ID is required']);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE members_tbl SET is_deleted = 0, status = 'Active' WHERE member_id = :member_id");
        $stmt->execute([':member_id' => $member_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Member has been restored successfully and is now Active'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Member not found'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to restore member: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ===== DELETE MEMBER HANDLER =====
if (isset($_GET['action']) && $_GET['action'] === 'deleteMember') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = $input['member_id'] ?? '';
    
    if (empty($member_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Member ID is required']);
        exit;
    }
    
    try {
        $check_column = $conn->query("SHOW COLUMNS FROM members_tbl LIKE 'is_deleted'");
        
        if ($check_column->rowCount() == 0) {
            $conn->exec("ALTER TABLE members_tbl ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
        }
        
        $stmt = $conn->prepare("UPDATE members_tbl SET is_deleted = 1, status = 'Deleted' WHERE member_id = :member_id");
        $stmt->execute([':member_id' => $member_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Member marked as deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Member not found or already deleted'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete member: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle "View QR" request
if (isset($_GET['action']) && $_GET['action'] === 'sendMemberQR') {
    $input = json_decode(file_get_contents('php://input'), true);
    $member_id = $input['member_id'] ?? '';

    if (!$member_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing member ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT first_name, last_name FROM members_tbl WHERE member_id = :mid");
    $stmt->execute([':mid' => $member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        exit;
    }

    $qr_filename = 'QR_' . $member_id . '.png';
    $qr_filepath = $qr_dir . $qr_filename;

    if (!file_exists($qr_filepath)) {
        $qr_data = "Member ID: $member_id\nName: {$member['first_name']} {$member['last_name']}";
        if (!generateQRCode($qr_data, $qr_filename, $qr_dir)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to generate QR code']);
            exit;
        }
    }

    echo json_encode([
        'status' => 'success',
        'qr_path' => 'qrcodes/' . $qr_filename,
        'message' => 'QR code ready.'
    ]);
    exit;
}

/* Main registration flow - FIXED */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    $registered_by = $_SESSION['employee_name'] ?? 'Unknown Staff';
    
    // Extract raw email BEFORE sanitization
    $raw_email = $_POST['email'] ?? '';
    
    // Validate email format BEFORE cleaning
    if (empty($raw_email) || !filter_var($raw_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Valid email is required']);
        exit;
    }
    
    // NOW clean the data
    $email = clean($raw_email);
    
    $memberData = [
        'first_name' => clean($_POST['first_name'] ?? ''),
        'last_name' => clean($_POST['last_name'] ?? ''),
        'email' => $email,
        'phone' => clean($_POST['phone'] ?? ''),
        'date_of_birth' => clean($_POST['date_of_birth'] ?? ''),
        'gender' => clean($_POST['gender'] ?? ''),
        'height' => $_POST['height'] ?? 0,
        'weight' => $_POST['weight'] ?? 0,
        'membership_type_raw' => $_POST['membership_type'] ?? '',
        'payment_method' => clean($_POST['payment_method'] ?? ''),
        'emergency_contact_name' => clean($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => clean($_POST['emergency_contact_phone'] ?? '')
    ];

    // Verify we have a first name and last name
    if (empty($memberData['first_name']) || empty($memberData['last_name'])) {
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        exit;
    }

    $membership_type = normalizeMembershipType($memberData['membership_type_raw']);
    $height = is_numeric($memberData['height']) ? (int)$memberData['height'] : null;
    $weight = is_numeric($memberData['weight']) ? (int)$memberData['weight'] : null;

    // Check if email already exists
    $stmt = $conn->prepare("SELECT member_id FROM members_tbl WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $memberData['email']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }

    $membershipId = generateMembershipId($conn, $membership_type);

    try {
        $sql = "INSERT INTO members_tbl (
            member_id, first_name, last_name, email, phone, date_of_birth, gender,
            height, weight, membership_type, payment_method,
            emergency_contact_name, emergency_contact_phone, registered_by, registration_date, expiry_date, is_deleted, status
        ) VALUES (
            :member_id, :first_name, :last_name, :email, :phone, :date_of_birth, :gender,
            :height, :weight, :membership_type, :payment_method,
            :emergency_contact_name, :emergency_contact_phone, :registered_by, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), 0, 'Active'
        )";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':member_id' => $membershipId,
            ':first_name' => $memberData['first_name'],
            ':last_name' => $memberData['last_name'],
            ':email' => $memberData['email'],
            ':phone' => $memberData['phone'],
            ':date_of_birth' => $memberData['date_of_birth'] ?: null,
            ':gender' => $memberData['gender'] ?: null,
            ':height' => $height,
            ':weight' => $weight,
            ':membership_type' => $membership_type,
            ':payment_method' => $memberData['payment_method'],
            ':emergency_contact_name' => $memberData['emergency_contact_name'],
            ':emergency_contact_phone' => $memberData['emergency_contact_phone'],
            ':registered_by' => $registered_by
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB Insert failed: ' . $e->getMessage(), 'code' => $e->getCode()]);
        exit;
    }

    $qr_filename = 'QR_' . $membershipId . '.png';
    $qr_filepath = $qr_dir . $qr_filename;
    $qr_data = "Member ID: $membershipId\nName: {$memberData['first_name']} {$memberData['last_name']}";
    generateQRCode($qr_data, $qr_filename, $qr_dir);

    $email_result = sendEmailWithAttachment(
        $memberData['email'],
        $memberData['first_name'] . ' ' . $memberData['last_name'],
        $membershipId,
        $qr_filepath,
        $gmail_config
    );

    echo json_encode([
        'success' => true,
        'message' => 'Member registered successfully!',
        'member_id' => $membershipId,
        'registered_by' => $registered_by,
        'email_status' => $email_result['message']
    ]);
    exit;
}