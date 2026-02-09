<?php
/**
 * Set Webhook Utility
 * Telegram serveriga bot manzilini tanishtirish
 */

// Faqat config kerak (Token uchun)
require_once __DIR__ . '/config.php';

// 1. Webhook manzilingizni shu yerga yozing!
// Masalan: 'https://sizning-sayt.uz/bot-papka/index.php'
$webhookUrl = 'https://yourdomain.com/index.php'; 

// Agar URL o'zgartirilmagan bo'lsa, ogohlantiramiz
if ($webhookUrl === 'https://yourdomain.com/index.php') {
    die("❌ Iltimos, faylni ochib <b>\$webhookUrl</b> ga o'z domeningizni yozing!");
}

echo "<h3>Telegram Webhook Sozlash</h3>";
echo "📡 URL: <code>$webhookUrl</code><br><br>";

// 2. Telegram API ga so'rov yuborish
$apiUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);

// So'rovni bajarish
$response = file_get_contents($apiUrl);
$result = json_decode($response, true);

// 3. Natijani ko'rsatish
if ($result && $result['ok']) {
    echo "✅ <b>Muvaffaqiyatli o'rnatildi!</b><br>";
    echo "Javob: " . htmlspecialchars($result['description']);
} else {
    echo "❌ <b>Xatolik yuz berdi!</b><br>";
    echo "Sabab: " . ($result['description'] ?? 'Noma\'lum xato');
}
?>