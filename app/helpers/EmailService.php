<?php

class EmailService {
    // --- CONFIGURATION ---
    private $host = 'smtp.hostinger.com';
    private $port = 465; 
    private $username = 'admin@kkkledshop.com'; 
    private $password = '$Citytaxi02'; 
    private $fromName = 'KKK LED SHOP';
    // ---------------------

    // 1. Generic Send Function
    public function sendEmail($to, $subject, $body) {
        return $this->send($to, $subject, $body);
    }

    // 2. Specific Order Status Function
    public function sendOrderStatusUpdate($toEmail, $customerName, $order, $items, $status) {
        $subject = "Order #{$order['id']} Update: " . ucfirst($status);
        $body = $this->buildEmailBody($customerName, $order, $items, $status);
        
        return $this->send($toEmail, $subject, $body);
    }

    // 3. Core Sending Logic (Socket + Native Fallback)
    private function send($to, $subject, $htmlContent) {
        try {
            if ($this->sendViaSocket($to, $subject, $htmlContent)) {
                return true;
            }
        } catch (Exception $e) {
            error_log("SMTP Socket failed: " . $e->getMessage());
        }
        return $this->sendViaNativeMail($to, $subject, $htmlContent);
    }

    // --- Helper: Socket SMTP ---
    private function sendViaSocket($to, $subject, $htmlContent) {
        $socket = fsockopen("ssl://{$this->host}", $this->port, $errno, $errstr, 10);
        if (!$socket) return false;

        $read = function() use ($socket) {
            $response = "";
            while($str = fgets($socket, 515)) {
                $response .= $str;
                if(substr($str, 3, 1) == " ") break;
            }
            return $response;
        };

        $cmd = function($c) use ($socket, $read) {
            fputs($socket, $c . "\r\n");
            return $read();
        };

        $read(); 
        $cmd("EHLO " . $_SERVER['SERVER_NAME']);
        $cmd("AUTH LOGIN");
        $cmd(base64_encode($this->username));
        $cmd(base64_encode($this->password));
        $cmd("MAIL FROM: <{$this->username}>");
        $cmd("RCPT TO: <$to>");
        $cmd("DATA");

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->username}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Message-ID: <" . uniqid() . "@" . $_SERVER['SERVER_NAME'] . ">\r\n";

        fputs($socket, "$headers\r\n$htmlContent\r\n.\r\n");
        $result = $read();
        $cmd("QUIT");
        fclose($socket);

