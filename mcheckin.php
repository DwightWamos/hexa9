<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G14 Gym - Member Attendance</title>
    <link rel="stylesheet" href="mcheckin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- QR Code Scanning Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js"></script>
</head>
<body>
    <div class="login-container">
        <!-- Background Decoration (as in mcheckin.css) -->
        <div class="bg-decoration">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>

        <div class="login-box">
            <div class="login-header">
                <div class="gym-logo">
                    <h1>G14</h1>
                    <span>FITNESS CENTRE</span>
                </div>
                <h2>Attendance System</h2>
                <p>Use the member's ID or QR code to Check In / Check Out</p>
            </div>

            <!-- Response Message Box -->
            <div id="responseBox" class="response-box" style="display: none;"></div>

            <!-- Toggle Buttons for Input Method -->
            <div class="input-method-toggle">
                <button id="manualInputToggle" class="toggle-btn active" data-target="manualInput">
                    <i class="fas fa-keyboard"></i> Manual ID
                </button>
                <button id="qrScannerToggle" class="toggle-btn" data-target="qrScanner">
                    <i class="fas fa-qrcode"></i> QR Scan
                </button>
            </div>

            <!-- 1. Manual ID Input Section -->
<div class="input-section active" id="manualInput">
    <form class="login-form" id="checkinForm">
        <div class="form-group">
            <label for="memberId">Member ID or Walk-in Name/ID</label>
            <input type="text" id="memberId" name="member_id" placeholder="Enter ID or Full Name" required>
            <div class="input-icon">👤</div>
        </div>

        <div class="form-actions">
            <button type="button" class="login-btn" id="checkinBtn">
                <span>Check In or Check Out</span>
                <div class="loader"></div>
            </button>

            <!-- ✅ New Non-member Registration Button -->
            <a href="walkin.php" class="nonmember-btn">
                <i class="fas fa-user-plus"></i> Non Member Registration
            </a>
        </div>
    </form>
