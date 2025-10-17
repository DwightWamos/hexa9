<?php
session_start();

// Session validation with permission check
if (!isset($_SESSION['employee_id'])) {
    header("Location: main.php");
    exit();
}

// Verify employee role has access to renewal log
$allowed_roles = ['admin', 'staff']; // Adjust based on your roles
if (!in_array($_SESSION['role'] ?? 'guest', $allowed_roles)) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "g14";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}

// Initialize filter variables
$filter_member_id = '';
$filter_date_from = '';
$filter_date_to = '';
$renewals = [];

try {
    // Get and validate filter parameters
    $filter_member_id = isset($_GET['member_id']) ? trim($_GET['member_id']) : '';
    $filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    
    // Validate date formats
    if (!empty($filter_date_from)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_from)) {
            throw new Exception("Invalid date format for date_from");
        }
    }
    
    if (!empty($filter_date_to)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date_to)) {
            throw new Exception("Invalid date format for date_to");
        }
    }
    
    // Validate member_id length
    if (strlen($filter_member_id) > 50) {
        throw new Exception("Search term too long");
    }
    
    // Build query with prepared statements
    $sql = "SELECT 
                rl.id, rl.member_id, rl.first_name, rl.last_name, rl.email, 
                rl.old_expiry_date, rl.new_expiry_date, rl.membership_type, 
                rl.payment_method, rl.height, rl.weight, rl.bmi, rl.renewed_by, 
                rl.renewal_date
            FROM renewal_log rl
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add member_id filter with prepared statement
    if (!empty($filter_member_id)) {
        $sql .= " AND (rl.member_id LIKE ? OR rl.first_name LIKE ? OR rl.last_name LIKE ?)";
        $search_term = "%{$filter_member_id}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sss";
    }
    
    // Add date_from filter with prepared statement
    if (!empty($filter_date_from)) {
        $sql .= " AND DATE(rl.renewal_date) >= ?";
        $params[] = $filter_date_from;
        $types .= "s";
    }
    
    // Add date_to filter with prepared statement
    if (!empty($filter_date_to)) {
        $sql .= " AND DATE(rl.renewal_date) <= ?";
        $params[] = $filter_date_to;
        $types .= "s";
    }
    
    $sql .= " ORDER BY rl.renewal_date DESC";
    
    // Execute query with prepared statement
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters if any exist
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $renewals[] = $row;
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Query Error: " . $e->getMessage());
    $error_message = "An error occurred while retrieving renewal records.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Renewal Log</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: none;
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      overflow: hidden;
    }

    .header {
      background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
      color: white;
      padding: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h1 {
      font-size: 2em;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header i {
      font-size: 2.2em;
    }

    .error-message {
      background: #ffe5e5;
      color: #d32f2f;
      padding: 15px;
      margin: 20px;
      border-radius: 8px;
      border-left: 4px solid #d32f2f;
    }

    .filter-section {
      padding: 25px 30px;
      background: #f8f9fa;
      border-bottom: 2px solid #e9ecef;
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .filter-group label {
      font-weight: 600;
      color: #2c3e50;
      font-size: 14px;
    }

    .filter-group input {
      padding: 10px 15px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .filter-group input:focus {
      border-color: #667eea;
      box-shadow: 0 0 8px rgba(102, 126, 234, 0.2);
      outline: none;
    }

    .filter-buttons {
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-search {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-search:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-reset {
      background: #95a5a6;
      color: white;
    }

    .btn-reset:hover {
      background: #7f8c8d;
    }

    .table-wrapper {
      overflow-x: auto;
      padding: 20px 30px;
    }

    .renewal-table {
      width: 100%;
      border-collapse: collapse;
    }

    .renewal-table thead {
      background: linear-gradient(135deg, #2c3e50, #34495e);
      color: white;
      position: sticky;
      top: 0;
    }

    .renewal-table th {
      padding: 18px 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 2px solid #34495e;
    }

    .renewal-table td {
      padding: 16px 15px;
      border-bottom: 1px solid #ecf0f1;
      color: #2c3e50;
    }

    .renewal-table tbody tr {
      transition: all 0.3s ease;
    }

    .renewal-table tbody tr:hover {
      background: linear-gradient(135deg, #f2ec75, #eff0db);
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .member-badge {
      display: inline-block;
      padding: 6px 12px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border-radius: 15px;
      font-weight: 600;
      font-size: 12px;
    }

    .status-badge {
      display: inline-block;
      padding: 6px 12px;
      background: #e8f5e9;
      color: #2e7d32;
      border-radius: 15px;
      font-weight: 600;
      font-size: 12px;
      border-left: 3px solid #2e7d32;
    }

    .date-renewed {
      font-size: 13px;
      color: #666;
    }

    .empty-state {
      text-align: center;
      padding: 60px 30px;
      color: #7f8c8d;
    }

    .empty-state i {
      font-size: 4em;
      margin-bottom: 20px;
      opacity: 0.3;
      display: block;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      padding: 25px 30px;
      background: #f8f9fa;
      border-top: 2px solid #e9ecef;
    }

    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .stat-card h3 {
      font-size: 2.5em;
      color: #667eea;
      margin-bottom: 8px;
    }

    .stat-card p {
      color: #7f8c8d;
      font-weight: 600;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        text-align: center;
      }

      .filter-section {
        flex-direction: column;
        gap: 12px;
      }

      .filter-group input {
        width: 100%;
      }

      .filter-buttons {
        width: 100%;
      }

      .filter-buttons .btn {
        flex: 1;
        justify-content: center;
      }

      .stats {
        grid-template-columns: 1fr;
      }

      .renewal-table {
        font-size: 12px;
      }

      .renewal-table th,
      .renewal-table td {
        padding: 10px 8px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>
        <i class="fas fa-sync-alt"></i> Renewal Log
      </h1>
      <p style="font-size: 14px;">Track all member renewal history</p>
    </div>

    <?php if (isset($error_message)): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <div class="filter-section">
      <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
        <div class="filter-group">
          <label for="member_id">Member ID / Name:</label>
          <input type="text" id="member_id" name="member_id" placeholder="Search by ID or name" value="<?php echo htmlspecialchars($filter_member_id); ?>" maxlength="50">
        </div>

        <div class="filter-group">
          <label for="date_from">Date From:</label>
          <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
        </div>

        <div class="filter-group">
          <label for="date_to">Date To:</label>
          <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
        </div>

        <div class="filter-buttons">
          <button type="submit" class="btn btn-search">
            <i class="fas fa-search"></i> Search
          </button>
          <a href="renewal_log.php" class="btn btn-reset">
            <i class="fas fa-redo"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <div class="table-wrapper">
      <?php if (empty($renewals)): ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <h3>No Renewal Records Found</h3>
          <p>No membership renewals match your search criteria.</p>
        </div>
      <?php else: ?>
        <table class="renewal-table">
          <thead>
            <tr>
              <th>Member ID</th>
              <th>Member Name</th>
              <th>Email</th>
              <th>Old Expiry</th>
              <th>New Expiry</th>
              <th>Membership Type</th>
              <th>Payment</th>
              <th>BMI</th>
              <th>Renewed By</th>
              <th>Renewal Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($renewals as $renewal): ?>
              <tr>
                <td>
                  <span class="member-badge"><?php echo htmlspecialchars($renewal['member_id']); ?></span>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($renewal['first_name'] . ' ' . $renewal['last_name']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($renewal['email']); ?></td>
                <td>
                  <?php echo $renewal['old_expiry_date'] ? htmlspecialchars(date('M d, Y', strtotime($renewal['old_expiry_date']))) : 'N/A'; ?>
                </td>
                <td>
                  <span class="status-badge">
                    <?php echo htmlspecialchars(date('M d, Y', strtotime($renewal['new_expiry_date']))); ?>
                  </span>
                </td>
                <td><?php echo htmlspecialchars(ucfirst($renewal['membership_type'])); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($renewal['payment_method'])); ?></td>
                <td>
                  <?php 
                    if ($renewal['bmi']) {
                      echo htmlspecialchars(number_format($renewal['bmi'], 1));
                    } else {
                      echo 'N/A';
                    }
                  ?>
                </td>
                <td><?php echo htmlspecialchars($renewal['renewed_by']); ?></td>
                <td>
                  <div class="date-renewed">
                    <?php echo htmlspecialchars(date('M d, Y', strtotime($renewal['renewal_date']))); ?>
                  </div>
                  <small><?php echo htmlspecialchars(date('H:i', strtotime($renewal['renewal_date']))); ?></small>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <?php if (!empty($renewals)): ?>
      <div class="stats">
        <div class="stat-card">
          <h3><?php echo count($renewals); ?></h3>
          <p>Total Renewals</p>
        </div>
        <div class="stat-card">
          <h3><?php echo count(array_unique(array_column($renewals, 'member_id'))); ?></h3>
          <p>Unique Members</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>