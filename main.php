<?php 
session_start(); 
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']); // clear after showing
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G14 Fitness Centre Sign In</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- background pattern --> 
    <div class="background-pattern">
        <!-- card for Welcome text --> 
        <div class="card">
            <div class="card-content">
                <p class="welcome-text">Welcome</p>
                <p class="welcome-text">to the Grind</p>
                <div class="logo">
                    <span class="logo-g14">G14</span>
                    <span class="logo-fitness">FITNESS</span>
                    <span class="logo-centre">CENTRE</span>
                </div>

                <p class="log-in-text">SIGN IN</p>

                

                <form method="post" action="mainSer.php">
                    <div class="input-group">
                        <label for="employee_id">Employee ID:</label>
                        <input type="text" id="employee_id" name="employee_id" required>
                    </div>
                    <div class="input-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login_user" class="login-button">Log In</button>

                    
                </form>

                <!-- 🔴 Error box under button -->
                    <?php if (!empty($errors)) : ?>
                        <div class="error-box" style="color:red; text-align:center; margin-top:10px;">
                            <?php foreach ($errors as $e) : ?>
                                <p><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
