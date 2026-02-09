<?php

class TelegramService {
    private $token;
    private $apiUrl;

    public function __construct($token) {
        $this->token = $token;
        $this->apiUrl = "https://api.telegram.org/bot{$token}/";
    }

    public function sendMessage($chatId, $text, $parseMode = 'HTML', $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }
        
        return $this->request('sendMessage', $data);
    }

    public function deleteMessage($chatId, $messageId) {
        return $this->request('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ]);
    }

    private function request($method, $data) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $method,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            file_put_contents(__DIR__.'/error.log', "Curl Error: " . curl_error($ch) . "\n", FILE_APPEND);
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }
}