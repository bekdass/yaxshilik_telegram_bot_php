<?php

// 1. Kerakli fayllarni ulaymiz
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrayerService.php';
require_once __DIR__ . '/LocationService.php';
// Yangi qo'shilgan fayllar:
require_once __DIR__ . '/TelegramService.php';
require_once __DIR__ . '/MessageBuilder.php';
require_once __DIR__ . '/TelegramBot.php';

// 2. Yordamchi servislarni ishga tushiramiz
$db = new Database();
$prayerService = new PrayerService($db);
$locationService = new LocationService();

// Yangi servislarni yaratamiz
$telegramService = new TelegramService(TELEGRAM_BOT_TOKEN);
$messageBuilder = new MessageBuilder();

// 3. Botni yaratamiz (DIQQAT: Bu yerda 6 ta argument bo'lishi SHART)
$bot = new TelegramBot(
    TELEGRAM_BOT_TOKEN,  // 1
    $db,                 // 2
    $prayerService,      // 3
    $locationService,    // 4
    $telegramService,    // 5 (Yangi)
    $messageBuilder      // 6 (Yangi)
);

// 4. Telegramdan kelgan xabarni o'qiymiz
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if ($update) {
    // Botni ishlatamiz
    $bot->handleUpdate($update);
}

// Telegramga javob qaytaramiz
http_response_code(200);
echo 'OK';