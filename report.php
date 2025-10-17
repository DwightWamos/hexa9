<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gym Equipment Reporting System</title>
  <link rel="stylesheet" href="report.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>Gym Equipment Reporting System</h1>
      <p class="subtitle">Report and track equipment issues efficiently</p>
    </header>
    
    <div class="card">
      <h2>Report Equipment Issue</h2>
      <form id="reportForm">
        <div class="form-group">
          <label for="equipment">Equipment *</label>
          <select id="equipment" required>
            <option value="">Select Equipment</option>
            <option value="Treadmill">Treadmill</option>
            <option value="Elliptical">Elliptical</option>
            <option value="Stationary Bike">Stationary Bike</option>
            <option value="Rowing Machine">Rowing Machine</option>
            <option value="Leg Press">Leg Press</option>
            <option value="Chest Press">Chest Press</option>
            <option value="Lat Pulldown">Lat Pulldown</option>
            <option value="Smith Machine">Smith Machine</option>
            <option value="Dumbbell Set">Dumbbell Set</option>
            <option value="Barbell Set">Barbell Set</option>
            <option value="Bench Press">Bench Press</option>
            <option value="Leg Curl Machine">Leg Curl Machine</option>
            <option value="Cable Crossover">Cable Crossover</option>
            <option value="Other">Other</option>
          </select>
        </div>
        
        <div class="form-group" id="otherEquipmentGroup" style="display: none;">
          <label for="otherEquipment">Specify Equipment *</label>
          <input type="text" id="otherEquipment" placeholder="Enter equipment name">
        </div>
        
        <div class="form-group">
          <label for="status">Status *</label>
          <select id="status" required>
            <option value="">Select Status</option>
            <option value="Good">Good</option>
            <option value="Needs Repair">Needs Repair</option>
            <option value="Needs Replacement">Needs Replacement</option>
            <option value="Damaged">Damaged</option>
            <option value="Under Maintenance">Under Maintenance</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="condition">Condition Description *</label>
          <textarea id="condition" placeholder="Please describe the condition of the equipment in detail..." required minlength="10"></textarea>
          <span class="char-counter">0 / 500</span>
        </div>
        
        <button type="submit" id="submitBtn">
          <span class="btn-text">Submit Report</span>
          <span class="btn-loader" style="display: none;">Submitting...</span>
        </button>
      </form>
    </div>
    
    <div class="card">
      <div class="reports-header">
        <h2>Equipment Reports</h2>
        <div class="filter-controls">
          <select id="filterStatus" class="filter-select">
            <option value="">All Statuses</option>
            <option value="Good">Good</option>
            <option value="Needs Repair">Needs Repair</option>
            <option value="Needs Replacement">Needs Replacement</option>
            <option value="Damaged">Damaged</option>
            <option value="Under Maintenance">Under Maintenance</option>
          </select>
          <input type="text" id="searchEquipment" class="search-input" placeholder="Search equipment...">
        </div>
      </div>
      
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-number" id="totalReports">0</div>
          <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="needsAttention">0</div>
          <div class="stat-label">Needs Attention</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="goodCondition">0</div>
          <div class="stat-label">Good Condition</div>
        </div>
      </div>
      
      <div id="reportsTableContainer">
        <table id="reportsTable">
          <thead>
            <tr>
              <th onclick="sortTable(0)">Equipment <span class="sort-icon">⇅</span></th>
              <th onclick="sortTable(1)">Status <span class="sort-icon">⇅</span></th>
              <th>Condition</th>
              <th onclick="sortTable(3)">Date & Time <span class="sort-icon">⇅</span></th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="reportsBody"></tbody>
        </table>

        <div id="emptyState" class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>No Reports Yet</h3>
          <p>Submit a report using the form above to get started</p>
        </div>
      </div>
    </div>
  </div>

  <div id="notification" class="notification"></div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <h3>Confirm Delete</h3>
      <p>Are you sure you want to delete this report?</p>
      <div class="modal-actions">
        <button onclick="cancelDelete()" class="btn-secondary">Cancel</button>
        <button onclick="confirmDelete()" class="btn-danger">Delete</button>
      </div>
    </div>
  </div>

  <!-- View Details Modal -->
  <div id="detailsModal" class="modal">
    <div class="modal-content modal-large">
      <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
      <h3>Report Details</h3>
      <div id="detailsContent"></div>
    </div>
  </div>

  <script>
    let allReports = [];
    let deleteReportId = null;
    let sortDirection = {};

    document.addEventListener('DOMContentLoaded', function () {
      const reportForm = document.getElementById('reportForm');
      const reportsBody = document.getElementById('reportsBody');
      const emptyState = document.getElementById('emptyState');
      const notification = document.getElementById('notification');
      const equipmentSelect = document.getElementById('equipment');
      const otherEquipmentGroup = document.getElementById('otherEquipmentGroup');
      const conditionTextarea = document.getElementById('condition');
      const charCounter = document.querySelector('.char-counter');
      const filterStatus = document.getElementById('filterStatus');
      const searchEquipment = document.getElementById('searchEquipment');

      // Show/hide "Other" equipment field
      equipmentSelect.addEventListener('change', function() {
        if (this.value === 'Other') {
          otherEquipmentGroup.style.display = 'block';
          document.getElementById('otherEquipment').required = true;
        } else {
          otherEquipmentGroup.style.display = 'none';
          document.getElementById('otherEquipment').required = false;
        }
      });

      // Character counter
      conditionTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCounter.textContent = `${length} / 500`;
        if (length > 500) {
          this.value = this.value.substring(0, 500);
          charCounter.textContent = '500 / 500';
        }
      });

      // Filter and search
      filterStatus.addEventListener('change', filterReports);
      searchEquipment.addEventListener('input', filterReports);

      // Load existing reports
      loadReports();

      // Submit form
      reportForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';

        let equipment = equipmentSelect.value;
        if (equipment === 'Other') {
          equipment = document.getElementById('otherEquipment').value;
        }
        
        const status = document.getElementById('status').value;
        const condition = conditionTextarea.value;

        const reportData = { equipment, status, condition };

        try {
          const response = await fetch('reportSer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reportData)
          });

          const result = await response.json();

          if (result.status === 'success') {
            showNotification('Report submitted successfully!', 'success');
            reportForm.reset();
            charCounter.textContent = '0 / 500';
            otherEquipmentGroup.style.display = 'none';
            loadReports();
          } else {
            showNotification(result.message || 'Something went wrong.', 'error');
          }
        } catch (error) {
          showNotification('Network error. Please try again.', 'error');
        } finally {
          submitBtn.disabled = false;
          btnText.style.display = 'inline';
          btnLoader.style.display = 'none';
        }
      });

      // Load all reports from DB
      async function loadReports() {
        try {
          const response = await fetch('reportSer.php');
          allReports = await response.json();
          filterReports();
          updateStats();
        } catch (error) {
          showNotification('Failed to load reports', 'error');
        }
      }

      // Filter reports based on status and search
      function filterReports() {
        const statusFilter = filterStatus.value;
        const searchTerm = searchEquipment.value.toLowerCase();

        const filtered = allReports.filter(report => {
          const matchesStatus = !statusFilter || report.status === statusFilter;
          const matchesSearch = !searchTerm || 
            report.equipment.toLowerCase().includes(searchTerm) ||
            report.condition_text.toLowerCase().includes(searchTerm);
          return matchesStatus && matchesSearch;
        });

        displayReports(filtered);
      }

      // Display reports in table
      function displayReports(reports) {
        reportsBody.innerHTML = '';

        if (reports.length > 0) {
          reports.forEach(report => {
            addReportToTable(report);
          });
          toggleEmptyState(false);
        } else {
          toggleEmptyState(true);
        }
      }

      // Add a report to the table
      function addReportToTable(report) {
        const row = document.createElement('tr');
        row.classList.add('fade-in-row');

        const statusClass = getStatusClass(report.status);
        const statusBadge = `<span class="status-badge ${statusClass}">${report.status}</span>`;
        const shortCondition = report.condition_text.length > 50 
          ? report.condition_text.substring(0, 50) + '...' 
          : report.condition_text;

        row.innerHTML = `
          <td><strong>${report.equipment}</strong></td>
          <td>${statusBadge}</td>
          <td class="condition-cell">${shortCondition}</td>
          <td>${formatDateTime(report.created_at)}</td>
          <td class="action-buttons">
            <button onclick="viewDetails(${report.id})" class="btn-view" title="View Details">👁️</button>
            <button onclick="deleteReport(${report.id})" class="btn-delete" title="Delete">🗑️</button>
          </td>
        `;

        reportsBody.appendChild(row);
      }

      // Format date time
      function formatDateTime(dateTime) {
        const date = new Date(dateTime);
        const options = { 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
      }

      // Update statistics
      function updateStats() {
        document.getElementById('totalReports').textContent = allReports.length;
        
        const needsAttention = allReports.filter(r => 
          r.status === 'Needs Repair' || 
          r.status === 'Needs Replacement' || 
          r.status === 'Damaged'
        ).length;
        document.getElementById('needsAttention').textContent = needsAttention;
        
        const good = allReports.filter(r => r.status === 'Good').length;
        document.getElementById('goodCondition').textContent = good;
      }

      // Match colors with status
      function getStatusClass(status) {
        switch (status) {
          case 'Good': return 'status-good';
          case 'Needs Repair': return 'status-needs-repair';
          case 'Needs Replacement': return 'status-needs-replacement'; // ✅ fixed
          case 'Damaged': return 'status-damaged';
          case 'Under Maintenance': return 'status-under-maintenance';
          
          default: return '';
        }
      }

      // Show or hide "No Reports Yet"
      function toggleEmptyState(show) {
        emptyState.style.display = show ? 'flex' : 'none';
        document.querySelector('table').style.display = show ? 'none' : 'table';
      }

      // Notification
      function showNotification(message, type = 'success') {
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
      }

      // Make functions global
      window.loadReports = loadReports;
      window.showNotification = showNotification;
      window.getStatusClass = getStatusClass;
      window.formatDateTime = formatDateTime;
    });

    // View details modal
    function viewDetails(id) {
      const report = allReports.find(r => r.id == id);
      if (!report) return;

      const statusClass = getStatusClass(report.status);
      const detailsContent = document.getElementById('detailsContent');
      
      detailsContent.innerHTML = `
        <div class="detail-row">
          <strong>Equipment:</strong> ${report.equipment}
        </div>
        <div class="detail-row">
          <strong>Status:</strong> <span class="status-badge ${statusClass}">${report.status}</span>
        </div>
        <div class="detail-row">
          <strong>Date & Time:</strong> ${formatDateTime(report.created_at)}
        </div>
        <div class="detail-row">
          <strong>Condition Description:</strong>
          <p class="condition-full">${report.condition_text}</p>
        </div>
      `;

      document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
      document.getElementById('detailsModal').style.display = 'none';
    }

    // Delete functionality
    function deleteReport(id) {
      deleteReportId = id;
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function cancelDelete() {
      deleteReportId = null;
      document.getElementById('deleteModal').style.display = 'none';
    }

    async function confirmDelete() {
      if (!deleteReportId) return;

      try {
        const response = await fetch('reportSer.php', {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: deleteReportId })
        });

        const result = await response.json();

        if (result.status === 'success') {
          showNotification('Report deleted successfully', 'success');
          loadReports();
        } else {
          showNotification(result.message || 'Failed to delete report', 'error');
        }
      } catch (error) {
        showNotification('Network error. Please try again.', 'error');
      }

      cancelDelete();
    }

    // Sort table
    function sortTable(columnIndex) {
      const tbody = document.getElementById('reportsBody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      
      const currentDirection = sortDirection[columnIndex] || 'asc';
      const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
      sortDirection = { [columnIndex]: newDirection };

      rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();

        if (columnIndex === 3) { // Date column
          return newDirection === 'asc' 
            ? new Date(aValue) - new Date(bValue)
            : new Date(bValue) - new Date(aValue);
        }

        return newDirection === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      });

      rows.forEach(row => tbody.appendChild(row));
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
      const deleteModal = document.getElementById('deleteModal');
      const detailsModal = document.getElementById('detailsModal');
      
      if (event.target === deleteModal) {
        cancelDelete();
      }
      if (event.target === detailsModal) {
        closeDetailsModal();
      }
    }
  </script>
</body>
</html>