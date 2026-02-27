<?php
// Simple PHP Mailer Script to Test SMTP Connection
// Save this as email_test.php in your public folder

// --- CONFIGURATION (EDIT THESE) ---
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 465;
$smtp_user = 'admin@kkkledshop.com'; // REPLACE THIS
$smtp_pass = '$Citytaxi02';         // REPLACE THIS
$to_email  = 'saturnboy02@gmail.com'; // REPLACE THIS (Where to send the test)
// ----------------------------------

echo "<h2>SMTP Email Test</h2>";
echo "<p>Testing connection to <strong>$smtp_host</strong> on port <strong>$smtp_port</strong>...</p>";

try {
    // 1. Open Socket Connection
    $socket = fsockopen("ssl://$smtp_host", $smtp_port, $errno, $errstr, 10);
    
    if (!$socket) {
        throw new Exception("Could not connect to SMTP host. Error: $errstr ($errno)");
    }
    
    echo "<p style='color:green'>&#10004; Socket connected successfully.</p>";
    
    // Helper function to read server response
    function read_response($socket) {
        $response = "";
        while($str = fgets($socket, 515)) {
            $response .= $str;
            if(substr($str, 3, 1) == " ") { break; }
        }
        return $response;
    }

    // Helper function to write command
    function send_command($socket, $cmd) {
        fputs($socket, $cmd . "\r\n");
        return read_response($socket);
    }

    read_response($socket); // Read initial greeting

    // 2. EHLO Command
    send_command($socket, "EHLO " . $_SERVER['SERVER_NAME']);

    // 3. AUTH LOGIN
    send_command($socket, "AUTH LOGIN");
    
    // 4. Send Encoded Username
    $user_response = send_command($socket, base64_encode($smtp_user));
    if (strpos($user_response, '334') === false && strpos($user_response, '235') === false) {
        throw new Exception("Username rejected: $user_response");
    }

    // 5. Send Encoded Password
    $pass_response = send_command($socket, base64_encode($smtp_pass));
    if (strpos($pass_response, '235') === false) {
        throw new Exception("Password rejected. Check your credentials.");
    }
    echo "<p style='color:green'>&#10004; Authentication successful.</p>";

    // 6. MAIL FROM
    send_command($socket, "MAIL FROM: <$smtp_user>");

    // 7. RCPT TO
    send_command($socket, "RCPT TO: <$to_email>");

    // 8. DATA
    send_command($socket, "DATA");

    // 9. Message Body
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Test Script <$smtp_user>\r\n";
    $headers .= "To: $to_email\r\n";
    $headers .= "Subject: Hostinger SMTP Test\r\n";
    
    $message  = "Hello,\r\n\r\nThis is a test email sent directly via PHP fsockopen to verify Hostinger SMTP settings.\r\n\r\nTime: " . date("Y-m-d H:i:s");

    fputs($socket, "$headers\r\n$message\r\n.\r\n");
    $result = read_response($socket);

    if (strpos($result, '250') !== false) {
        echo "<h3 style='color:green'>SUCCESS! Email queued for delivery.</h3>";
        echo "<p>Server response: $result</p>";
    } else {
        throw new Exception("Message rejected by server: $result");
    }

    // 10. QUIT
    send_command($socket, "QUIT");
    fclose($socket);

} catch (Exception $e) {
    echo "<h3 style='color:red'>FAILED</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Troubleshooting:</strong><br>";
    echo "1. Double check your password.<br>";
    echo "2. Ensure 'ssl://' is enabled in php.ini (extension=openssl).<br>";
    echo "3. Hostinger outgoing settings: smtp.hostinger.com, Port 465, SSL.</p>";
}
?>