<?php
session_start();

$current_employee = isset($_SESSION['employee_name']) ? $_SESSION['employee_name'] : 'Guest';

$host = "localhost";
$user = "root";
$pass = "";
$db   = "g14";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Auto update status based on expiry and renewal history
// Inactive = no renewal for 5+ months (150 days)
$current_date = date('Y-m-d');
$inactive_threshold = date('Y-m-d', strtotime('-5 months'));

$update_sql = "
    UPDATE members_tbl
    SET status = CASE
        WHEN is_deleted = 1 THEN 'Deleted'
        WHEN expiry_date < '$current_date' AND registration_date < '$inactive_threshold' THEN 'Inactive'
        WHEN expiry_date < '$current_date' THEN 'Expired'
        ELSE 'Active'
    END
";
$conn->query($update_sql);

// Fetch updated member list
$members = [];
$sql = "SELECT * FROM members_tbl ORDER BY member_id ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Members List</title>
  <link rel="stylesheet" href="members.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
  
  <div class="table-container">
    <div class="table-header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search members by name, ID, or contact...">
      </div>

      <div class="filter-options">
        <select id="sortFilter">
          <option value="all">All Members</option>
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="inactive">Inactive</option>
          <option value="deleted">Deleted</option>
          <option value="student">Student</option>
          <option value="premium">Premium</option>
          <option value="standard">Standard</option>
        </select>
      </div>
    </div>
    
    <table class="members-table">
      <thead>
        <tr>
          <th>Member ID</th>
          <th>Name</th>
          <th>Contact</th>
          <th>Membership Type</th>
          <th>Join Date</th>
          <th>Expiry Date</th>
          <th>Status</th>
          <th>QR Code</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php
        if (empty($members)) {
          echo '<tr><td colspan="9" style="text-align: center; padding: 20px; color: #666;">No members registered yet.</td></tr>';
        } else {
          foreach ($members as $member) {
            $full_name = htmlspecialchars($member['first_name'] . ' ' . $member['last_name']);
            $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
            $join_date = date('Y-m-d', strtotime($member['registration_date']));
            $expiry_date_str = $member['expiry_date'];
            $expiry_date = date('Y-m-d', strtotime($expiry_date_str));
            $current_date = date('Y-m-d');
            $status_text = ucfirst($member['status']);
            $status_class = strtolower($member['status']);

            $member_id = htmlspecialchars($member['member_id']);
            $phone = htmlspecialchars($member['phone']);
            
            $registered_by = !empty($member['registered_by']) ? htmlspecialchars($member['registered_by']) : 'Unknown Staff';
            
            $is_deleted = isset($member['is_deleted']) && $member['is_deleted'] == 1;
        ?>

        <tr class="member-row" data-member-id="<?php echo $member['member_id']; ?>">
          <td><span class="member-id"><?php echo $member_id; ?></span></td>
          <td class="clickable-name">
            <div class="member-info">
              <div class="member-avatar"><?php echo $initials; ?></div>
              <span><?php echo $full_name; ?></span>
            </div>
          </td>
          <td><?php echo $phone; ?></td>
          <td><?php echo ucfirst($member['membership_type']); ?></td>
          <td><?php echo $join_date; ?></td>
          <td><?php echo $expiry_date; ?></td>
          <td><span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
          <td>
            <button class="btn-sendqr" onclick="sendMemberQR('<?php echo $member['member_id']; ?>')">
              <i class="fa-solid fa-qrcode"></i> View QR
            </button>
          </td>
          <td>
            <div class="action-buttons">
              <?php if ($is_deleted): ?>
                <button class="btn-restore" onclick="restoreMember('<?php echo $member['member_id']; ?>', '<?php echo addslashes($full_name); ?>')">
                  <i class="fa-solid fa-undo"></i> Restore
                </button>
              <?php else: ?>
                <button class="btn-delete" onclick="deleteMember('<?php echo $member['member_id']; ?>', '<?php echo addslashes($full_name); ?>')">
                  <i class="fa-solid fa-trash-alt"></i> Delete
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>

        <tr class="details-row" style="display:none;">
          <td colspan="9">
            <div class="details-box">
              <p><strong>Gender:</strong> <?php echo htmlspecialchars($member['gender']); ?></p>
              <p><strong>Date of Birth:</strong> 
                <?php echo !empty($member['date_of_birth']) ? htmlspecialchars($member['date_of_birth']) : 'N/A'; ?>
              </p>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></p>
              <p><strong>Height:</strong> 
                <?php echo !empty($member['height']) ? htmlspecialchars($member['height']) . ' cm' : 'N/A'; ?>
              </p>
              <p><strong>Weight:</strong> 
                <?php echo !empty($member['weight']) ? htmlspecialchars($member['weight']) . ' kg' : 'N/A'; ?>
              </p>
              <p><strong>Membership Type:</strong> <?php echo htmlspecialchars($member['membership_type']); ?></p>
              <p><strong>Emergency Contact:</strong> 
                <?php 
                    if (!empty($member['emergency_contact_name']) && !empty($member['emergency_contact_phone'])) {
                        echo htmlspecialchars($member['emergency_contact_name']) . ' (' . htmlspecialchars($member['emergency_contact_phone']) . ')';
                    } else {
                        echo 'N/A';
                    }
                ?>
              </p>
              <p><strong>Registered By:</strong> <span style="color: #667eea; font-weight: 600;"><?php echo $registered_by; ?></span></p>
            </div>
          </td>
        </tr>

        <?php
          }
        }
        ?>
      </tbody>
    </table>
  </div>

  <style>
    .action-buttons {
      display: flex;
      gap: 8px;
    }
    .btn-restore {
      background: #4caf50;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      transition: background 0.3s;
    }
    .btn-restore:hover {
      background: #45a049;
    }
  </style>

  <script>
    window.addEventListener("DOMContentLoaded", () => {
      document.querySelector(".table-container").classList.add("show");
    });

    document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll(".clickable-name").forEach(cell => {
        cell.addEventListener("click", () => {
          const detailsRow = cell.closest("tr").nextElementSibling;
          if (detailsRow && detailsRow.classList.contains("details-row")) {
            if (detailsRow.style.display === "table-row") {
              detailsRow.classList.remove("open");
              setTimeout(() => detailsRow.style.display = "none", 300);
            } else {
              detailsRow.style.display = "table-row";
              setTimeout(() => detailsRow.classList.add("open"), 10);
            }
          }
        });
      });
    });

    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById("searchInput");
      const sortFilter = document.getElementById("sortFilter");
      const rows = document.querySelectorAll(".member-row");

      function filterMembers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedFilter = sortFilter.value.toLowerCase();

        rows.forEach(row => {
          const nextRow = row.nextElementSibling;
          const name = row.querySelector(".member-info span").textContent.toLowerCase();
          const id = row.querySelector(".member-id").textContent.toLowerCase();
          const contact = row.children[2].textContent.toLowerCase();
          const type = row.children[3].textContent.toLowerCase();
          const status = row.querySelector(".status").textContent.toLowerCase();

          let matchesSearch = name.includes(searchTerm) || id.includes(searchTerm) || contact.includes(searchTerm);
          let matchesFilter =
            selectedFilter === "all" ||
            (selectedFilter === "active" && status === "active") ||
            (selectedFilter === "expired" && status === "expired") ||
            (selectedFilter === "inactive" && status === "inactive") ||
            (selectedFilter === "deleted" && status === "deleted") ||
            (selectedFilter === "student" && type.includes("student")) ||
            (selectedFilter === "premium" && type.includes("premium")) ||
            (selectedFilter === "standard" && type.includes("standard"));

          if (matchesSearch && matchesFilter) {
            row.style.display = "";
            if (nextRow && nextRow.classList.contains("details-row")) nextRow.style.display = "none";
          } else {
            row.style.display = "none";
            if (nextRow && nextRow.classList.contains("details-row")) nextRow.style.display = "none";
          }
        });
      }

      searchInput.addEventListener("input", filterMembers);
      sortFilter.addEventListener("change", filterMembers);
    });

    async function sendMemberQR(memberId) {
      try {
        const res = await fetch('membershipSer.php?action=sendMemberQR', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ member_id: memberId })
        });

        const data = await res.json();

        if (data.status === "success") {
          const qrModal = document.createElement('div');
          qrModal.classList.add('qr-modal');
          qrModal.innerHTML = `
            <div class="qr-modal-content">
              <h3>QR Code for Member ID: ${memberId}</h3>
              <img src="${data.qr_path}?t=${Date.now()}" alt="QR Code" id="qr-image-${memberId}">
              <div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">
                <a href="${data.qr_path}" download="QR_${memberId}.png" class="btn-downloadqr">
                  <i class="fa-solid fa-download"></i> Download
                </a>
                <button onclick="this.closest('.qr-modal').remove()" class="btn-closeqr">
                  <i class="fa-solid fa-times"></i> Close
                </button>
              </div>
              <p style="margin-top: 15px; font-size: 13px; color: #666;">${data.message}</p>
            </div>
          `;
          document.body.appendChild(qrModal);

          qrModal.addEventListener('click', (e) => {
            if (e.target === qrModal) {
              qrModal.remove();
            }
          });
        } else {
          alert('Error: ' + data.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to load QR code. Please try again.');
      }
    }

    async function deleteMember(memberId, memberName) {
      if (!confirm(`Are you sure you want to delete member "${memberName}" (ID: ${memberId})?\n\nThis will mark them as deleted but they will still appear in the members list.`)) {
        return;
      }

      try {
        const res = await fetch('membershipSer.php?action=deleteMember', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ member_id: memberId })
        });

        const data = await res.json();

        if (data.status === "success") {
          alert(data.message);
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete member. Please try again.');
      }
    }

    async function restoreMember(memberId, memberName) {
      if (!confirm(`Are you sure you want to restore member "${memberName}" (ID: ${memberId})?\n\nThis will mark them as Active and they can check in/out and renew their membership.`)) {
        return;
      }

      try {
        const res = await fetch('membershipSer.php?action=restoreMember', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ member_id: memberId })
        });

        const data = await res.json();

        if (data.status === "success") {
          alert(data.message);
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Failed to restore member. Please try again.');
      }
    }
  </script>

</body>
</html>