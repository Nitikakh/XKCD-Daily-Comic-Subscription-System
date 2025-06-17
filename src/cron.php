<?php
require_once 'config.php';
require_once 'includes/email_functions.php';
require_once 'includes/xkcd_functions.php';

// Log file for CRON job
$log_file = __DIR__ . '/cron_log.txt';

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

writeLog("Starting daily XKCD comic delivery...");

try {
    // Get all verified and subscribed users
    $sql = "SELECT * FROM users WHERE is_verified = 1 AND is_subscribed = 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        writeLog("No active subscribers found.");
        exit();
    }
    
    writeLog("Found " . $result->num_rows . " active subscribers.");
    
    // Get random comic
    $comic = getRandomXKCDComic();
    writeLog("Fetched comic: #{$comic['num']} - {$comic['title']}");
    
    $success_count = 0;
    $error_count = 0;
    
    // Send comic to each user
    while ($user = $result->fetch_assoc()) {
        writeLog("Sending to: " . $user['email']);
        
        if (sendComicEmail($user['email'], $comic, $user['id'])) {
            // Log successful delivery
            logComicDelivery($user['id'], $comic, 'sent');
            $success_count++;
            writeLog(" Successfully sent to: " . $user['email']);
        } else {
            // Log failed delivery
            logComicDelivery($user['id'], $comic, 'failed');
            $error_count++;
            writeLog("Failed to send to: " . $user['email']);
        }
        
        // Small delay to avoid overwhelming email server
        sleep(1);
    }
    
    writeLog("Delivery complete! Success: $success_count, Errors: $error_count");
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
}

writeLog("CRON job finished.\n" . str_repeat("-", 50));

// Function to log comic delivery
function logComicDelivery($user_id, $comic_data, $status = 'sent') {
    global $conn;
    
    $sql = "INSERT INTO email_logs (user_id, comic_title, comic_number, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isis", $user_id, $comic_data['title'], $comic_data['num'], $status);
    $stmt->execute();
}
?>
