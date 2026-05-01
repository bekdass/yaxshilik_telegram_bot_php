<?php
/**
 * Bot Sozlamalari
 */

// 1. Admin ID (Sizning Telegram ID raqamingiz)
define('ADMIN_ID', 123456789);

// 2. Baza manzili
//define('DB_PATH', __DIR__ . '/database.sqlite');

// 3. Telegram Bot Token (replace with your actual token)
define('TELEGRAM_BOT_TOKEN', '1234567890:AAH_SIZNING_TOKENINGIZ_BU_YERDA');


// 4. Debug mode (set to false in production)
define('DEBUG_MODE', true);

// 5. Kunlik limit
define('DAILY_LIMIT', 12);

// So'rov
define('REQUEST_TIMEOUT', 10);

// MYSQL SOZLAMALARI (SERVER UCHUN)
define('DB_DRIVER', 'mysql');       // <-- Muhim!
define('DB_HOST', 'localhost');
define('DB_NAME', 'db');     // Asosiy baza nomi
define('DB_USER', 'user');          // Baza useri
define('DB_PASS', '123445667');    // Baza paroli

// HTTP  so'rovlar uchun User Agent 
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
