<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Membership Renewal</title>
    <link rel="stylesheet" href="renewal.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Membership Renewal Form</h1>
            
            <!-- Status Message Box -->
            <div id="statusBox" class="status-box" style="display: none;"></div>
            
            <form id="renewalForm">
                <div class="section-title">Personal Details</div>
                
                <div class="form-group">
                    <label for="memberId">Member ID:</label>
                    <input type="text" id="memberId" name="memberId" required>
                    <button type="button" id="lookupBtn" class="lookup-btn">Lookup Member</button>
                </div>
                
                <!-- Member Info Display -->
                <div id="memberInfo" style="display: none; background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <p><strong>Name:</strong> <span id="memberName">--</span></p>
                    <p><strong>Membership Type:</strong> <span id="memberType">--</span></p>
                    <p><strong>Current Status:</strong> <span id="memberStatus" style="font-weight: 600;">--</span></p>
                    <p><strong>Expiry Date:</strong> <span id="expiryDate">--</span></p>
                </div>
                
                <div class="section-title">Physical Metrics (for BMI Calculation)</div>
                
                <div class="form-group">
                    <label for="height">Height (cm):</label>
                    <input type="number" id="height" name="height" min="1" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="weight">Weight (kg):</label>
                    <input type="number" id="weight" name="weight" min="1" step="0.1" required>
                </div>
                
                <div class="form-group">
                    <div class="bmi-display">
                        BMI: <span id="bmiValue">--</span>
                    </div>
                </div>
                
                <div class="section-title">Membership Details</div>
                
                <div class="form-group">
                    <label>Membership Type:</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="membershipType" value="standard" required>
                            Standard (₱1,500)
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="membershipType" value="premium">
                            Premium (₱2,500)
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="membershipType" value="student">
                            Student (₱1000)
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Payment Method:</label>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="gcash" required>
                            GCash
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="paymentMethod" value="cash">
                            Cash
                        </label>
                    </div>
                </div>
                
                <button type="submit" id="submitBtn">Renew Membership</button>
            </form>
        </div>
    </div>

    <style>
        .lookup-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .lookup-btn:hover {
            background: #5568d3;
        }
        .status-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .status-box.error {
            background: #ffe5e5;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }
        .status-box.success {
            background: #e5ffe5;
            color: #388e3c;
            border-left: 4px solid #388e3c;
        }
        .status-box.warning {
            background: #fff5e5;
            color: #f57c00;
            border-left: 4px solid #f57c00;
        }
    </style>

    <script>
        let currentMemberId = null;
        let memberStatus = null;

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('renewalForm');
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const bmiValue = document.getElementById('bmiValue');
            const submitBtn = document.getElementById('submitBtn');
            const lookupBtn = document.getElementById('lookupBtn');
            const memberIdInput = document.getElementById('memberId');

            // Lookup Member
            lookupBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                const memberId = memberIdInput.value.trim();
                
                if (!memberId) {
                    showStatus('Please enter a Member ID', 'error');
                    return;
                }

                try {
                    const res = await fetch('mrSer.php?action=lookupMember', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ member_id: memberId })
                    });

                    const data = await res.json();

                    if (data.status === "success") {
                        currentMemberId = data.member_id;
                        memberStatus = data.member_status;
                        
                        document.getElementById('memberName').textContent = data.member_name;
                        document.getElementById('memberType').textContent = data.membership_type;
                        document.getElementById('expiryDate').textContent = data.expiry_date;
                        
                        // Display status with appropriate styling
                        const statusSpan = document.getElementById('memberStatus');
                        statusSpan.textContent = data.member_status;
                        statusSpan.style.color = data.member_status === 'Deleted' ? '#d32f2f' : 
                                                 data.member_status === 'Inactive' ? '#f57c00' : '#388e3c';
                        
                        document.getElementById('memberInfo').style.display = 'block';
                        
                        // Show appropriate message
                        if (data.member_status === 'Deleted') {
                            showStatus('This member account has been deleted and cannot be renewed. Use the restore button in the Members List to restore the account first.', 'error');
                            submitBtn.disabled = true;
                        } else if (data.member_status === 'Inactive') {
                            showStatus('This member account is inactive (no renewal for 5+ months). You can renew it now.', 'warning');
                            submitBtn.disabled = false;
                        } else {
                            showStatus('Member found! Proceed with renewal.', 'success');
                            submitBtn.disabled = false;
                        }
                    } else {
                        showStatus(data.message, 'error');
                        document.getElementById('memberInfo').style.display = 'none';
                        currentMemberId = null;
                        submitBtn.disabled = true;
                    }
                } catch (err) {
                    showStatus('Lookup failed: ' + err.message, 'error');
                    document.getElementById('memberInfo').style.display = 'none';
                    currentMemberId = null;
                    submitBtn.disabled = true;
                }
            });

            // Calculate BMI live
            function calculateBMI() {
                if (!heightInput.value || !weightInput.value) {
                    bmiValue.textContent = '--';
                    return;
                }
                const height = parseFloat(heightInput.value);
                const weight = parseFloat(weightInput.value);
                const bmi = weight / Math.pow(height / 100, 2);
                bmiValue.textContent = bmi.toFixed(1);
            }
            heightInput.addEventListener('input', calculateBMI);
            weightInput.addEventListener('input', calculateBMI);

            // Submit to PHP
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!currentMemberId) {
                    showStatus('Please lookup a member first', 'error');
                    return;
                }

                if (memberStatus === 'Deleted') {
                    showStatus('Cannot renew a deleted member account', 'error');
                    return;
                }

                submitBtn.textContent = 'Processing...';
                submitBtn.disabled = true;

                const formData = new FormData(form);
                formData.append('member_id', currentMemberId);

                fetch('mrSer.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        showStatus(data.message + " (BMI: " + data.bmi + ")", 'success');
                        form.reset();
                        bmiValue.textContent = '--';
                        currentMemberId = null;
                        document.getElementById('memberInfo').style.display = 'none';
                    } else {
                        showStatus("Error: " + data.message, 'error');
                    }
                })
                .catch(err => {
                    showStatus("Request failed: " + err, 'error');
                })
                .finally(() => {
                    submitBtn.textContent = 'Renew Membership';
                    submitBtn.disabled = false;
                });
            });

            function showStatus(message, type) {
                const statusBox = document.getElementById('statusBox');
                statusBox.textContent = message;
                statusBox.className = 'status-box ' + type;
                statusBox.style.display = 'block';
            }
        });
    </script>
</body>
</html>