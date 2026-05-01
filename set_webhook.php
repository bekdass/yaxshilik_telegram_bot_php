<?php
/**
 * Set Webhook Utility
 * Telegram serveriga bot manzilini va maxfiy kalitni tanishtirish
 */

// Config fayl (Token uchun)
require_once __DIR__ . '/config.php';

// -------------------------------------------------------------------
// 1. SOZLAMALAR (O'zingizga moslab o'zgartiring)
// -------------------------------------------------------------------

// Bot joylashgan papka manzili
$baseUrl = 'https://musbat.uz/telegram_botlar/harkunyaxshilik-bot';

// Asosiy bot fayli nomi (Xavfsizlik uchun nomini qiyinroq qilish tavsiya etiladi)
// Masalan: 'index.php' emas, 'bot_run_x9s82.php' qiling.
$botFileName = 'AAFQB1tVlmo-HarKunYaxshilik_bot.php'; 

// Xavfsizlik kaliti (Secret Token)
// Buni o'zingiz o'ylab toping va ESLAB QOLING! 
// Bot faylingiz (index.php) ichida ham aynan shuni tekshirasiz.
$mySecretToken = SIRLI_KALIT; 

// To'liq Webhook URL
$webhookUrl = $baseUrl . '/' . $botFileName;


// -------------------------------------------------------------------
// 2. Webhookni o'rnatish jarayoni
// -------------------------------------------------------------------

echo "<h3>Telegram Webhook Sozlash</h3>";
echo "📡 URL: <code>$webhookUrl</code><br>";
echo "🔒 Secret Token: <code>$mySecretToken</code><br><br>";

// Parametrlarni tayyorlash
$params = [
    'url' => $webhookUrl,
    'secret_token' => $mySecretToken,
    // 'drop_pending_updates' => true // Agar eski xabarlar tiqilib qolgan bo'lsa, buni yoqish mumkin
];

// API so'rovini shakllantirish
$apiUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook?" . http_build_query($params);

// So'rovni bajarish
$response = @file_get_contents($apiUrl);
$result = json_decode($response, true);

// 3. Natijani ko'rsatish
if ($result && $result['ok']) {
    echo "<div style='color:green; border:1px solid green; padding:10px;'>";
    echo "✅ <b>Muvaffaqiyatli o'rnatildi!</b><br>";
    echo "Telegram javobi: " . htmlspecialchars($result['description']);
    echo "</div>";
    
    echo "<br><b>Endi nima qilish kerak?</b><br>";
    echo "Bot faylingiz (<i>$botFileName</i>) eng yuqorisiga quyidagi kodni qo'shing:<br>";
    echo "<pre style='background:#f4f4f4; padding:10px;'>";
    echo "\$secret_token = \$_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';\n";
    echo "if (\$secret_token !== '$mySecretToken') {\n";
    echo "    http_response_code(403);\n";
    echo "    die('Access Denied');\n";
    echo "}";
    echo "</pre>";
    
} else {
    echo "<div style='color:red; border:1px solid red; padding:10px;'>";
    echo "❌ <b>Xatolik yuz berdi!</b><br>";
    echo "Sabab: " . ($result['description'] ?? 'Noma\'lum xato. Token yoki URL xato bo\'lishi mumkin.');
    echo "</div>";
}
?>