</div>


            <!-- 2. QR Scanner Section -->
            <div class="input-section" id="qrScanner" style="display: none;">
                <!-- The ID 'qr-reader' is where the scanner UI will be injected -->
                <div id="qr-reader" style="width: 100%;"></div>
                <div id="qr-reader-results" style="margin-top: 15px; font-weight: bold; text-align: center;">Ready to scan...</div>
            </div>

        </div>
    </div>

    <script>
        const API_URL = "mcheckinSer.php";
        const form = document.getElementById("checkinForm");
        const checkinBtn = document.getElementById("checkinBtn");
        const memberIdInput = document.getElementById("memberId");
        const responseBox = document.getElementById("responseBox");
        const loader = document.querySelector(".loader");

        // ===========================================
        // ATTENDANCE SUBMISSION FUNCTION (Manual & QR)
        // ===========================================
        async function submitAttendance(id_value) {
            id_value = id_value.trim();
            if (!id_value) {
                responseBox.style.display = "block";
                responseBox.style.color = "orange";
                responseBox.textContent = "Please enter an ID or scan a QR code.";
                return;
            }

            // Disable button and show loader (only for manual input button)
            checkinBtn.disabled = true;
            loader.style.display = "block";
            checkinBtn.querySelector('span').style.display = 'none';

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    // Send only the ID; the server determines the action
                    body: JSON.stringify({ member_id: id_value }) 
                });

                const data = await response.json();

                // Handle server response
                responseBox.style.display = "block";
                
                if (data.status === "success") {
                    responseBox.style.color = "green";
                    responseBox.style.fontWeight = "bold";
                    // Display the dynamic action from the server (Check In or Check Out)
                    responseBox.innerHTML = data.message; 
                    memberIdInput.value = ""; // Clear input on success
                } else {
                    responseBox.style.color = "red";
                    responseBox.textContent = data.message;
                }

            } catch (err) {
                console.error("Fetch error:", err);
                responseBox.style.color = "red";
                responseBox.textContent = "❌ Network Error: Could not connect to the server.";
            } finally {
                // Re-enable button and hide loader
                checkinBtn.disabled = false;
                loader.style.display = "none";
                checkinBtn.querySelector('span').style.display = 'block';
            }
        }

        // ===========================================
        // MANUAL INPUT LOGIC
        // ===========================================
        checkinBtn.addEventListener("click", () => {
            const id = memberIdInput.value;
            submitAttendance(id);
        });

        // Allow Enter key to submit
        memberIdInput.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                document.getElementById("checkinBtn").click();
            }
        });

        // ===========================================
        // QR SCANNER LOGIC (html5-qrcode)
        // ===========================================
        let lastScanTime = 0;
        const scanThrottleTime = 3000; // 3 seconds delay between scans

        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            const now = Date.now();
            
            // Throttle mechanism to prevent multiple rapid scans
            if (now - lastScanTime < scanThrottleTime) {
                document.getElementById('qr-reader-results').textContent = 'Scan detected. Please wait briefly...';
                return;
            }

            // Only process if the scanner is actively shown
            if (document.getElementById('qrScanner').style.display !== 'none') {
                lastScanTime = now; // Update the last successful scan time
                document.getElementById('qr-reader-results').textContent = `QR Scanned: Processing ID...`;
                
                // Stop the scanner momentarily for processing
                if (html5QrCode) {
                    html5QrCode.pause(); 
                }
                
                // Submit attendance using the scanned ID
                submitAttendance(decodedText).then(() => {
                    // Resume scanning after submission, or switch back
                    setTimeout(() => {
                        if (document.getElementById('qrScanner').style.display !== 'none') {
                           html5QrCode.resume();
                           document.getElementById('qr-reader-results').textContent = 'Scanner Active... Ready to scan next code.';
                        }
                    }, 2000); // 2-second pause after successful log
                });
            }
        };

        const qrCodeErrorCallback = (errorMessage) => {
             // Update UI with a friendly message if the scanner is active
            if (document.getElementById('qrScanner').style.display !== 'none') {
                document.getElementById('qr-reader-results').textContent = 'Scanning... Ensure QR code is clear and centered.';
            }
        };

        const html5QrCode = new Html5Qrcode("qr-reader");
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true
        };
        let qrScanActive = false;
        
        function startQrScanner() {
            if (!qrScanActive) {
                // Ensure manual input button is disabled while scanning
                checkinBtn.disabled = true; 
                form.style.pointerEvents = 'none';

                html5QrCode.start(
                    { facingMode: "environment" }, // Prefer rear camera
                    config,
                    qrCodeSuccessCallback,
                    qrCodeErrorCallback
                ).then(() => {
                    qrScanActive = true;
                    document.getElementById('qr-reader-results').textContent = 'Scanner Active...';
                }).catch((err) => {
                    document.getElementById('qr-reader-results').textContent = 'Error: No camera found or permission denied. Ensure camera access is allowed.';
                    console.error("QR Scanner Startup Error:", err);
                });
            }
        }

        function stopQrScanner() {
            if (qrScanActive) {
                // Re-enable manual input button
                checkinBtn.disabled = false;
                form.style.pointerEvents = 'auto';

                html5QrCode.stop().then((ignore) => {
                    qrScanActive = false;
                    document.getElementById('qr-reader-results').textContent = 'Ready to scan...';
                }).catch((err) => {
                    // Ignore error if already stopped
                    console.log("QR scanner was already stopped or failed to stop gracefully.", err);
                    qrScanActive = false;
                });
            }
        }

        // ===========================================
        // TOGGLE INPUT METHOD LOGIC
        // ===========================================
        document.querySelectorAll('.toggle-btn').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                
                // Update active button state
                document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Hide all sections
                document.querySelectorAll('.input-section').forEach(section => section.style.display = 'none');
                
                // Show the target section
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.style.display = 'block';
                }

                // Manage QR Scanner lifecycle
                if (targetId === 'qrScanner') {
                    startQrScanner();
                } else {
                    stopQrScanner();
                }

                // Clear response box when switching modes
                responseBox.textContent = "";
                responseBox.style.display = "none";
            });
        });

        // Initialize: Ensure only manual input is visible on load
        document.addEventListener('DOMContentLoaded', () => {
            // Initial call to ensure QR scanner is not active if the manual input is default
            document.getElementById('manualInput').style.display = 'block';
            document.getElementById('qrScanner').style.display = 'none';
        });

    </script>
</body>
</html>
