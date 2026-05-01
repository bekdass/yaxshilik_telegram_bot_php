<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PrayerService.php';
require_once __DIR__ . '/LocationService.php';
require_once __DIR__ . '/TelegramService.php'; // Qo'shing
require_once __DIR__ . '/MessageBuilder.php'; // Qo'shing
require_once __DIR__ . '/TelegramBot.php';

// Log faylini tozalash (agar har safar yangidan boshlamoqchi bo'lsangiz)
file_put_contents(__DIR__.'/cron.log', '');

// Servislarni ishga tushiramiz
$db = new Database();
$prayerService = new PrayerService($db);
$locationService = new LocationService();
$telegramService = new TelegramService(TELEGRAM_BOT_TOKEN); // Yangi
$messageBuilder = new MessageBuilder(); // Yangi


$logOutput = ""; // Log uchun alohida o'zgaruvchi
$limitDate = date('Y-m-d', strtotime('-1 day'));

$logOutput .= "🧹 " . $limitDate . " sanasidan oldingi barcha eski keshlar tozalanmoqda... ";
$db->cleanOldCache();
$logOutput .= "OK\n";
// Botni yaratish (6 ta argument bilan)
$bot = new TelegramBot(
    TELEGRAM_BOT_TOKEN,
    $db,
    $prayerService,
    $locationService,
    $telegramService,
    $messageBuilder
);

$isCron = true;
$users = $db->getAllUsers($isCron);
$count = count($users);
$logOutput .= "👥 Jami foydalanuvchilar: $count ta\n";

foreach ($users as $user) {
    $chatId = $user['chat_id'];
    $name = $user['first_name'];
    
    // Har bir user uchun logga qo'shamiz
    $logOutput .= "📨 Yuborilyapti: $chatId ($name) -> ";

    $bot->sendPrayerTimes(
        $chatId, 
        $chatId, 
        $name, 
        $user['username'], 
        '',
        true,
        $user['lat'],
        $user['lon']
    );
    
    $logOutput .= "OK\n";
    usleep(100000); 
}

$finishTime = date('Y-m-d H:i:s');
$logOutput .= "✅ Tugadi: $finishTime\n";

// Ekranga chiqarish
echo $logOutput;

// Faylga yozish (faqat bir marta va toza holatda)
$finalLog = "[" . $finishTime . "]\n" . $logOutput . "--------------------------\n";
file_put_contents(__DIR__ . '/cron.log', $finalLog, FILE_APPEND);