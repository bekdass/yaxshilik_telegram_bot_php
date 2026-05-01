<?php

class FakeTelegram {
    public array $sent = [];

    public function sendMessage($chatId, $text, $parseMode = 'HTML', $kb = null) {
        $this->sent[] = compact('chatId','text','parseMode');
        echo "\n--- SEND to {$chatId} ---\n{$text}\n";
        return ['ok' => true];
    }

    public function deleteMessage($chatId, $messageId) {
        // testda shart emas
        return ['ok' => true];
    }
}