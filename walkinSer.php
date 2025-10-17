<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "g14";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]);
    exit;
}

// Set charset
$conn->set_charset("utf8mb4");

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
    exit;
}

// Function to generate next member ID
function generateMemberID($conn) {
    // Get the last member ID
    $result = $conn->query("SELECT member_id FROM walkin_members WHERE member_id LIKE 'NM%' ORDER BY member_id DESC LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['member_id'];
        
        // Extract the number part (e.g., from "NB0001" get "0001")
        $number = intval(substr($lastID, 2));
        
        // Increment and format with leading zeros
        $newNumber = $number + 1;
        $newMemberID = "NM" . str_pad($newNumber, 4, "0", STR_PAD_LEFT);
    } else {
        // First member
        $newMemberID = "NM0001";
    }
    
    return $newMemberID;
}

// Validation errors array
$errors = [];

// Retrieve and sanitize form data
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
$gender = isset($_POST['gender']) ? $_POST['gender'] : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$height = isset($_POST['height']) ? floatval($_POST['height']) : 0;
$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$emergency_contact_name = isset($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : '';
$emergency_contact_phone = isset($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : '';

// Validate required fields
if (empty($first_name)) {
    $errors[] = "First name is required";
}
if (empty($last_name)) {
    $errors[] = "Last name is required";
}
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}
if (empty($phone)) {
    $errors[] = "Phone number is required";
}
if (empty($date_of_birth)) {
    $errors[] = "Date of birth is required";
}
if (empty($gender)) {
    $errors[] = "Gender is required";
}
if (empty($address)) {
    $errors[] = "Address is required";
}
if ($height <= 0) {
    $errors[] = "Valid height is required";
}
if ($weight <= 0) {
    $errors[] = "Valid weight is required";
}
if (empty($emergency_contact_name)) {
    $errors[] = "Emergency contact name is required";
}
if (empty($emergency_contact_phone)) {
    $errors[] = "Emergency contact phone is required";
}

// Check if email already exists
if (!empty($email)) {
    $email_check = $conn->real_escape_string($email);
    $emailCheck = $conn->query("SELECT member_id FROM walkin_members WHERE email = '$email_check'");
    if ($emailCheck && $emailCheck->num_rows > 0) {
        $errors[] = "Email address is already registered";
    }
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode([
        "success" => false,
        "message" => "Validation failed",
        "errors" => $errors
    ]);
    exit;
}

// Generate new member ID
$member_id = generateMemberID($conn);

// Calculate BMI
$height_in_meters = $height / 100;
$bmi = round($weight / ($height_in_meters * $height_in_meters), 1);

// Escape all data for SQL
$member_id_esc = $conn->real_escape_string($member_id);
$first_name_esc = $conn->real_escape_string($first_name);
$last_name_esc = $conn->real_escape_string($last_name);
$email_esc = $conn->real_escape_string($email);
$phone_esc = $conn->real_escape_string($phone);
$date_of_birth_esc = $conn->real_escape_string($date_of_birth);
$gender_esc = $conn->real_escape_string($gender);
$address_esc = $conn->real_escape_string($address);
$payment_method_esc = $conn->real_escape_string($payment_method);
$emergency_contact_name_esc = $conn->real_escape_string($emergency_contact_name);
$emergency_contact_phone_esc = $conn->real_escape_string($emergency_contact_phone);

// Build SQL query
$sql = "INSERT INTO walkin_members (
    member_id,
    first_name, 
    last_name, 
    email, 
    phone, 
    date_of_birth, 
    gender, 
    address, 
    height, 
    weight, 
    bmi, 
    payment_method, 
    emergency_contact_name, 
    emergency_contact_phone,
    status
) VALUES (
    '$member_id_esc',
    '$first_name_esc',
    '$last_name_esc',
    '$email_esc',
    '$phone_esc',
    '$date_of_birth_esc',
    '$gender_esc',
    '$address_esc',
    $height,
    $weight,
    $bmi,
    '$payment_method_esc',
    '$emergency_contact_name_esc',
    '$emergency_contact_phone_esc',
    'pending'
)";

// Execute query
if ($conn->query($sql) === TRUE) {
    echo json_encode([
        "success" => true,
        "message" => "Registration completed successfully! Welcome to G14 Fitness Centre.",
        "member_id" => $member_id,
        "data" => [
            "name" => $first_name . " " . $last_name,
            "email" => $email,
            "bmi" => $bmi
        ]
    ]);
} else {
    // Return the actual error for debugging
    echo json_encode([
        "success" => false,
        "message" => "Registration failed: " . $conn->error,
        "sql_error" => $conn->error
    ]);
}

$conn->close();
?>