        return (strpos($result, '250') !== false);
    }

    // --- Helper: Native Mail Fallback ---
    private function sendViaNativeMail($to, $subject, $htmlContent) {
        $sender = 'noreply@' . $_SERVER['HTTP_HOST']; 
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->fromName} <{$sender}>" . "\r\n";
        $headers .= "Reply-To: {$this->username}" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        return mail($to, $subject, $htmlContent, $headers, "-f" . $sender);
    }

    // --- HTML Builder (UPDATED WITH ADDRESS & PHONE) ---
    private function buildEmailBody($name, $order, $items, $status) {
        $statusColor = '#3b82f6';
        $statusMessage = "There is an update regarding your order.";
        $digitalSection = "";

        // Status Logic
        if ($status === 'approved') {
            $statusColor = '#10b981';
            $statusMessage = "Great news! Your order has been approved and is being processed.";
            
            // Digital Items
            $digitalItems = array_filter($items, function($i) { 
                return $i['type'] === 'digital' && !empty($i['download_link']); 
            });

            if (!empty($digitalItems)) {
                $linksHtml = "";
                foreach ($digitalItems as $d) {
                    $linksHtml .= "
                    <li style='margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #bbf7d0;'>
                        <strong style='color:#14532d'>{$d['name']}</strong><br>
                        <a href='{$d['download_link']}' style='display:inline-block; margin-top:5px; background:#16a34a; color:white; padding:5px 10px; text-decoration:none; border-radius:4px; font-size:12px;'>Download</a>
                    </li>";
                }
                $digitalSection = "
                <div style='background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:15px; margin:20px 0;'>
                    <h3 style='margin:0 0 10px 0; color:#15803d; font-size:16px;'>📥 Digital Downloads</h3>
                    <ul style='list-style:none; padding:0; margin:0;'>$linksHtml</ul>
                </div>";
            }
        } elseif ($status === 'completed') {
            $statusColor = '#10b981';
            $statusMessage = "Your order has been delivered successfully. Thank you for shopping with us!";
        } elseif ($status === 'rejected') {
            $statusColor = '#ef4444';
            $statusMessage = "We are sorry, but your order has been rejected. Please contact support.";
        }

        // Items Table
        $itemsHtml = "";
        foreach ($items as $item) {
            $total = $item['price'] * $item['quantity'];
            $itemsHtml .= "
            <tr style='border-bottom:1px solid #eee;'>
                <td style='padding:10px; font-size:13px;'>{$item['name']}</td>
                <td style='padding:10px; text-align:center; font-size:13px;'>{$item['quantity']}</td>
                <td style='padding:10px; text-align:right; font-size:13px;'>" . number_format($total) . "</td>
            </tr>";
        }

        // Handle Delivery/Address info safely
        $address = !empty($order['customer_address']) ? $order['customer_address'] : 'N/A';
        $phone = !empty($order['customer_phone']) ? $order['customer_phone'] : 'N/A';
        $orderDate = isset($order['created_at']) ? date('M d, Y h:i A', strtotime($order['created_at'])) : date('M d, Y');

        return "
        <!DOCTYPE html>
        <html>
        <body style='font-family:sans-serif; background-color:#f3f4f6; padding:20px; margin:0;'>
            <div style='max-width:600px; margin:0 auto; background:white; border-radius:8px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1);'>
                
                <!-- HEADER -->
                <div style='background:#1e293b; padding:20px; text-align:center;'>
                    <h1 style='color:white; margin:0; font-size:20px;'>{$this->fromName}</h1>
                </div>
                
                <!-- STATUS BANNER -->
                <div style='background:{$statusColor}20; padding:15px; text-align:center; border-bottom:1px solid {$statusColor}40;'>
                    <strong style='color:$statusColor; text-transform:uppercase; letter-spacing:1px;'>ORDER " . strtoupper($status) . "</strong>
                </div>

                <div style='padding:25px;'>
                    <p style='margin-top:0;'>Hello <strong>$name</strong>,</p>
                    <p style='color:#555;'>$statusMessage</p>
                    
                    $digitalSection

                    <!-- ORDER DETAILS BOX -->
                    <div style='background-color:#f8fafc; padding:15px; border-radius:6px; margin: 20px 0; border:1px solid #e2e8f0; font-size:13px;'>
                        <h3 style='margin:0 0 10px 0; font-size:14px; border-bottom:1px solid #e2e8f0; padding-bottom:5px;'>📄 Order Details</h3>
                        <table style='width:100%;'>
                            <tr><td style='color:#666; width:40%; padding:3px 0;'>Order ID:</td><td><strong>#{$order['id']}</strong></td></tr>
                            <tr><td style='color:#666; padding:3px 0;'>Date:</td><td>$orderDate</td></tr>
                            <tr><td style='color:#666; padding:3px 0;'>Phone:</td><td>$phone</td></tr>
                            <tr><td style='color:#666; padding:3px 0;'>Address:</td><td>$address</td></tr>
                        </table>
                    </div>

                    <!-- ITEMS TABLE -->
                    <table style='width:100%; border-collapse:collapse; margin-top:10px;'>
                        <tr style='background:#1e293b; color:white; text-align:left;'>
                            <th style='padding:10px; font-size:12px; border-top-left-radius:4px;'>Item</th>
                            <th style='padding:10px; text-align:center; font-size:12px;'>Qty</th>
                            <th style='padding:10px; text-align:right; font-size:12px; border-top-right-radius:4px;'>Price</th>
                        </tr>
                        $itemsHtml
                        <tr>
                            <td colspan='2' style='padding:15px 10px; text-align:right; font-weight:bold; border-top:2px solid #e2e8f0;'>Grand Total</td>
                            <td style='padding:15px 10px; text-align:right; font-weight:bold; color:#1e293b; border-top:2px solid #e2e8f0;'>" . number_format($order['total_amount']) . " MMK</td>
                        </tr>
                    </table>

                    <div style='margin-top:30px; padding-top:20px; border-top:1px solid #eee; text-align:center; font-size:12px; color:#999;'>
                        <p>Thank you for shopping with KKK LED SHOP.</p>
                        <p>Need help? Contact us at {$this->username}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>