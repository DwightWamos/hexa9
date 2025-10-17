<?php
// mainSer.php
// Handles login using employee_tbl table with hashed passwords

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DB CONNECTION (XAMPP defaults: root / no password) ---
$mysqli = @new mysqli('localhost', 'root', '', 'g14');
if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Helper to clean input
function clean($v) {
    return trim($v ?? '');
}

// 🔧 AUTO-SETUP: Fix password column size and create employee (REMOVE THIS AFTER FIRST LOGIN!)
// First, make sure password column is large enough for password_hash()
$mysqli->query("ALTER TABLE employee_tbl MODIFY COLUMN password VARCHAR(255)");

// Delete existing E001 if exists (to recreate with proper hash
// ---------------------- LOGIN ----------------------
if (isset($_POST['login_user'])) {
    $employee_id = clean($_POST['employee_id']);
    $password    = clean($_POST['password']);

    $errors = [];

    if (empty($employee_id)) { $errors[] = "Employee ID is required"; }
    if (empty($password))    { $errors[] = "Password is required"; }

    if (count($errors) === 0) {
        // Get user by employee_id only
        $stmt = $mysqli->prepare("SELECT * FROM employee_tbl WHERE employee_id = ?");
        if (!$stmt) {
            $_SESSION['errors'][] = "Database error occurred";
            header("Location: main.php");
            exit();
        }
        
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // ✅ Verify password using password_verify()
            if (password_verify($password, $user['password'])) {
                // ✅ Password is correct!
                
                // Save session variables
                $_SESSION['employee_id'] = $user['employee_id'];
                
                // Get the name from the database
                if (isset($user['name'])) {
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['employee_name'] = $user['name'];
                } elseif (isset($user['employee_name'])) {
                    $_SESSION['name'] = $user['employee_name'];
                    $_SESSION['employee_name'] = $user['employee_name'];
                } elseif (isset($user['full_name'])) {
                    $_SESSION['name'] = $user['full_name'];
                    $_SESSION['employee_name'] = $user['full_name'];
                } else {
                    $_SESSION['name'] = $user['employee_id'];
                    $_SESSION['employee_name'] = $user['employee_id'];
                }
                
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: Menu.php");
                }
                exit();
            } else {
                // ❌ Password verification failed
                $_SESSION['errors'][] = "Invalid Employee ID or Password";
                header("Location: main.php");
                exit();
            }
        } else {
            // ❌ No user found with that employee_id
            $_SESSION['errors'][] = "Invalid Employee ID or Password";
            header("Location: main.php");
            exit();
        }
    } else {
        // ❌ Validation errors (missing fields)
        $_SESSION['errors'] = $errors;
        header("Location: main.php");
        exit();
    }
} else {
    // If someone loads this file directly, redirect back
    header("Location: main.php");
    exit();
}