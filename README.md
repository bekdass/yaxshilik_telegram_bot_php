Markdown# 🌙 Har Kuni Yaxshilik - Namoz Vaqtlari Boti (PHP)

**Har Kuni Yaxshilik** — bu O'zbekiston hududidagi namoz vaqtlarini aniq va ishonchli ko'rsatib beruvchi Telegram boti. Loyiha PHP tilida yozilgan bo'lib, ma'lumotlarni bir nechta manbalardan (Muslim.uz, Islom.uz, Namozvaqti.uz) avtomatik tarzda yig'adi va aqlli kesh (smart cache) tizimidan foydalanadi.

![License](https://img.shields.io/badge/license-Apache%202.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)
![Telegram](https://img.shields.io/badge/Telegram-Bot%20API-blue.svg)

## 🚀 Asosiy Imkoniyatlar

- 🔄 **Multi-Manba (Fallback Tizimi):** Bot ma'lumotni 3 ta manbadan qidiradi. Agar birinchisi ishlamasa, avtomatik ikkinchisiga o'tadi:
  1. **Muslim.uz** (Rasmiy manba, Toshkent uchun)
  2. **Islom.uz** (Barcha viloyatlar uchun)
  3. **Namozvaqti.uz** (Zaxira manba)
- 📍 **Geolokatsiya:** Foydalanuvchi lokatsiya yuborganda, **Haversine formulasi** orqali unga eng yaqin shaharni aniqlaydi.
- 💾 **Aqlli Kesh (SQLite):** Har safar saytga so'rov yubormaslik uchun ma'lumotlarni kunlik keshga oladi. Bu botning ishlash tezligini 10x oshiradi.
- 📝 **Formatlash:** Kirill alifbosidagi ma'lumotlarni (saytlardan kelgan) avtomatik **Lotin** alifbosiga o'giradi.
- 🛡 **Limitlar:** Serverni zo'riqishdan saqlash uchun har bir foydalanuvchiga kunlik so'rov limiti o'rnatilgan.

## 🛠 Texnik Talablar

Loyiha ishlashi uchun serveringizda quyidagilar bo'lishi kerak:

- **PHP 8.1** yoki yuqori versiya.
- **SQLite3** (Ma'lumotlar bazasi uchun).
- **cURL** (Saytlardan ma'lumot olish uchun).
- **DOMDocument & XML** (HTML parsing uchun).
- **MBString** (Matnlar bilan ishlash uchun).

## 📥 O'rnatish va Ishga Tushirish

### 1-qadam: Loyihani yuklab olish
Loyihani serveringizga yuklang yoki git orqali klonlang:

```bash
git clone [https://github.com/bekdass/harkunyaxshilik-bot.git](https://github.com/bekdass/harkunyaxshilik-bot.git)
cd harkunyaxshilik-bot
2-qadam: Konfiguratsiyaconfig.example.php faylidan nusxa oling va nomini config.php ga o'zgartiring:Bashcp config.example.php config.php
nano config.php
Fayl ichidagi quyidagi qatorga o'z Telegram Bot Tokeningizni yozing:PHPdefine('TELEGRAM_BOT_TOKEN', '1234567890:BU_YERGA_TOKEN_YOZILADI');
3-qadam: Ruxsatlarni (Permissions) to'g'irlashBot papkasiga va bazaga yozish huquqini bering:Bashchmod -R 755 /var/www/telegram_botlar/harkunyaxshilik-bot/
(Birinchi marta ishga tushganda database.sqlite fayli avtomatik yaratiladi).4-qadam: Webhookni ulashBot ishlashi uchun Telegram serveriga webhook manzilingizni ko'rsatishingiz kerak.Brauzerda quyidagi manzilni oching (o'z domeningiz bilan):[https://sizning-domeningiz.uz/bot-papkasi/index.php](https://sizning-domeningiz.uz/bot-papkasi/index.php)
Yoki set_webhook.php skriptidan foydalaning.🗄 Ma'lumotlar Bazasi Tuzilishi (Schema)Bot SQLite bazasidan foydalanadi. Baza fayli (database.sqlite) kod birinchi marta ishlaganda avtomatik yaratiladi. Agar qo'lda yaratmoqchi bo'lsangiz, jadvallar tuzilishi quyidagicha:1. users jadvali (Foydalanuvchilar)Foydalanuvchilar va ularning oxirgi lokatsiyasini saqlaydi.UstunTuriTavsifidINTEGER PRIMARY KEYAvtomatik IDuser_idINTEGER UNIQUETelegram User IDfirst_nameTEXTIsmilast_nameTEXTFamiliyasiusernameTEXTTelegram userneymilatREALKenglik (Latitude)lonREALUzunlik (Longitude)created_atDATETIMERo'yxatdan o'tgan vaqtiSQLCREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY,
    user_id INTEGER UNIQUE,
    first_name TEXT,
    last_name TEXT,
    username TEXT,
    lat REAL,
    lon REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
2. tg_prayer_cache jadvali (Vaqtlar Keshi)Saytlardan olingan ma'lumotlarni 24 soat davomida saqlab turadi.UstunTuriTavsifcity_slugTEXTShahar kodi (masalan: toshkent)dateTEXTSana (YYYY-MM-DD)dataTEXTNamoz vaqtlari (JSON formatda)updated_atDATETIMEYangilangan vaqtiSQLCREATE TABLE IF NOT EXISTS tg_prayer_cache (
    city_slug TEXT,
    date TEXT,
    data TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(city_slug, date)
);
3. requests jadvali (Limitlar)Foydalanuvchining kunlik so'rovlar sonini hisoblaydi.UstunTuriTavsifidINTEGER PRIMARY KEYAvtomatik IDuser_idINTEGERTelegram User IDrequest_dateDATESo'rov sanasirequest_countINTEGERSo'rovlar soniSQLCREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    request_date DATE NOT NULL,
    request_count INTEGER DEFAULT 1,
    UNIQUE(user_id, request_date)
);
📂 Loyiha TuzilishiPlaintext/
├── index.php              # Asosiy kirish nuqtasi (Webhook)
├── config.php             # Sozlamalar fayli (Token, Limitlar)
├── Database.php           # SQLite bilan ishlash klassi
├── TelegramBot.php        # Bot logikasi va boshqaruv
├── PrayerService.php      # Parsing (Vaqtlarni saytlardan olish)
├── LocationService.php    # Masofa hisoblash (Geolokatsiya)
├── TelegramService.php    # Telegram API ga so'rov yuborish
├── MessageBuilder.php     # Javob matnlari va tugmalar
├── Cities.php             # Shaharlar koordinatalari va ID lari
└── .htaccess             # Server xavfsizligi

📄 Litsenziya va Mualliflik HuquqiUshbu loyiha Apache License 2.0 ostida tarqatilmoqda.Litsenziya Shartlari:Siz ushbu koddan nusxa olishingiz, o'zgartirishingiz va tarqatishingiz mumkin, lekin quyidagi shartlarga amal qilishingiz kerak:Mualliflikni ko'rsatish: Dasturning asl nusxasi yoki o'zgartirilgan versiyasida ushbu litsenziya matni va mualliflik huquqi (Copyright) saqlanib qolishi shart.Javobgarlik: Dastur "boricha" (AS IS) taqdim etiladi. Muallif foydalanish natijasida kelib chiqadigan har qanday zarar uchun javobgar emas.Mualliflik Huquqi (Copyright):Ushbu loyiha Ulugbek Nematullayev tomonidan ishlab chiqilgan va Python versiyasidan PHP tiliga o'girilgan (ported).Copyright © 2026 Ulugbek Nematullayev (@bekdass)Loyiha O'zbekiston musulmonlari uchun qulaylik yaratish maqsadida ishlab chiqildi.


# Telegram Prayer Times Bot (PHP Version)

This is a complete PHP port of the Python Telegram bot for Uzbekistan prayer times.

## Features

- 🕌 Fetches prayer times from 3 sources with automatic fallback:
  - Muslim.uz (Official, Tashkent only)
  - Islom.uz (Comprehensive, all cities)
  - Namozvaqti.uz (Backup)
- 📍 Location-based nearest city detection using meridian calculation
- 💾 SQLite database for user management and request limiting
- 🔄 Daily caching to reduce API calls
- 📊 Daily request limit per user
- 🌐 Cyrillic to Latin text conversion

## File Structure

```
/
├── index.php              # Webhook handler (entry point)
├── config.php             # Configuration settings
├── Database.php           # SQLite database class
├── PrayerService.php      # Prayer times fetching service
├── LocationService.php    # Location/distance calculations
├── TelegramBot.php        # Bot logic and message handling
├── Cities.php             # Uzbekistan cities coordinates
├── .htaccess             # Apache configuration
├── database.sqlite       # SQLite database (auto-created)
└── README.md             # This file
```

## Installation

### 1. Upload Files

Upload all PHP files to your shared hosting account.

### 2. Configure Bot Token

Edit `config.php` and replace `YOUR_BOT_TOKEN_HERE` with your actual Telegram Bot Token:

```php
define('TELEGRAM_BOT_TOKEN', '1234567890:ABCdefGHIjklMNOpqrsTUVwxyz');
```

### 3. Set Webhook

Use this URL to set your webhook (replace with your domain):

```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://yourdomain.com/index.php
```

Or use this PHP script (save as `setWebhook.php`):

```php
<?php
require_once 'config.php';

$url = "https://yourdomain.com/index.php"; // Your webhook URL

$response = file_get_contents(
    "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . 
    "/setWebhook?url=" . urlencode($url)
);

echo $response;
?>
```

### 4. Set Permissions

Make sure the directory is writable for the database:

```bash
chmod 755 /path/to/bot/
chmod 666 /path/to/bot/database.sqlite  # After first run
```

## Configuration

Edit `config.php` to customize:

- `TELEGRAM_BOT_TOKEN`: Your bot token from BotFather
- `DAILY_LIMIT`: Maximum requests per user per day (default: 10)
- `DEBUG_MODE`: Enable logging (default: true)
- `REQUEST_TIMEOUT`: HTTP request timeout in seconds (default: 10)

## Usage

Users can interact with the bot using:

- `/start` - Start the bot and get welcome message
- `/help` - Get help information
- `vaqtlar` - Get Tashkent prayer times
- `toshkent` - Get Tashkent prayer times
- **Send location** - Get prayer times for nearest city

## Bot Commands

The bot responds to:

- Text: "vaqtlar", "namoz vaqtlari", "toshkent"
- Location: Automatically finds nearest city by meridian

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    user_id INTEGER UNIQUE NOT NULL,
    first_name TEXT,
    last_name TEXT,
    username TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

### Requests Table
```sql
CREATE TABLE requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    request_date DATE NOT NULL,
    request_count INTEGER DEFAULT 1,
    UNIQUE(user_id, request_date)
)
```

## Troubleshooting

### Bot not responding

1. Check if webhook is set correctly:
```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo
```

2. Check file permissions
3. Enable `DEBUG_MODE` in `config.php` and check `updates.log`

### Database errors

1. Ensure directory is writable
2. Check SQLite is enabled in PHP (`php -m | grep sqlite`)

### Prayer times not loading

1. Check if cURL is enabled in PHP
2. Verify internet connection from server
3. Check if external websites are accessible

## Requirements

- PHP 8.0 or higher
- cURL extension
- SQLite3 extension
- DOMDocument extension
- MB String extension

## Differences from Python Version

1. **HTTP Library**: `aiohttp` → `cURL`
2. **HTML Parser**: `BeautifulSoup` → `DOMDocument` + `DOMXPath`
3. **Distance**: `geopy.distance` → Custom Haversine formula
4. **Async**: Python async/await → Synchronous PHP
5. **Encoding**: Automatic UTF-8 handling in PHP

## Maintenance

- Database file (`database.sqlite`) can be backed up regularly
- Logs can be found in `updates.log` (if DEBUG_MODE is enabled)
- Clear old requests from database periodically if needed

## License

This is a port of the original Python bot. Use freely for personal or commercial projects.

## Credits

Ported from Python Telegram Bot to PHP by following the exact logic and structure of the original implementation.


