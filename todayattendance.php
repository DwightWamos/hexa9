<?php
// =======================
// Attendance Log (Auto Reset Daily)
// =======================

$mysqli = new mysqli("localhost", "root", "", "g14");
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}

// Ensure consistent timezone
date_default_timezone_set('Asia/Manila');

// Pagination setup
$records_per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

// Count unique members checked in today
$count_sql = "SELECT COUNT(DISTINCT member_id) AS total 
              FROM memattendance_tbl 
              WHERE date = CURDATE()";
$total_records = $mysqli->query($count_sql)->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_records / $records_per_page));

// Fetch today's attendance summary
$sql = "
SELECT 
    member_id,
    name,
    date,
    MIN(CASE WHEN action = 'Check In' THEN time END) AS check_in_time,
    MAX(CASE WHEN action = 'Check Out' THEN time END) AS check_out_time
FROM memattendance_tbl
WHERE date = CURDATE()
GROUP BY member_id, name, date
ORDER BY name ASC
LIMIT $offset, $records_per_page
";
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Today's Attendance Log</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{
      font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
      background:none;
      min-height:100vh;
      display:flex;justify-content:center;align-items:center;
      padding:20px;
    }
    .container{max-width:1200px;width:100%;}
    .attendance-box{
      background:#fff;
      border-radius:20px;
      padding:40px;
      box-shadow:0 10px 40px rgba(0,0,0,0.1);
    }
    h2{text-align:center;margin-bottom:25px;}
    table{width:100%;border-collapse:collapse;border-radius:12px;overflow:hidden;}
    thead{background:linear-gradient(135deg,#ffd86f,#fcab64);}
    thead th{padding:16px;text-align:center;text-transform:uppercase;}
    tbody td{padding:12px;text-align:center;border-bottom:1px solid #eee;}
    tbody tr:hover{background: none;}
    .pagination{
      display:flex;justify-content:center;gap:8px;margin-top:20px;
    }
    .pagination a,.pagination span{
      min-width:38px;height:38px;display:flex;align-items:center;justify-content:center;
      border:2px solid #e0e0e0;border-radius:6px;background:white;color:#555;text-decoration:none;
    }
    .pagination span.active{
      background:linear-gradient(135deg,#ffd86f,#fcab64);
      border-color:#ffd86f;font-weight:bold;color:#333;
    }
    .no-records{text-align:center;padding:30px;color:#999;}
  </style>
</head>
<body>
<div class="container">
  <main class="attendance-box">
    <h2>Today's Attendance Log (<?php echo date("F d, Y (l)"); ?>)</h2>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Member ID</th>
          <th>Name</th>
          <th>Check-In</th>
          <th>Check-Out</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['date']) ?></td>
              <td><?= htmlspecialchars($row['member_id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= $row['check_in_time'] ? date("h:i A", strtotime($row['check_in_time'])) : '-' ?></td>
              <td><?= $row['check_out_time'] ? date("h:i A", strtotime($row['check_out_time'])) : '-' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" class="no-records">No check-ins yet today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($total_records > 0): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>">«</a>
      <?php else: ?><span class="disabled">«</span><?php endif; ?>

      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php if ($i == $page): ?>
          <span class="active"><?= $i ?></span>
        <?php else: ?>
          <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>">»</a>
      <?php else: ?><span class="disabled">»</span><?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
// Auto-refresh every 30 seconds
setInterval(() => location.reload(), 30000);
</script>
</body>
</html>
