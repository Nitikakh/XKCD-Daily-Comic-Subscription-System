<?php
require_once 'functions.php';

$message = '';
$messageType = '';
$showConfirmation = false;
$emailToUnsubscribe = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'unsubscribe':
                $email = trim($_POST['email']);
                
                if (empty($email)) {
                    $message = "Please enter an email address.";
                    $messageType = 'error';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = "Please enter a valid email address.";
                    $messageType = 'error';
                } else {
                    // Show confirmation before unsubscribing
                    $showConfirmation = true;
                    $emailToUnsubscribe = $email;
                }
                break;
                
            case 'confirm_unsubscribe':
                $email = trim($_POST['email']);
                
                if (unsubscribeEmail($email)) {
                    $message = "You have been successfully unsubscribed from XKCD Daily Comics. We're sorry to see you go!";
                    $messageType = 'success';
                } else {
                    $message = "Email address not found in our subscription list or unsubscribe failed. Please check your email address.";
                    $messageType = 'error';
                }
                break;
                
            case 'cancel':
                // User cancelled unsubscription
                $message = "Unsubscription cancelled. You will continue to receive daily XKCD comics.";
                $messageType = 'info';
                break;
        }
    }
}

// Handle GET parameter for direct unsubscribe links (from emails)
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $emailFromLink = urldecode($_GET['email']);
    if (filter_var($emailFromLink, FILTER_VALIDATE_EMAIL)) {
        $showConfirmation = true;
        $emailToUnsubscribe = $emailFromLink;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - XKCD Daily Comics</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #0c1821 30%, #1b2a41 70%, #324a5f 100%);
            min-height: 100vh;
            padding: 20px;
            color: #ccc9dc;
            margin: 0;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
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
        
        .header p {
            color: rgba(204, 201, 220, 0.8);
            font-size: 1.1em;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc9dc;
            font-weight: 500;
            font-size: 1em;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(204, 201, 220, 0.1);
            border: 2px solid rgba(204, 201, 220, 0.2);
            border-radius: 12px;
            color: #ccc9dc;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ccc9dc;
            background: rgba(204, 201, 220, 0.15);
            transform: translateY(-2px);
        }
        
        .form-group input::placeholder {
            color: rgba(204, 201, 220, 0.5);
        }
        
        .btn {
            background: linear-gradient(135deg, #324a5f 0%, #1b2a41 100%);
            color: #ccc9dc;
            padding: 15px 30px;
            border: 2px solid rgba(204, 201, 220, 0.3);
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
            min-width: 140px;
            text-align: center;
        }
        
        .btn:hover {
            background: linear-gradient(135deg, #1b2a41 0%, #ccc9dc 100%);
            color: #0c1821;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            width: 100%;
            margin: 8px 0;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-color: rgba(220, 53, 69, 0.5);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
            color: #ffffff;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            border-color: rgba(108, 117, 125, 0.5);
        }
        
        .message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
        }
        
        .message.success {
            background: rgba(50, 74, 95, 0.3);
            border: 2px solid #324a5f;
            color: #ccc9dc;
        }
        
        .message.error {
            background: rgba(220, 53, 69, 0.2);
            border: 2px solid rgba(220, 53, 69, 0.5);
            color: #ff6b7a;
        }
        
        .message.info {
            background: rgba(23, 162, 184, 0.2);
            border: 2px solid rgba(23, 162, 184, 0.5);
            color: #17a2b8;
        }
        
        .confirmation-box {
            background: rgba(204, 201, 220, 0.05);
            border: 2px solid rgba(204, 201, 220, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
        }
        
        .confirmation-box h3 {
            color: #ccc9dc;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .confirmation-box p {
            color: rgba(204, 201, 220, 0.8);
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .email-highlight {
            background: rgba(204, 201, 220, 0.2);
            padding: 3px 8px;
            border-radius: 5px;
            font-weight: bold;
            color: #ccc9dc;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .reasons {
            background: rgba(204, 201, 220, 0.03);
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .reasons h4 {
            color: #ccc9dc;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .reasons ul {
            list-style: none;
            padding: 0;
        }
        
        .reasons li {
            padding: 8px 0;
            color: rgba(204, 201, 220, 0.7);
            border-bottom: 1px solid rgba(204, 201, 220, 0.1);
        }
        
        .reasons li:last-child {
            border-bottom: none;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #ccc9dc;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .back-link a:hover {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 25px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Unsubscribe</h1>
            <p>We're sorry to see you go! Manage your XKCD Daily Comics subscription below.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$showConfirmation && $messageType !== 'success'): ?>
            <!-- Unsubscribe Form -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="unsubscribe">
                
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter the email address you want to unsubscribe"
                           value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Continue to Unsubscribe</button>
            </form>
            
            <div class="reasons">
                <h4>Before you go, consider these options:</h4>
                <ul>
                    <li> Reduce frequency - We only send one email per day</li>
                    <li> High-quality content - Carefully curated XKCD comics</li>
                    <li> Easy to manage - Simple unsubscribe process</li>
                    <li> Privacy respected - We never share your email</li>
                </ul>
            </div>

        <?php elseif ($showConfirmation): ?>
            <!-- Confirmation Form -->
            <div class="confirmation-box">
                <h3>⚠ Confirm Unsubscription</h3>
                <p>Are you sure you want to unsubscribe the email address:</p>
                <p><span class="email-highlight"><?php echo htmlspecialchars($emailToUnsubscribe); ?></span></p>
                <p>You will no longer receive daily XKCD comics at this email address.</p>
                
                <div class="button-group">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="confirm_unsubscribe">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($emailToUnsubscribe); ?>">
                        <button type="submit" class="btn btn-danger">Yes, Unsubscribe Me</button>
                    </form>
                    
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-secondary">Cancel</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($messageType === 'success'): ?>
            <!-- Success Actions -->
            <div style="text-align: center; margin-top: 25px;">
                <p style="color: rgba(204, 201, 220, 0.8); margin-bottom: 20px;">
                    If you change your mind, you can always subscribe again!
                </p>
                <a href="index.php" class="btn">Subscribe Again</a>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php">← Back to XKCD Daily Comics</a>
        </div>
    </div>

    <script>
        // Auto-focus email input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });

        // Confirmation dialog for unsubscribe action
        document.addEventListener('DOMContentLoaded', function() {
            const unsubscribeBtn = document.querySelector('button[name="action"][value="confirm_unsubscribe"]');
            if (unsubscribeBtn) {
                unsubscribeBtn.addEventListener('click', function(e) {
                    const email = document.querySelector('input[name="email"]').value;
                    if (!confirm(`Are you absolutely sure you want to unsubscribe ${email} from XKCD Daily Comics?`)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
