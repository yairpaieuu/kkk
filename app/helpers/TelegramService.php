<?php

class TelegramService {
    // --- CONFIGURATION ---
    private $botToken = '8327178521:AAGaZcvK3_YMD1G-J3pizXFcGzGFeRzpj6k'; 
    private $chatId = '-1003868140865';     
    // ---------------------

    public function sendOrderNotification($orderId, $customerName, $totalAmount, $items, $receiptPath = null) {
        // --- 1. BUILD CAPTION / MESSAGE ---
        $date = date('d M Y, h:i A');
        $total = number_format($totalAmount);
        
        $message = "<b>🆕 NEW ORDER RECEIVED</b>\n";
        $message .= "➖➖➖➖➖➖➖➖➖➖\n\n";
        $message .= "🆔 <b>Order ID:</b> <code>#{$orderId}</code>\n";
        $message .= "👤 <b>Customer:</b> {$customerName}\n";
        $message .= "📅 <b>Date:</b> {$date}\n\n";
        
        $message .= "<b>🛒 ORDER DETAILS:</b>\n";
        foreach ($items as $item) {
            $price = number_format($item['price']);
            $subtotal = number_format($item['price'] * $item['qty']);
            $message .= "▫️ <b>{$item['name']}</b>\n";
            $message .= "   └ <i>{$item['qty']} x {$price} = {$subtotal} Ks</i>\n";
        }
        
        $message .= "\n➖➖➖➖➖➖➖➖➖➖\n";
        $message .= "💰 <b>TOTAL AMOUNT: {$total} Ks</b>\n";
        $message .= "➖➖➖➖➖➖➖➖➖➖";

        // --- 2. SEND LOGIC (PHOTO vs TEXT) ---
        if ($receiptPath && file_exists(__DIR__ . '/../../' . $receiptPath)) {
            // Send Photo with Caption
            return $this->sendPhoto(__DIR__ . '/../../' . $receiptPath, $message);
        } else {
            // Send Text Only (Fallback)
            return $this->sendMessage($message);
        }
    }

    // --- Helper: Send Text ---
    private function sendMessage($text) {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = ['chat_id' => $this->chatId, 'text' => $text, 'parse_mode' => 'HTML'];
        return $this->executeCurl($url, $data);
    }

    // --- Helper: Send Photo ---
    private function sendPhoto($filePath, $caption) {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";
        
        // Prepare file for upload
        $cFile = new CURLFile($filePath);
        
        $data = [
            'chat_id' => $this->chatId,
            'photo' => $cFile,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        return $this->executeCurl($url, $data);
    }

    // --- Helper: Execute Request ---
    private function executeCurl($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log("Telegram Curl Error: " . curl_error($ch));
        } else {
            $response = json_decode($result, true);
            if (!$response['ok']) {
                error_log("Telegram API Error: " . $response['description']);
            }
        }
        
        curl_close($ch);
        return $result;
    }
}
?>