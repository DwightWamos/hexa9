<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: main.php");
    exit();
}

// Get user info from session
$employee_name = $_SESSION['name'] ?? 'Unknown User';
$employee_role = $_SESSION['role'] ?? 'employee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>G14 Fitness Centre</title>
  <link rel="stylesheet" href="menu-style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  <div class="sidebar">
    <div class="sidebar-header">
      <a href="Menu.php" style="text-decoration: none;">
  <h1 style="
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background-color: none;
      color: black;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
      font-family: Arial, sans-serif;">
    
    <i class="fas fa-dumbbell"></i> G14 FITNESS CENTRE
  </h1>
</a>

<!-- Font Awesome (if not already included) -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
      <div class="employee-info">
        <span id="employee-name"><?php echo htmlspecialchars($employee_name); ?></span> 
        <small>(<span id="employee-role"><?php echo htmlspecialchars(ucfirst($employee_role)); ?></span>)</small>
      </div>
    </div>

    <nav>
      <ul>
        <!-- Membership Manager  -->
        <li>
          <a href="#"><i class="fas fa-id-card"></i> Membership Manager</a>
          <ul class="dropdown">
            <li><a href="#" id="show-members">Member List &amp; Status</a></li>
            <li><a href="#" id="show-registration">New Member Registration</a></li>
            <li><a href="#" id="show-renewal">Membership Renewal</a></li>
            <li><a href="#" id="show-renewal-log"><i class="fas fa-history"></i> Renewal Log</a></li>
          </ul>
        </li>
        <li>
          <a href="#"><i class="fas fa-calendar-check"></i> Attendance Log</a>
          <ul class="dropdown">
              <li><a href="#" id="show-today-attendance">Today's Attendance Log</a></li>
              <li><a href="#" id="show-attendance-history">Attendance Records</a></li>
          </ul>
        </li>
        <li><a href="#" id="show-memberlogin"><i class="fas fa-user"></i> Member Check-in / out</a></li>
        <li><a href="#" id="show-gymstore"><i class="fas fa-dumbbell"></i> Gym Store Manager</a></li>
        <li><a href="#" id="show-reportstatus"><i class="fas fa-chart-line"></i> Report Status</a></li>
        <li><a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </nav>
  </div>

  <div class="main-content">
    <div class="menu-toggle" onclick="toggleMenu()">
      ☰ Menu
    </div>
    
    <!-- Content area for iframe -->
    <div class="content-area">
      <!-- Welcome Content (default) -->
      <div class="welcome-content active" id="welcome-content">
        <h2>Welcome, <?php echo htmlspecialchars($employee_name); ?>!</h2>
        <p>You are logged in as: <?php echo htmlspecialchars(ucfirst($employee_role)); ?></p>
        <p>Please select an option from the menu to get started.</p>
      </div>
      
      <!-- Iframe for external content -->
      <iframe id="content-frame" class="content-frame" style="display: none;"></iframe>
    </div>
  </div>

  <script src="menu.js"></script>
</body>
</html>