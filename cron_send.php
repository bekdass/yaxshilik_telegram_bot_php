<?php
// ... (tepadagi require_once qismlariga TelegramService va MessageBuilder ni qo'shing) ...
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrayerService.php';
require_once __DIR__ . '/LocationService.php';
require_once __DIR__ . '/TelegramService.php'; // Qo'shing
require_once __DIR__ . '/MessageBuilder.php'; // Qo'shing
require_once __DIR__ . '/TelegramBot.php';

// Servislarni ishga tushiramiz
$db = new Database();
$prayerService = new PrayerService($db);
$locationService = new LocationService();
$telegramService = new TelegramService(TELEGRAM_BOT_TOKEN); // Yangi
$messageBuilder = new MessageBuilder(); // Yangi

// Keshni tozalash
echo "🧹 Eski kesh tozalanmoqda... ";
$db->cleanOldCache();
echo "OK\n";

// Botni yaratish (6 ta argument bilan)
$bot = new TelegramBot(
    TELEGRAM_BOT_TOKEN,
    $db,
    $prayerService,
    $locationService,
    $telegramService,
    $messageBuilder
);
// 1. Userlarni olamiz
$users = $db->getAllUsers();
$count = count($users);
echo "👥 Jami foydalanuvchilar: $count ta\n";

foreach ($users as $user) {
    $chatId = $user['chat_id'];
    $name = $user['first_name'];
    
    // 2. Lokatsiyani aniqlash
    // Agar bazada lokatsiya bo'lsa (lat/lon 0 bo'lmasa)
    if (!empty($user['lat']) && !empty($user['lon'])) {
        // POINT(lon lat) -> Bizga Lat, Lon kerak. 
        // Bazada ST_X = lon, ST_Y = lat bo'lishi mumkin (MySQL versiyasiga qarab).
        // Hozircha Lat=X, Lon=Y deb faraz qilamiz yoki teskarisi.
        // LocationService::findNearestCity($lat, $lon)
        
        $cityData = $locationService->findNearestCity($user['lat'], $user['lon']);
        $citySlug = $cityData['city'];
    } else {
        // Lokatsiya bo'lmasa Default Toshkent
        $citySlug = 'toshkent';
    }

    echo "📨 Yuborilyapti: $chatId ($name) -> $citySlug... ";

    // 3. Xabarni yuborish
    // sendPrayerTimes funksiyasini public qilgan bo'lishingiz shart!
    $bot->sendPrayerTimes(
        $chatId, 
        $chatId, // userId o'rniga chatId ketaveradi
        $name, 
        '', 
        $user['username'], 
        $citySlug
    );
    
    echo "OK\n";

    // Telegram limitga tushmaslik uchun ozgina kutamiz (0.1 soniya)
    usleep(100000); 
}

echo "✅ Tugadi: " . date('Y-m-d H:i:s') . "\n";
