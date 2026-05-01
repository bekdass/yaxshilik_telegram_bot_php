<?php

class TelegramBot {
    private $token;
    private $db;
    private $prayerService;
    private $locationService;
    private $telegram;
    private $builder;
    
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
        try {
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
            $textLower = mb_strtolower($text, 'UTF-8');
            // 2.1. Qo'llanma (/help) komandalarini alohida ushlaymiz
            if (in_array($textLower, ['/help', '📘 qo\'llanma', 'qo\'llanma'])) {
                $msg = $this->builder->getGuideMessage();          // ⚡ shu metod tayyor deb aytgansiz
                $kb  = $this->builder->getMainMenuKeyboard();

                $this->telegram->sendMessage($chatId, $msg, 'HTML', $kb);
                $this->telegram->deleteMessage($chatId, $messageId);
                return;
            }



            if (in_array($textLower, $this->allowedCommands)) {
                $isStart = ($textLower === '/start');
                $this->handleUserRequest($chatId, $userId, $firstName, $username, $isStart);
                $this->telegram->deleteMessage($chatId, $messageId);
                return;
            }

            // Shahar nomi bo'yicha qidiruv
     
            // Shahar chiroyli nomi bo'yicha qidiruv
           $slug = Cities::resolveCitySlug($text);

            if ($slug) {
                $this->sendPrayerTimes($chatId, $userId, $firstName, $username, $slug);
                $this->telegram->deleteMessage($chatId, $messageId);
                return;
            }

            // Hech narsa topilmasa
            $this->telegram->deleteMessage($chatId, $messageId);
            $kb = $this->builder->getMainMenuKeyboard();
            $res = $this->telegram->sendMessage($chatId, "❗️ Shahringiz lokatsiyasi bo'yicha qidirb ko'ring. Menyudan tanlang.", 'HTML', $kb);
            $this->markBlockedIfNeeded($chatId, $res);
        }
       } catch (Throwable $e) {
        error_log("[BOT] handleUpdate ERROR: ".$e->getMessage()."\n".$e->getTraceAsString());
        // hech bo‘lmasa userga javob qaytadi:
        $this->telegram->sendMessage($chatId ?? 0, "⚠️ Texnik xatolik. Keyinroq urinib ko‘ring.");
    }
    }
    
    private function handleUserRequest($chatId, $userId, $firstName, $username, $isStart) {
        $this->db->addUser($chatId, $firstName, $username);
        $user = $this->db->getUser($chatId);
        
        if ($isStart) {
            $msg = $this->builder->getWelcomeMessage($firstName);
            $kb = $this->builder->getMainMenuKeyboard();
            $this->telegram->sendMessage($chatId, $msg, 'HTML', $kb);
        }

        // Agar foydalanuvchi kordinatasi bo'lsa, o'shandan foydalanamiz
        if ($user && !empty($user['lat']) && !empty($user['lon'])) {
            $this->sendPrayerTimes($chatId, $userId, $firstName, $username, null, false, $user['lat'], $user['lon']);
        } else {
            // Aks holda default Toshkent
            $this->sendPrayerTimes($chatId, $userId, $firstName, $username, 'toshkent');
        }
    }
    
    private function handleLocationUpdate($message, $chatId, $userId, $firstName, $username) {
        $lat = $message['location']['latitude'];
        $lon = $message['location']['longitude'];
        
        // Bazada yangilash (Database.php SRID 0: POINT(Lon Lat) ekanini hisobga oladi)
        $this->db->addUser($chatId, $firstName, $username, $lat, $lon);
        
        // Kordinata orqali vaqtlarni yuborish
        $this->sendPrayerTimes($chatId, $userId, $firstName, $username, null, false, $lat, $lon);
    }
    
    public function sendPrayerTimes($chatId, $userId, $firstName, $username, $citySlug = null, $isCron = false, $lat = null, $lon = null, $farq=false) {
        $todayStr = date('Y-m-d');
        $citySlug = $citySlug ?: 'toshkent';

        // 1. Limitni tekshirish
        if (!$isCron && !$this->db->checkLimit($userId, $todayStr)) {
            $this->telegram->sendMessage($chatId, "⛔️ <b>Kunlik limitga yetildi</b>\n\n Sizga ajratilgan so'rovlar soni bugun uchun yakunlandi. Bot xizmatlaridan <b>soat 00:01 dan boshlab</b> qayta foydalanishingiz mumkin.\n\n<i>E'tiboringiz uchun rahmat.</i>");
            return;
        }
        
        // 2. Ma'lumotni aniqlash (Local yoki Global)
        $data = null;
        if ($lat && $lon) {
            
            $loc = $this->locationService->findNearestCity($lat, $lon);
             if ($isCron && $loc['is_global']){
                return;
             }
            if ($loc['city']) {
            $citySlug = $loc['city'];
            $farq = $loc['farq'];
            }
            if ($loc['is_global']) {
                // O'zbekistondan 300km dan uzoq bo'lsa (Aladhan API)
                $data = $this->prayerService->getPrayerTimes($citySlug, $lat, $lon, $loc['is_global']);
            } else {
                // O'zbekiston ichida (Islom.uz / Namozvaqti.uz)
                $data = $this->prayerService->getPrayerTimes($citySlug, $lat, $lon);
            }
        } else {
            $data = $this->prayerService->getPrayerTimes($citySlug);
        }
        
        // Meridian farqini (daqiqa) Builder uchun qo'shamiz
        if ($data) {
            $data['farq'] = $farq;
            // Agar hudud nomi kelsa saqlaymiz, bo'lmasa Cities dan olamiz
            $data['hudud'] = $data['hudud'] ?? (Cities::$citysNames[$loc['city']] ?? $loc['city']);
        }
        if (!$data) {
            $this->telegram->sendMessage($chatId, "⚠️ Ma'lumot topilmadi.Keyinroq urinib ko'ring."); //\n Agar xorijda bo'lsangiz shahar nomini ingliz tilida jo'natib ko'ring \nyoki 
            return;
        }
        
        // 3. Xabarni shakllantirish va yuborish
        $text = $this->builder->getPrayerTimesMessage($data);
        $kb = $this->builder->getMainMenuKeyboard();
        
        $res = $this->telegram->sendMessage($chatId, $text, 'HTML', $kb);
        $this->markBlockedIfNeeded($chatId, $res);
    }
    private function markBlockedIfNeeded($chatId, $telegramResult): void
        {
            if (!is_array($telegramResult)) return;
            if (($telegramResult['ok'] ?? true) === true) return;

            $code = $telegramResult['error_code'] ?? null;
            $desc = $telegramResult['description'] ?? '';

            if ($code === 403 && (
                stripos($desc, 'bot was blocked by the user') !== false ||
                stripos($desc, 'user is deactivated') !== false
            )) {
                $this->db->deactivateUser($chatId);
            }
        }
}