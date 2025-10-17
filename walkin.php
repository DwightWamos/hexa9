<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>G14 Fitness Centre - Walk-In Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            background-image: none;
        }

        .header {
            text-align: center;
            color: rgb(0, 0, 0);
            margin-bottom: 30px;
            padding: 20px;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        h2 {
            text-align: center;
            color: rgb(0, 0, 0);
            margin-bottom: 30px;
            font-size: 1.8em;
        }

        form {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        fieldset {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        legend {
            font-size: 1.3em;
            font-weight: bold;
            color: #667eea;
            padding: 0 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .gender-options {
            display: flex;
            gap: 30px;
            margin-top: 10px;
        }

        .gender-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .gender-options input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .bmi-display {
            background: #f0f4ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px solid #667eea;
        }

        .bmi-result {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.1em;
        }

        .bmi-label {
            font-weight: 600;
            color: #333;
        }

        .bmi-value {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
        }

        .bmi-category {
            color: #666;
            font-style: italic;
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .payment-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ffc107;
            margin-top: 10px;
        }

        .payment-info strong {
            color: #856404;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            form {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>G14 Fitness Centre</h1>
        <p>Walk-In Registration</p>
    </div>

    <!-- Registration Form -->
    <h2>New Walk-In Member Registration</h2>
    <form id="registration-form" action="walkinSer.php" method="POST" onsubmit="return registerMember(event)">
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
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
            </div>
        </fieldset>

        <!-- Physical Metrics -->
        <fieldset>
            <legend>Physical Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="height">Height (cm)</label>
                    <input type="number" id="height" name="height" min="100" max="250" required>
                </div>
                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" id="weight" name="weight" min="30" max="300" step="0.1" required>
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

        <!-- Payment Information -->
        <fieldset>
            <legend>Payment Information</legend>
            <div class="form-group">
                <label for="payment-method">Payment Method</label>
                <select id="payment-method" name="payment_method" required>
                    <option value="cash">Cash</option>
                </select>
                <div class="payment-info">
                    <strong>Note:</strong> Walk-in registration accepts cash payment only. Please have exact amount ready at the counter.
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
        <button type="submit" class="btn-register">Complete Walk-In Registration</button>
    </form>

    <!-- Success Message -->
    <div id="success-message"></div>
    <div id="error-message" style="display: none; color: red; margin-top: 10px;"></div>

-    <script>
        // Listen to height/weight input changes
        document.getElementById('height').addEventListener('input', calculateBMI);
        document.getElementById('weight').addEventListener('input', calculateBMI);

        function calculateBMI() {
            const height = parseFloat(document.getElementById('height').value);
            const weight = parseFloat(document.getElementById('weight').value);

            if (!isNaN(height) && !isNaN(weight) && height > 0 && weight > 0) {
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
            } else {
                document.getElementById('bmi-display').style.display = 'none';
            }
        }

        function registerMember(event) {
            event.preventDefault();

            const form = document.getElementById('registration-form');
            const formData = new FormData(form);
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';

            fetch('walkinSer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.innerHTML = `
                        <div style="background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 20px auto; max-width: 800px;">
                            <h3>Walk-In Registration Successful!</h3>
                            <p>${data.message}</p>
                            <p><strong>Your Member ID: ${data.member_id}</strong></p>
                            <p>Please proceed to the counter to complete your cash payment.</p>
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
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 800px;">
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
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px auto; max-width: 800px;">
                        <h3>Error</h3>
                        <p>An error occurred while processing your registration. Please try again.</p>
                    </div>
                `;
                errorMessage.style.display = 'block';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Complete Walk-In Registration';
            });

            return false;
        }
    </script>
</body>
</html>
