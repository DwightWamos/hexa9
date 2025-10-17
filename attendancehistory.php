<?php
$mysqli = new mysqli("localhost", "root", "", "g14");
if ($mysqli->connect_errno) {
    die("DB connection failed: " . $mysqli->connect_error);
}

// Handle AJAX request for fetching attendance by date
if (isset($_GET['fetch_date'])) {
    $selected_date = $_GET['fetch_date'];
    
    // Pagination settings
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);
    $offset = ($page - 1) * $records_per_page;
    
    // Get total records for selected date
    $count_stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM memattendance_tbl WHERE date = ?");
    $count_stmt->bind_param("s", $selected_date);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $records_per_page);
    
    // Fetch records for selected date with pagination
    $stmt = $mysqli->prepare("SELECT * FROM memattendance_tbl WHERE date = ? ORDER BY time DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $selected_date, $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode([
        'records' => $data,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance History</title>
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
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    body::before,
    body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: 0.1;
      z-index: 0;
    }

    body::before {
      width: 500px;
      height: 500px;
      top: -250px;
      left: -250px;
    }

    body::after {
      width: 400px;
      height: 400px;
      background:none;
      bottom: -200px;
      right: -200px;
    }

    .container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 1200px;
    }

    .attendance-box {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      opacity: 0;
      transform: translateY(20px);
      transition: all 0.5s ease;
    }

    .attendance-box.show {
      opacity: 1;
      transform: translateY(0);
    }

    h2 {
      text-align: center;
      color: #333;
      font-size: 1.8em;
      margin-bottom: 30px;
      font-weight: 600;
    }

    .date-picker {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 15px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }

    .date-picker label {
      font-size: 1.1em;
      font-weight: 500;
      color: #333;
    }

    .date-picker input[type="date"] {
      padding: 12px 20px;
      font-size: 1em;
      border: 2px solid #ffd89b;
      border-radius: 10px;
      background: white;
      color: #333;
      cursor: pointer;
      transition: all 0.3s ease;
      outline: none;
    }

    .date-picker input[type="date"]:hover {
      border-color: #f5cd79;
      box-shadow: 0 4px 12px rgba(255, 216, 155, 0.3);
    }

    .date-picker input[type="date"]:focus {
      border-color: #f5cd79;
      box-shadow: 0 4px 12px rgba(255, 216, 155, 0.5);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    thead {
      background: linear-gradient(135deg, #ffd89b 0%, #f5cd79 100%);
    }

    thead th {
      padding: 18px;
      text-align: center;
      font-weight: 600;
      color: #333;
      font-size: 1em;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tbody tr {
      border-bottom: 1px solid #f0f0f0;
      transition: background 0.3s ease;
    }

    tbody tr:hover {
      background: #fffef5;
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    tbody td {
      padding: 16px;
      text-align: center;
      color: #555;
      font-size: 0.95em;
    }

    tbody td:nth-child(2) {
      font-weight: 600;
      color: #667eea;
    }

    tbody td:nth-child(3) {
      font-weight: 500;
      color: #333;
    }

    tbody td:nth-child(4) {
      font-weight: 500;
    }

    .check-in {
      color: #28a745;
    }

    .check-out {
      color: #dc3545;
    }

    .no-records {
      text-align: center;
      padding: 40px;
      color: #999;
      font-style: italic;
    }

    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 40px;
      height: 40px;
      padding: 0 12px;
      background: white;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      color: #666;
      text-decoration: none;
    }

    .pagination a:hover {
      background: #f5f5f5;
      border-color: #ffd89b;
    }

    .pagination span.active {
      background: linear-gradient(135deg, #ffd89b 0%, #f5cd79 100%);
      border-color: #ffd89b;
      color: #333;
      font-weight: 600;
      cursor: default;
    }

    .pagination span.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .record-info {
      text-align: center;
      margin-top: 20px;
      color: #666;
      font-size: 0.9em;
    }

    .loading {
      text-align: center;
      padding: 40px;
      color: #666;
      font-style: italic;
    }

    @media (max-width: 768px) {
      .attendance-box {
        padding: 20px;
      }

      h2 {
        font-size: 1.4em;
      }

      .date-picker {
        flex-direction: column;
        gap: 10px;
      }

      table {
        font-size: 0.85em;
      }

      thead th,
      tbody td {
        padding: 12px 8px;
      }

      .pagination a,
      .pagination span {
        min-width: 35px;
        height: 35px;
        font-size: 0.9em;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <main class="attendance-box">
      <h2>Attendance History</h2>

      <div class="date-picker">
        <label for="date">Select Date: </label>
        <input type="date" id="date" name="date">
      </div>

      <table id="historyTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Member ID</th>
            <th>Name</th>
            <th>Check in / out</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="5" class="no-records">Please select a date.</td>
          </tr>
        </tbody>
      </table>

      <div id="recordInfo" class="record-info" style="display: none;"></div>
      <div id="paginationContainer" class="pagination" style="display: none;"></div>
    </main>
  </div>

  <script>
    let currentDate = '';
    let currentPage = 1;

    const dateInput = document.getElementById("date");
    const tableBody = document.querySelector("#historyTable tbody");
    const recordInfo = document.getElementById("recordInfo");
    const paginationContainer = document.getElementById("paginationContainer");

    // Function to fetch attendance data
    async function fetchAttendance(date, page = 1) {
      try {
        tableBody.innerHTML = '<tr><td colspan="5" class="loading">Loading...</td></tr>';
        recordInfo.style.display = 'none';
        paginationContainer.style.display = 'none';

        const response = await fetch(`attendancehistory.php?fetch_date=${date}&page=${page}`);
        const data = await response.json();

        tableBody.innerHTML = "";

        if (data.records && data.records.length > 0) {
          data.records.forEach(record => {
            const actionClass = record.action.toLowerCase().replace(' ', '-');
            const row = `
              <tr>
                <td>${record.date}</td>
                <td>${record.member_id}</td>
                <td>${record.name}</td>
                <td class="${actionClass}">${record.action}</td>
                <td>${formatTime(record.time)}</td>
              </tr>
            `;
            tableBody.innerHTML += row;
          });

          // Show record info
          const start = (page - 1) * 10 + 1;
          const end = Math.min(page * 10, data.total_records);
          recordInfo.textContent = `Showing ${start} to ${end} of ${data.total_records} records`;
          recordInfo.style.display = 'block';

          // Show pagination if needed
          if (data.total_pages > 1) {
            renderPagination(data.current_page, data.total_pages);
            paginationContainer.style.display = 'flex';
          }
        } else {
          tableBody.innerHTML = `
            <tr>
              <td colspan="5" class="no-records">No records found for this date.</td>
            </tr>
          `;
        }
      } catch (error) {
        console.error('Error fetching data:', error);
        tableBody.innerHTML = `
          <tr>
            <td colspan="5" class="no-records">Error loading data. Please try again.</td>
          </tr>
        `;
      }
    }

    // Function to format time
    function formatTime(timeStr) {
      const time = new Date('2000-01-01 ' + timeStr);
      return time.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
      });
    }

    // Function to render pagination
    function renderPagination(currentPage, totalPages) {
      let html = '';

      // Previous button
      if (currentPage > 1) {
        html += `<a href="#" onclick="changePage(${currentPage - 1}); return false;">«</a>`;
      } else {
        html += '<span class="disabled">«</span>';
      }

      // First page
      if (currentPage > 3) {
        html += `<a href="#" onclick="changePage(1); return false;">1</a>`;
        if (currentPage > 4) {
          html += '<span class="disabled">...</span>';
        }
      }

      // Pages around current page
      const start = Math.max(1, currentPage - 2);
      const end = Math.min(totalPages, currentPage + 2);

      for (let i = start; i <= end; i++) {
        if (i === currentPage) {
          html += `<span class="active">${i}</span>`;
        } else {
          html += `<a href="#" onclick="changePage(${i}); return false;">${i}</a>`;
        }
      }

      // Last page
      if (currentPage < totalPages - 2) {
        if (currentPage < totalPages - 3) {
          html += '<span class="disabled">...</span>';
        }
        html += `<a href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>`;
      }

      // Next button
      if (currentPage < totalPages) {
        html += `<a href="#" onclick="changePage(${currentPage + 1}); return false;">»</a>`;
      } else {
        html += '<span class="disabled">»</span>';
      }

      paginationContainer.innerHTML = html;
    }

    // Function to change page
    function changePage(page) {
      currentPage = page;
      if (currentDate) {
        fetchAttendance(currentDate, page);
      }
    }

    // Make changePage available globally
    window.changePage = changePage;

    // Date input change event
    dateInput.addEventListener("change", () => {
      currentDate = dateInput.value;
      currentPage = 1;
      if (currentDate) {
        fetchAttendance(currentDate, 1);
      }
    });

    // Show animation on load
    window.addEventListener("DOMContentLoaded", () => {
      const box = document.querySelector(".attendance-box");
      setTimeout(() => {
        box.classList.add("show");
      }, 50);
    });
  </script>
</body>
</html>