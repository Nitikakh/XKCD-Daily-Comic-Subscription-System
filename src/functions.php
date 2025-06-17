<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code): bool {
    $subject = "XKCD Comics - Email Verification Code";
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c3e50;'>Welcome to XKCD Daily Comics!</h2>
            <p>Thank you for subscribing to our daily XKCD comic updates.</p>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                <h3 style='color: #e74c3c;'>Your Verification Code:</h3>
                <h1 style='color: #2c3e50; font-size: 2.5em; letter-spacing: 5px; margin: 10px 0;'>{$code}</h1>
            </div>
            <p>Please enter this code to verify your email address and start receiving daily XKCD comics.</p>
            <p style='color: #7f8c8d; font-size: 12px; margin-top: 30px;'>This code will expire in 10 minutes.</p>
        </div>
    </body>
    </html>";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: XKCD Comics <noreply@xkcdcomics.com>" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Check if email already exists
    if (file_exists($file)) {
        $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $emails)) {
            return false; // Email already registered
        }
    }
    
    // Add email to file
    $result = file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
    return $result !== false;
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';
    
    if (!file_exists($file)) {
        return false;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updatedEmails = array_filter($emails, function($registeredEmail) use ($email) {
        return trim($registeredEmail) !== trim($email);
    });
    
    // If no change, email wasn't found
    if (count($emails) === count($updatedEmails)) {
        return false;
    }
    
    // Write updated list back to file
    $result = file_put_contents($file, implode(PHP_EOL, $updatedEmails) . PHP_EOL, LOCK_EX);
    return $result !== false;
}

/**
 * Fetch random XKCD comic and format data as HTML.
 */
function fetchAndFormatXKCDData(): string {
    try {
        // Get latest comic number first
        $currentComicJson = @file_get_contents('https://xkcd.com/info.0.json');
        if ($currentComicJson === false) {
            throw new Exception("Failed to fetch current comic info");
        }
        
        $currentComic = json_decode($currentComicJson, true);
        if (!$currentComic || !isset($currentComic['num'])) {
            throw new Exception("Invalid current comic data");
        }
        
        $latestNum = $currentComic['num'];
        
        // Generate random comic number (avoiding comic 404 which doesn't exist)
        do {
            $randomNum = mt_rand(1, $latestNum);
        } while ($randomNum === 404);
        
        // Fetch random comic
        $comicUrl = "https://xkcd.com/{$randomNum}/info.0.json";
        $comicJson = @file_get_contents($comicUrl);
        
        if ($comicJson === false) {
            throw new Exception("Failed to fetch comic #{$randomNum}");
        }
        
        $comic = json_decode($comicJson, true);
        if (!$comic) {
            throw new Exception("Invalid comic data for #{$randomNum}");
        }
        
        // Format comic data as HTML
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #2c3e50; text-align: center; margin-bottom: 20px;'>{$comic['title']}</h2>
            <div style='text-align: center; margin: 20px 0;'>
                <img src='{$comic['img']}' alt='{$comic['alt']}' style='max-width: 100%; height: auto; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>
            </div>
            <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p style='margin: 0; font-style: italic; color: #555;'><strong>Alt text:</strong> {$comic['alt']}</p>
            </div>
            <p style='text-align: center; color: #7f8c8d; font-size: 14px;'>
                Comic #{$comic['num']} • Published: {$comic['day']}/{$comic['month']}/{$comic['year']}
            </p>
            <div style='text-align: center; margin-top: 30px;'>
                <a href='https://xkcd.com/{$comic['num']}' style='color: #3498db; text-decoration: none;'>View on XKCD.com</a>
            </div>
        </div>";
        
        return $html;
        
    } catch (Exception $e) {
        error_log("XKCD fetch error: " . $e->getMessage());
        
        // Return fallback content
        return "
        <div style='font-family: Arial, sans-serif; max-width: 700px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #e74c3c; text-align: center;'>Oops! Comic Unavailable</h2>
            <p style='text-align: center; color: #7f8c8d;'>We're having trouble fetching today's comic. Please check back later!</p>
            <div style='text-align: center; margin-top: 20px;'>
                <a href='https://xkcd.com' style='color: #3498db; text-decoration: none;'>Visit XKCD.com directly</a>
            </div>
        </div>";
    }
}

/**
 * Send the formatted XKCD updates to registered emails.
 */
function sendXKCDUpdatesToSubscribers(): void {
    $file = __DIR__ . '/registered_emails.txt';
    
    if (!file_exists($file)) {
        echo "No registered emails file found.\n";
        return;
    }
    
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (empty($emails)) {
        echo "No registered emails found.\n";
        return;
    }
    
    // Get formatted XKCD content
    $comicContent = fetchAndFormatXKCDData();
    
    $subject = "Your Daily XKCD Comic - " . date('F j, Y');
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: XKCD Daily Comics <noreply@xkcdcomics.com>" . "\r\n";
    
    // Add unsubscribe footer
    $emailContent = $comicContent . "
    <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;'>
        <p style='color: #7f8c8d; font-size: 12px;'>
            You're receiving this because you subscribed to XKCD Daily Comics.<br>
            <a href='mailto:unsubscribe@xkcdcomics.com?subject=Unsubscribe&body=Please unsubscribe this email address' style='color: #7f8c8d;'>Unsubscribe</a>
        </p>
    </div>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($emails as $email) {
        $email = trim($email);
        if (empty($email)) continue;
        
        if (mail($email, $subject, $emailContent, $headers)) {
            $successCount++;
            echo " Sent to: {$email}\n";
        } else {
            $errorCount++;
            echo " Failed to send to: {$email}\n";
        }
        
        // Small delay to avoid overwhelming the mail server
        usleep(500000); // 0.5 second delay
    }
    
    echo "\nDelivery Summary:\n";
    echo " Successful: {$successCount}\n";
    echo " Failed: {$errorCount}\n";
    echo "📧 Total subscribers: " . count($emails) . "\n";
}

// Example usage functions for testing
function testFunctions() {
    echo "Testing XKCD functions...\n\n";
    
    // Test verification code generation
    echo "Generated verification code: " . generateVerificationCode() . "\n";
    
    // Test email registration
    $testEmail = "test@example.com";
    if (registerEmail($testEmail)) {
        echo " Email registered successfully\n";
    } else {
        echo " Email registration failed\n";
    }
    
    // Test XKCD data fetching
    echo "\nFetching XKCD comic...\n";
    $comicHtml = fetchAndFormatXKCDData();
    echo "Comic HTML length: " . strlen($comicHtml) . " characters\n";
}

// Uncomment to test the functions
// testFunctions();
?>
