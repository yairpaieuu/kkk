<?php
// Enable error reporting to see any hidden issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📧 Email System Diagnostic Test</h1>";
echo "<hr>";

// --- CONFIGURATION ---
// Change this to the email you want to test receiving on
$to_email = "admin@kkkledshop.com"; 

// --- TEST 1: PHP NATIVE MAIL() ---
echo "<h3>Test 1: PHP Native mail() Function</h3>";

$subject = "Test Email from PHP mail()";
$message = "This is a test email sent using PHP's native mail() function. If you see this, the server can send emails.";
$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
           "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
           "X-Mailer: PHP/" . phpversion();

if (mail($to_email, $subject, $message, $headers)) {
    echo "<p style='color:green'>✅ PHP mail() function returned TRUE. Check your inbox (and spam folder) for: <strong>$to_email</strong></p>";
} else {
    echo "<p style='color:red'>❌ PHP mail() function returned FALSE. The server configuration might be blocking emails.</p>";
}

echo "<hr>";

// --- TEST 2: CUSTOM EmailService CLASS ---
echo "<h3>Test 2: Custom EmailService Class</h3>";

// Adjust this path if your folder structure is different
// Based on your controller, it seems to be in app/helpers/EmailService.php
$path = __DIR__ . '/app/helpers/EmailService.php';

if (file_exists($path)) {
    require_once $path;
    echo "<p>Found EmailService file at: $path</p>";

    if (class_exists('EmailService')) {
        try {
            $service = new EmailService();
            echo "<p>EmailService class instantiated successfully.</p>";
            
            // Attempt to send
            echo "<p>Attempting to send via EmailService...</p>";
            
            // Note: This assumes your sendEmail method takes ($to, $subject, $body)
            // If it returns true/false, we can capture it. If it's void, we catch exceptions.
            $service->sendEmail($to_email, "Test from EmailService", "This is a test from your custom EmailService class.");
            
            echo "<p style='color:green'>✅ EmailService->sendEmail() executed without throwing an exception.</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ EmailService Error: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    } else {
        echo "<p style='color:red'>❌ File exists, but class 'EmailService' was not found inside it.</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ Could not find EmailService.php at: <code>$path</code></p>";
    echo "<p>If your `app` folder is not in the root, you might need to adjust the path in this test file.</p>";
}

echo "<hr>";
echo "<p><em>Diagnostic finished. Delete this file after testing.</em></p>";
?>