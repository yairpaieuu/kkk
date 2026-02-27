<?php
// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Telegram Test Debugger</h1>";

// 1. Load Service
$file = __DIR__ . '/app/helpers/TelegramService.php';
if (!file_exists($file)) {
    die("❌ Error: TelegramService.php not found at $file");
}
require_once $file;

// 2. Read File to check Config (Security check)
$content = file_get_contents($file);
if (strpos($content, 'YOUR_BOT_TOKEN_HERE') !== false) {
    die("❌ Error: You haven't replaced 'YOUR_BOT_TOKEN_HERE' with your actual token in app/helpers/TelegramService.php");
}

// 3. Try to Send
echo "Attempting to send message...<br>";

try {
    $tg = new TelegramService();
    
    // Dummy Data
    $dummyItems = [
        ['name' => 'Test Product A', 'price' => 5000, 'qty' => 2],
        ['name' => 'Test Product B', 'price' => 12000, 'qty' => 1]
    ];
    
    $result = $tg->sendOrderNotification(9999, "Test User", 22000, $dummyItems);
    
    $json = json_decode($result, true);
    
    if ($json['ok']) {
        echo "<h2 style='color:green'>✅ SUCCESS! Check your Telegram Channel.</h2>";
    } else {
        echo "<h2 style='color:red'>❌ FAILED TO SEND</h2>";
        echo "<strong>Telegram Error Code:</strong> " . $json['error_code'] . "<br>";
        echo "<strong>Description:</strong> " . $json['description'] . "<br>";
        
        if ($json['error_code'] == 400 && strpos($json['description'], 'chat not found') !== false) {
            echo "<br>💡 <strong>Solution:</strong> Your Chat ID is wrong. Remember to add -100 at the start for channels.";
        }
        if ($json['error_code'] == 401) {
            echo "<br>💡 <strong>Solution:</strong> Your Bot Token is incorrect.";
        }
        if ($json['error_code'] == 403) {
            echo "<br>💡 <strong>Solution:</strong> The Bot is not an Admin in the channel.";
        }
    }
    
    echo "<br><br><strong>Raw Response:</strong><br>";
    echo "<pre>" . print_r($json, true) . "</pre>";

} catch (Exception $e) {
    echo "❌ Exception Error: " . $e->getMessage();
}
?>