// Display employee info on page load
document.addEventListener('DOMContentLoaded', function() {
  // Menu button: Membership Manager
  document.getElementById('show-members').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('members.php');
  });

  // ✅ Member Login (Check-In Page)
  document.getElementById('show-memberlogin').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('mcheckin.php'); // ← fixed the typo (was mcheckin,php)
  });

  // Registration
  document.getElementById('show-registration').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('registration.php');
  });

  // Renewal
  document.getElementById('show-renewal').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('memberrenewal.php');
  });
  
  // Gym Store Manager
  document.getElementById('show-gymstore').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('store.php');
  });
  document.getElementById('show-renewal-log').addEventListener('click', function(e) {
       e.preventDefault();
       document.getElementById('content-frame').src = 'renewal_log.php';
       document.getElementById('content-frame').style.display = 'block';
       document.getElementById('welcome-content').classList.remove('active');
   });
  // Report Status
  document.getElementById('show-reportstatus').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('report.php');
  });
  
  // Attendance Log - Today's Attendance
  document.querySelector('nav ul li:nth-child(2) .dropdown li:nth-child(1) a').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('todayattendance.php');
  });
  
  // Attendance Log - Attendance Records
  document.querySelector('nav ul li:nth-child(2) .dropdown li:nth-child(2) a').addEventListener('click', function(e) {
    e.preventDefault();
    loadContent('attendancehistory.php');
  });

  // Logout functionality
  document.getElementById('logout').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Clear session or local storage
    sessionStorage.clear();
    localStorage.clear();
    
    // Redirect back to login
    window.location.href = 'main.php';
  });
});

// Function to load content into iframe
function loadContent(url) {
  const welcomeContent = document.getElementById('welcome-content');
  const contentFrame = document.getElementById('content-frame');
  
  welcomeContent.style.display = 'none';
  contentFrame.style.display = 'block';
  contentFrame.src = url;
}

// Function to show welcome content
function showWelcome() {
  const welcomeContent = document.getElementById('welcome-content');
  const contentFrame = document.getElementById('content-frame');
  
  welcomeContent.style.display = 'flex';
  contentFrame.style.display = 'none';
  contentFrame.src = '';
}

// Function to toggle menu
function toggleMenu() {
  document.querySelector('.sidebar').classList.toggle('active');
}

// Make functions available globally
window.showWelcome = showWelcome;
window.loadContent = loadContent;
