<?php
class SimpleSMTP {
    private $host = 'smtp.hostinger.com';
    private $port = 465;
    private $username = 'no-reply@yourdomain.com'; // CHANGE THIS
    private $password = 'YourEmailPassword123!';   // CHANGE THIS
    private $fromName = 'KKK LED Shop';

    public function __construct() {
        // You can also load these from a config file if you prefer
    }

    public function send($to, $subject, $htmlMessage) {
        $socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }

        // 1. Handshake
        $this->read($socket);
        $this->write($socket, "EHLO " . $_SERVER['SERVER_NAME']);
        $this->read($socket);

        // 2. Auth
        $this->write($socket, "AUTH LOGIN");
        $this->read($socket);
        $this->write($socket, base64_encode($this->username));
        $this->read($socket);
        $this->write($socket, base64_encode($this->password));
        $this->read($socket);

        // 3. Email Headers & Recipients
        $this->write($socket, "MAIL FROM: <{$this->username}>");
        $this->read($socket);
        $this->write($socket, "RCPT TO: <$to>");
        $this->read($socket);

        // 4. Data
        $this->write($socket, "DATA");
        $this->read($socket);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "From: {$this->fromName} <{$this->username}>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Date: " . date("r") . "\r\n";

        $this->write($socket, $headers . "\r\n" . $htmlMessage . "\r\n.");
        $this->read($socket);

        // 5. Quit
        $this->write($socket, "QUIT");
        fclose($socket);

        return true;
    }

    private function write($socket, $cmd) {
        fwrite($socket, $cmd . "\r\n");
    }

    private function read($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }
}
?>