<?php
/**
 * Database Class for Harkuniyaxshilik Bot
 * Optimized for SRID 0 (X=Lon, Y=Lat)
 */

class Database {
    private $pdo;
    private $driver;
    
    public function __construct() {
        $this->driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $this->connect();
    }
    
    private function connect() {
        try {
            if ($this->driver === 'mysql') {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
                $this->pdo->exec("SET time_zone = '+05:00'");
            } else {
                $this->pdo = new PDO('sqlite:' . DB_PATH);
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    /**
     * Foydalanuvchini qo'shish yoki yangilash
     * SRID 0 tartibi: POINT(LON LAT)
     */
    public function addUser($chatId, $firstName = null, $username = null, $lat = null, $lon = null) {
        if (!$this->pdo || $this->driver !== 'mysql') return false;

        $params = [
            ':chat_id' => $chatId,
            ':first_name' => $firstName,
            ':username' => $username
        ];

        // ---------------------------------------------------------
        // 1. SENARIYNI ANIQLASH
        // ---------------------------------------------------------
        
        if ($lat && $lon) {
            // --- A) USER LOKATSIYA BILAN KELDI ---
            // Insert: Berilgan lokatsiyani yozamiz
            // Update: Lokatsiyani yangilaymiz
            
            $params[':point'] = "POINT($lon $lat)"; // DIQQAT: MySQLda POINT(Lon Lat)

            $sql = "INSERT INTO tg_bot_harkunyaxshilik_users 
                    (chat_id, first_name, username, location, created_at, updated_at) 
                    VALUES (:chat_id, :first_name, :username, ST_GeomFromText(:point, 0), NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), 
                    username = VALUES(username),
                    location = ST_GeomFromText(:point, 0), -- Lokatsiya yangilansin
                    status = 'active',
                    updated_at = NOW()";
        } else {
            // --- B) USER LOKATSIYASIZ KELDI (TEXT YOZDI) ---
            // Insert: Majburiy Toshkent lokatsiyasini yozamiz (chunki NOT NULL)
            // Update: Lokatsiyaga TEGMAYMIZ (eski manzili qolsin)
            
            $sql = "INSERT INTO tg_bot_harkunyaxshilik_users 
                    (chat_id, first_name, username, location, created_at, updated_at) 
                    VALUES (:chat_id, :first_name, :username, ST_GeomFromText('POINT(69.2401 41.2995)', 0), NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), 
                    username = VALUES(username),
                    -- Location qismi yo'q, demak eski qiymat o'zgarishsiz qoladi
                    status = 'active',
                    updated_at = NOW()";
        }

        // ---------------------------------------------------------
        // 2. SO'ROVNI BAJARISH
        // ---------------------------------------------------------
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            // Xatolikni aniq ko'rish uchun log
            file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " AddUser Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * FAQAT joylashuvni yangilash (Location tugmasi bosilganda)
     */
    public function updateLocation($chatId, $lat, $lon) {
        if (!$this->pdo || $this->driver !== 'mysql') return false;

        $sql = "UPDATE tg_bot_harkunyaxshilik_users 
                SET location = ST_GeomFromText(:point, 0), 
                    updated_at = NOW() 
                WHERE chat_id = :chat_id";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':point'   => "POINT($lon $lat)", // SRID 0: X=Lon, Y=Lat
                ':chat_id' => $chatId
            ]);
        } catch (PDOException $e) {
            file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " UpdateLoc Error: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
    
    /**
     * Barcha foydalanuvchilarni olish
     * SRID 0 da: ST_X = Longitude, ST_Y = Latitude
     */
    public function getAllUsers($isCron=false) {
        if ($this->driver === 'mysql') {
            $sql = "SELECT chat_id, first_name, username, 
                    ST_X(location) as lon, ST_Y(location) as lat 
                    FROM tg_bot_harkunyaxshilik_users WHERE status = 'active'";
            $isCron=false;
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        }
        error_log("[USER] db lat={$user['lat']} lon={$user['lon']}");
        return [];
    }
    /**
     * Bitta foydalanuvchini olish
     */
    public function getUser($chatId) {
        if ($this->driver === 'mysql') {
            $sql = "SELECT first_name, username, 
                    ST_X(location) as lon, ST_Y(location) as lat 
                    FROM tg_bot_harkunyaxshilik_users 
                    WHERE chat_id = :chat_id AND status = 'active' LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':chat_id' => $chatId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
  
    public function deleteUser($chatId) {
    // 1. Ulanishni tekshirish
    if (!$this->pdo || $this->driver !== 'mysql') return false;

    // 2. SQL so'rovi: ID bo'yicha qidirib o'chirish
    $sql = "DELETE FROM tg_bot_harkunyaxshilik_users WHERE chat_id = :chat_id";

    try {
        $stmt = $this->pdo->prepare($sql);
        // 3. So'rovni bajarish
        return $stmt->execute([':chat_id' => $chatId]);
    } catch (PDOException $e) {
        // 4. Xatolikni logga yozish
        file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " DeleteUser Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}
public function deactivateUser($chatId) {
    if (!$this->pdo || $this->driver !== 'mysql') return false;

    // Statusni 'inactive' yoki 'blocked' ga o'zgartiramiz va vaqtni yangilaymiz
    $sql = "UPDATE tg_bot_harkunyaxshilik_users 
            SET status = 'blocked', updated_at = NOW() 
            WHERE chat_id = :chat_id";

    try {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':chat_id' => $chatId]);
    } catch (PDOException $e) {
        file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " DeactivateUser Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}
    /**
     * Limitni tekshirish (Vaqtinchalik o'chirib turamiz yoki soddalashtiramiz)
     */
    public function checkLimit($userId, $date) {
    // 1. Limitlar saqlanadigan fayl yo'li
    $file = __DIR__ . '/limits.json';
    
    // 2. Fayl borligini tekshiramiz va o'qiymiz
    $data = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true) ?? [];
    }

    // 3. Agar sana o'zgargan bo'lsa, eski (kechagi) ma'lumotlarni tozalab tashlaymiz
    // Bu fayl hajmi kattalashib ketishining oldini oladi
    if (!isset($data[$date])) {
        $data = []; // Hammasini o'chiramiz
        $data[$date] = []; // Yangi sana ochamiz
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    // 4. Foydalanuvchi bugun necha marta so'rov qilganini olamiz
    $userCount = $data[$date][$userId] ?? 0;

    // 5. Limitni tekshiramiz (Configdagi DAILY_LIMIT ishlatiladi)
   if ($userCount >= (defined('DAILY_LIMIT') ? DAILY_LIMIT : 12)) { 
    return false; 
}

    // 6. Hisobni bittaga oshiramiz
    $data[$date][$userId] = $userCount + 1;

    // 7. Yangilangan ma'lumotni faylga saqlaymiz
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

    return true; // Ruxsat bor
}
    
    public function getUserCount() {
        if ($this->driver === 'mysql') {
             return $this->pdo->query("SELECT COUNT(*) FROM tg_bot_harkunyaxshilik_users WHERE status = 'active'")->fetchColumn();
        }
        return 0;
    }
   /**
     * Keshdan ma'lumot olish (YANGILANDI: Manbani ham qaytaradi)
     */
    public function getCache($key, $date)
{
    if (!$this->pdo || $this->driver !== 'mysql') return null;

    try {
        $sql = "SELECT data, manba 
                FROM tg_prayer_cache 
                WHERE city = :key 
                AND date = :date";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':key'  => $key,
            ':date' => $date
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $data = json_decode($row['data'], true);

            if (!empty($row['manba'])) {
                $data['manba'] = $row['manba'];
            }

            return $data;
        }

        return null;

    } catch (PDOException $e) {
        error_log("Cache Error: " . $e->getMessage());
        return null;
    }
}


    /**
     * Keshga ma'lumot yozish (Yangilandi: manba ustuni qo'shildi)
     */
   public function saveCache($city, $date, $data) {
    if ($this->driver !== 'mysql' || !$this->pdo) return;

    try {
        $manba = $data['manba'] ?? 'Internet';

        $sql = "INSERT INTO tg_prayer_cache (city, date, data, manba)
                VALUES (:city, :date, :data, :manba)
                ON DUPLICATE KEY UPDATE
                    data = VALUES(data),
                    manba = VALUES(manba)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':city'  => $city,
            ':date'  => $date,
            ':data'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            ':manba' => $manba
        ]);
    } catch (PDOException $e) {
        file_put_contents(__DIR__.'/error.log', "Cache Save Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

    /**
     * Eski keshni tozalash (Bugungi kundan oldingi hamma narsani o'chiradi)
     */
  public function cleanOldCache() {
        if ($this->driver === 'mysql') {
            try {
                // Bugungi sana (Toshkent vaqti bilan): 2026-02-17
                // Bizga kerak sana (chegara): 2026-02-16 (Kecha)
                
                $limitDate = date('Y-m-d', strtotime('-1 day')); 

                // SQL: "Sana 2026-02-16 dan KICHIK bo'lsa o'chirilsin"
                // Bu degani: 2026-02-15, 2026-02-14... o'chib ketadi.
                // Lekin 2026-02-16 va 2026-02-17 QOLADI.
                
                $stmt = $this->pdo->prepare("DELETE FROM tg_prayer_cache WHERE date < :limit OR city NOT LIKE 'global_%'");
                $stmt->execute([':limit' => $limitDate]);
                
                return true;
            } catch (PDOException $e) {
                // Log yozish (ixtiyoriy)
                return false;
            }
        }
        return false;
    }
   
}
