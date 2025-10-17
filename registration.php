<?php
// Start session and check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if employee is logged in
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['employee_name'])) {
    // Redirect to login if not authenticated
    header("Location: main.php");
    exit();
}

$current_employee = $_SESSION['employee_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>G14 Fitness Centre - New Member Registration</title>
    <link rel="stylesheet" href="registration.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>G14 Fitness Centre</h1>
        <p>Join our fitness family today!</p>
        <p style="font-size: 14px; color: #666; margin-top: 10px;">
            Registering as: <strong style="color: #667eea;"><?php echo htmlspecialchars($current_employee); ?></strong>
        </p>
    </div>

    <!-- Registration Form -->
    <h2>New Member Registration</h2>
    <form id="registration-form" class="show" action="membershipSer.php" method="POST" onsubmit="return registerMember(event)">
        <!-- Personal Details -->
        <fieldset>
            <legend>Personal Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="last_name" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="contact-number">Phone Number</label>
                    <input type="tel" id="contact-number" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="date_of_birth" required>
                </div>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <div class="gender-options">
                    <label><input type="radio" name="gender" value="Male" required> Male</label>
                    <label><input type="radio" name="gender" value="Female" required> Female</label>
                </div>
            </div>
        </fieldset>

        <!-- Physical Metrics -->
        <fieldset>
            <legend>Physical Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" id="height" name="height" min="100" max="250" required onchange="calculateBMI()">
                </div>
                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" min="30" max="300" step="0.1" required onchange="calculateBMI()">
                </div>
            </div>
            <!-- BMI Display -->
            <div class="bmi-display" id="bmi-display" style="display: none;">
                <div class="bmi-result">
                    <span class="bmi-label">Your BMI:</span>
                    <span class="bmi-value" id="bmi-value">0</span>
                    <span class="bmi-category" id="bmi-category"></span>
                </div>
            </div>
        </fieldset>

        <!-- Membership Details -->
        <fieldset>
            <legend>Membership Options</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="membership-type">Membership Type</label>
                    <select id="membership-type" name="membership_type" required>
                        <option value="">Select membership type</option>
                        <option value="standard">Standard - ₱1,500/month</option>
                        <option value="premium">Premium - ₱2,500/month</option>
                        <option value="student">Student - ₱1,000/month</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment-method">Payment Method</label>
                    <select id="payment-method" name="payment_method" required>
                        <option value="">Select payment method</option>
                        <option value="ewallet">E-Wallet (GCash)</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- Emergency Contact -->
        <fieldset>
            <legend>Emergency Contact</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="emergency-name">Contact Name</label>
                    <input type="text" id="emergency-name" name="emergency_contact_name" required>
                </div>
                <div class="form-group">
                    <label for="emergency-phone">Contact Phone</label>
                    <input type="tel" id="emergency-phone" name="emergency_contact_phone" required>
                </div>
            </div>
        </fieldset>

        <!-- Register Button -->
        <button type="submit" class="btn-register">Complete Registration</button>
    </form>

    <!-- Success Message -->
    <div id="success-message"></div>
    <div id="error-message" style="display: none; color: red; margin-top: 10px;"></div>

    <script>
        function calculateBMI() {
            const height = parseFloat(document.getElementById('height').value);
            const weight = parseFloat(document.getElementById('weight').value);
            
            if (height && weight) {
                const heightInMeters = height / 100;
                const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);
                
                document.getElementById('bmi-value').textContent = bmi;
                
                let category = '';
                if (bmi < 18.5) {
                    category = 'Underweight';
                } else if (bmi < 25) {
                    category = 'Normal weight';
                } else if (bmi < 30) {
                    category = 'Overweight';
                } else {
                    category = 'Obese';
                }
                
                document.getElementById('bmi-category').textContent = `(${category})`;
                document.getElementById('bmi-display').style.display = 'block';
            }
        }

        function registerMember(event) {
            event.preventDefault();
            
            const form = document.getElementById('registration-form');
            const formData = new FormData(form);
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            // Hide previous messages
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // Disable submit button to prevent double submission
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            fetch('membershipSer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.innerHTML = `
                        <div style="background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px 0;">
                            <h3>Registration Successful!</h3>
                            <p>${data.message}</p>
                            <p><strong>Member ID: ${data.member_id}</strong></p>
                            <p><strong>Registered by: ${data.registered_by || 'Staff'}</strong></p>
                            <p>Email Status: ${data.email_status || 'Sent'}</p>
                            <p style="margin-top: 10px; font-size: 14px;">Please save this Member ID for future reference.</p>
                        </div>
                    `;
                    successMessage.style.display = 'block';
                    form.reset();
                    document.getElementById('bmi-display').style.display = 'none';
                } else {
                    let errorText = data.message;
                    if (data.errors && data.errors.length > 0) {
                        errorText += '<br><ul>';
                        data.errors.forEach(error => {
                            errorText += `<li>${error}</li>`;
                        });
                        errorText += '</ul>';
                    }
                    
                    errorMessage.innerHTML = `
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;">
                            <h3>Registration Failed</h3>
                            <div>${errorText}</div>
                        </div>
                    `;
                    errorMessage.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.innerHTML = `
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0;">
                        <h3>Error</h3>
                        <p>An error occurred while processing your registration. Please try again.</p>
                    </div>
                `;
                errorMessage.style.display = 'block';
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Complete Registration';
            });
            
            return false;
        }
    </script>
</body>
</html>