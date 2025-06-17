<?php
require_once 'functions.php';

$message = '';
$messageType = '';
$showVerificationForm = false;
$userEmail = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $email = trim($_POST['email']);
                
                if (empty($email)) {
                    $message = "Please enter an email address.";
                    $messageType = 'error';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Please enter a valid email address.";
                    $messageType = 'error';
                } else {
                    if (registerEmail($email)) {
                        $verificationCode = generateVerificationCode();
                        
                        // Store verification code temporarily (in a real app, use database)
                        session_start();
                        $_SESSION['verification_code'] = $verificationCode;
                        $_SESSION['pending_email'] = $email;
                        $_SESSION['code_timestamp'] = time();
                        
                        if (sendVerificationEmail($email, $verificationCode)) {
                            $message = "Registration successful! Please check your email for the verification code.";
                            $messageType = 'success';
                            $showVerificationForm = true;
                            $userEmail = $email;
                        } else {
                            $message = "Registration successful, but we couldn't send the verification email. Please try again.";
                            $messageType = 'error';
                        }
                    } else {
                        $message = "This email is already registered or registration failed.";
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'verify':
                session_start();
                $enteredCode = trim($_POST['verification_code']);
                $storedCode = $_SESSION['verification_code'] ?? '';
                $pendingEmail = $_SESSION['pending_email'] ?? '';
                $timestamp = $_SESSION['code_timestamp'] ?? 0;
                
                // Check if code is expired (10 minutes)
                if (time() - $timestamp > 600) {
                    $message = "Verification code has expired. Please register again.";
                    $messageType = 'error';
                    session_destroy();
                } elseif ($enteredCode === $storedCode && !empty($pendingEmail)) {
                    $message = "Email verified successfully! You will start receiving daily XKCD comics.";
                    $messageType = 'success';
                    session_destroy();
                } else {
                    $message = "Invalid verification code. Please try again.";
                    $messageType = 'error';
                    $showVerificationForm = true;
                    $userEmail = $pendingEmail;
                }
                break;
                
            case 'resend':
                session_start();
                $pendingEmail = $_SESSION['pending_email'] ?? '';
                
                if (!empty($pendingEmail)) {
                    $newCode = generateVerificationCode();
                    $_SESSION['verification_code'] = $newCode;
                    $_SESSION['code_timestamp'] = time();
                    
                    if (sendVerificationEmail($pendingEmail, $newCode)) {
                        $message = "New verification code sent to your email.";
                        $messageType = 'success';
                        $showVerificationForm = true;
                        $userEmail = $pendingEmail;
                    } else {
                        $message = "Failed to send verification code. Please try again.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "Session expired. Please register again.";
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Check if there's a pending verification
session_start();
if (!$showVerificationForm && isset($_SESSION['pending_email']) && isset($_SESSION['verification_code'])) {
    $showVerificationForm = true;
    $userEmail = $_SESSION['pending_email'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XKCD Daily Comics - Subscribe</title>
    <style>
        /* Your existing CSS styles here */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #0c1821 30%, #1b2a41 70%, #324a5f 100%);
            min-height: 100vh;
            padding: 20px;
            color: #ccc9dc;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(204, 201, 220, 0.08);
            backdrop-filter: blur(30px);
            border-radius: 25px;
            border: 1px solid rgba(204, 201, 220, 0.15);
            padding: 40px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.4);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #ccc9dc 0%, #ffffff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc9dc;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            background: rgba(204, 201, 220, 0.1);
            border: 2px solid rgba(204, 201, 220, 0.2);
            border-radius: 10px;
            color: #ccc9dc;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ccc9dc;
            background: rgba(204, 201, 220, 0.15);
        }
        
        .btn {
            background: linear-gradient(135deg, #324a5f 0%, #1b2a41 100%);
            color: #ccc9dc;
            padding: 15px 30px;
            border: 2px solid rgba(204, 201, 220, 0.3);
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #1b2a41 0%, #ccc9dc 100%);
            color: #0c1821;
        }
        
        .btn-primary {
            width: 100%;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: rgba(50, 74, 95, 0.3);
            border: 1px solid #324a5f;
        }
        
        .message.error {
            background: rgba(27, 42, 65, 0.3);
            border: 1px solid #1b2a41;
        }
        
        .verification-form {
            background: rgba(204, 201, 220, 0.05);
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .code-input {
            text-align: center;
            font-size: 24px;
            letter-spacing: 5px;
            font-weight: bold;
        }
        
        .features {
            margin-top: 30px;
            text-align: center;
        }
        
        .features h3 {
            color: #ccc9dc;
            margin-bottom: 15px;
        }
        
        .features ul {
            list-style: none;
            padding: 0;
        }
        
        .features li {
            padding: 8px 0;
            color: rgba(204, 201, 220, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 XKCD Daily Comics</h1>
            <p>Get a random XKCD comic delivered to your email every day!</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$showVerificationForm): ?>
            <!-- Registration Form -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email address">
                </div>
                
                <button type="submit" class="btn btn-primary">Subscribe to Daily Comics</button>
            </form>
        <?php else: ?>
            <!-- Verification Form -->
            <div class="verification-form">
                <h3>Email Verification</h3>
                <p>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($userEmail); ?></strong></p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="form-group">
                        <label for="verification_code">Enter Verification Code:</label>
                        <input type="text" id="verification_code" name="verification_code" 
                               class="code-input" maxlength="6" pattern="[0-9]{6}" 
                               placeholder="000000" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Verify Email</button>
                </form>
                
                <div style="text-align: center; margin-top: 15px;">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="resend">
                        <button type="submit" class="btn">Resend Code</button>
                    </form>
                    
                    <a href="?" class="btn">Start Over</a>
                </div>
                
                <p style="color: rgba(204, 201, 220, 0.6); font-size: 12px; text-align: center; margin-top: 15px;">
                    Code expires in 10 minutes
                </p>
            </div>
        <?php endif; ?>

        <div class="features">
            <h3>What you'll get:</h3>
            <ul>
                <li> Daily Brain Candy - Random XKCD comics delivered fresh</li>
                <li> Zero Inbox Clutter - One premium comic, zero spam</li>
                <li> Instant Genius Status - Perfect nerdy references for any situation</li>
                <li> Easy Unsubscribe - One-click removal that actually works</li>
            </ul>
        </div>
    </div>

    <script>
        // Auto-format verification code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('verification_code');
            if (codeInput) {
                codeInput.addEventListener('input', function(e) {
                    // Only allow numbers
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Auto-submit when 6 digits are entered
                    if (this.value.length === 6) {
                        this.form.submit();
                    }
                });
                
                // Focus the input when verification form is shown
                codeInput.focus();
            }
        });
    </script>
</body>
</html>
