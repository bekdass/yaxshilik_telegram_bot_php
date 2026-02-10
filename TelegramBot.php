<?php

class TelegramBot {
    private $token;
    private $db;
    private $prayerService;
    private $locationService;
    private $telegram;
    private $builder;
    
    // Ruxsat berilgan buyruqlar
    private $allowedCommands = [
        '/start', '/help', 
        '🤲 namoz vaqtlari', 'vaqtlar', '/vaqtlar',
        '📍 lokatsiya yangilash',
        '📘 qo\'llanma', 'qo\'llanma'
    ];
    
    public function __construct($token, $db, $prayerService, $locationService, $telegramService, $messageBuilder) {
        $this->token = $token;
        $this->db = $db;
        $this->prayerService = $prayerService;
        $this->locationService = $locationService;
        $this->telegram = $telegramService;
        $this->builder = $messageBuilder;
    }
    
    public function handleUpdate($update) {
        if (!isset($update['message'])) return;
        
        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $messageId = $message['message_id'];
        
        $firstName = $message['from']['first_name'] ?? null;
        $username = $message['from']['username'] ?? null;
        
        // --- 1. LOKATSIYA KELSA ---
        if (isset($message['location'])) {
            $this->handleLocationUpdate($message, $chatId, $userId, $firstName, $username);
            return;
        }
        
        // --- 2. MATN KELSA ---
        if (isset($message['text'])) {
            $text = trim($message['text']);
            $textLower = mb_strtolower($text, 'UTF-8'); // Kichik harfga o'tkazamiz

            // A) Buyruqlar (/start, vaqtlar...)
            if (in_array($textLower, $this->allowedCommands)) {
                
                if ($textLower === '📘 qo\'llanma' || $textLower === 'qo\'llanma') {
                    $msg = $this->builder->getGuideMessage();
                    $kb = $this->builder->getMainMenuKeyboard();
                    $this->telegram->sendMessage($chatId, $msg, 'HTML', $kb);
                    $this->telegram->deleteMessage($chatId, $messageId);
                    return;
                }

                $isStart = ($text === '/start');
                $this->handleUserRequest($chatId, $userId, $firstName, $username, $isStart);
                $this->telegram->deleteMessage($chatId, $messageId);
                return;
            }

            // B) SHAHAR NOMI (Cities.php dan tekshiramiz)
            // Cities::$islomUzIds kalitlari ichidan qidiramiz (toshkent, andijon...)
            if (array_key_exists($textLower, Cities::$islomUzIds)) {
                $this->sendPrayerTimes($chatId, $userId, $firstName, $username, $textLower);
                $this->telegram->deleteMessage($chatId, $messageId);
                return;
            }
            
            // Agar shaharlar ro'yxatida bo'lmasa, lekin Cities::$citysNames da bo'lsa (masalan "Toshkent")
            // Bu ham kerak, chunki user "Toshkent" deb yozishi mumkin
            foreach (Cities::$citysNames as $slug => $name) {
                if (mb_strtolower($name, 'UTF-8') === $textLower) {
                    $this->sendPrayerTimes($chatId, $userId, $firstName, $username, $slug);
                    $this->telegram->deleteMessage($chatId, $messageId);
                    return;
                }
            }

            // Hech narsaga tushmasa -> O'chiramiz
            $this->telegram->deleteMessage($chatId, $messageId);
        }
    }
    
    // ... QOLGAN FUNKSIYALAR O'ZGARISHSIZ ...
    
    private function handleUserRequest($chatId, $userId, $firstName, $username, $isStart) {
        $user = $this->db->getUser($userId);
        $citySlug = 'toshkent';

        if ($user && !empty($user['lat']) && !empty($user['lon'])) {
            $cityData = $this->locationService->findNearestCity($user['lat'], $user['lon']);
            $citySlug = $cityData['city'];
        } else {
            $this->db->addUser($userId, $firstName, $username, 41.2995, 69.2401);
            $citySlug = 'toshkent';
        }

        if ($isStart) {
            $msg = $this->builder->getWelcomeMessage($firstName);
            $kb = $this->builder->getMainMenuKeyboard();
            $this->telegram->sendMessage($chatId, $msg, 'HTML', $kb);
        }

        $this->sendPrayerTimes($chatId, $userId, $firstName, $username, $citySlug);
    }
    
    private function handleLocationUpdate($message, $chatId, $userId, $firstName, $username) {
        $lat = $message['location']['latitude'];
        $lon = $message['location']['longitude'];
        $this->db->addUser($userId, $firstName, $username, $lat, $lon);
        $result = $this->locationService->findNearestCity($lat, $lon);
        $this->sendPrayerTimes($chatId, $userId, $firstName, $username, $result['city']);
    }
    
    public function sendPrayerTimes($chatId, $userId, $firstName, $username, $citySlug) {
        $todayStr = date('Y-m-d');
        if (!$this->db->checkLimit($userId, $todayStr)) {
            $this->telegram->sendMessage($chatId, "⛔️ Limit tugadi!");
            return;
        }
        
        $this->db->addUser($userId, $firstName, $username);
        $data = $this->prayerService->getPrayerTimes($citySlug);
        
        if (!$data) {
            $this->telegram->sendMessage($chatId, "⚠️ Ma'lumot topilmadi. Keyinroq urining.");
            return;
        }
        
        $text = $this->builder->getPrayerTimesMessage($data);
        $kb = $this->builder->getMainMenuKeyboard();
        
        $this->telegram->sendMessage($chatId, $text, 'HTML', $kb);
    }
    
    // Yordamchi metodlar (agar bo'lsa)
    private function apiRequest($url, $data) { /* ... */ } // Bu yerda TelegramService ishlatilyapti, shart emas
}