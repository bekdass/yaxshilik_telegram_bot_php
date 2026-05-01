<?php
date_default_timezone_set('Asia/Tashkent');
// Hamma xatolarni ko'rsatish (ishlab chiqish jarayonida)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =======================================================================
// 1-QISM: XAVFSIZLIK (ENG MUHIMI)
// =======================================================================
require_once __DIR__ . '/config.php';
// A) Secret Token tekshiruvi (Majburiy)
$mySecretToken = SIRLI_KALIT;  // Webhook sozlangan parolingiz
$incoming_token = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';

if ($incoming_token !== $mySecretToken) {
    http_response_code(403);
    die("Xatolik: Maxfiy kalit noto'g'ri!");
}

// B) IP Manzil tekshiruvi (Ixtiyoriy, lekin tavsiya etiladi)
function isTelegramIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Agar Cloudflare ishlatayotgan bo'lsangiz, haqiqiy IP ni olish uchun:
    // if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];

    // Telegram IP diapazonlari (CIDR formatida)
    $telegram_ranges = [
        '149.154.160.0/20',
        '91.108.4.0/22'
    ];

    foreach ($telegram_ranges as $range) {
        list($subnet, $bits) = explode('/', $range);
        $ip_decimal = ip2long($ip);
        $subnet_decimal = ip2long($subnet);
        $mask_decimal = -1 << (32 - $bits);
        
        // Agar IP shu diapazonga to'g'ri kelsa
        if (($ip_decimal & $mask_decimal) == ($subnet_decimal & $mask_decimal)) {
            return true;
        }
    }
    return false;
}

if (!isTelegramIP()) {
    http_response_code(403);
    die("Xatolik: Faqat Telegram serverlariga ruxsat berilgan. Sizning IP: " . $_SERVER['REMOTE_ADDR']);
}
// 1. Kerakli fayllarni ulaymiz

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