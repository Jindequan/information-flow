<?php
namespace App\Services\Platform;
use App\Core\Config;
class TelegramBotAPI {
    private $config;

    public function __construct() {
        $this->config = Config::config()['telegram_bot'];
    }

    public function sendHtmlFile($filePath) {
        $chatId = $this->config['chat_id'];
        $botToken = $this->config['token'];
        $url = "https://api.telegram.org/bot$botToken/sendDocument";
        
        // Open the file for reading
        $file = new \CURLFile(realpath($filePath));
    
        // Prepare the data
        $postFields = array(
            'chat_id' => $chatId,
            'document' => $file
        );

        $headers = ['Content-Type: multipart/form-data; charset=utf-8'];
    
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Execute cURL session and get the response
        $response = curl_exec($ch);
    
        // Close cURL session
        curl_close($ch);

        return $response;
    }

    private function getUpdates() {
        $botToken = $this->config['token'];
        $url = "https://api.telegram.org/bot$botToken/getUpdates";
        echo $url;
        // Initialize cURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        
        // Close cURL session
        curl_close($ch);
        
        return json_decode($response, true);
    }

    private function deleteWebhook() {
        $botToken = $this->config['token'];
        $url = "https://api.telegram.org/bot$botToken/deleteWebhook";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        
        // Close cURL session
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
}