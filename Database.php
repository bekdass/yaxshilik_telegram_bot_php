<?php
/**
 * Database Class for Harkuniyaxshilik Bot
 * Integrates with Laravel's tg_harkun_users table and handles Spatial Data
 */

class Database {
    private $pdo;
    private $driver;
    
    public function __construct() {
        // Configdan driverni olamiz (serverda 'mysql', lokalda 'sqlite')
        $this->driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $this->connect();
        
        // SQLite bo'lsa jadvallarni o'zi yaratsin, MySQL da Laravel yaratadi
        if ($this->driver !== 'mysql') {
            $this->createTables();
        }
    }
    
    private function connect() {
        try {
            if ($this->driver === 'mysql') {
                // SERVER (GCP / MariaDB) - Laravel Bazasi
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            } else {
                // LOKAL (GitHub / SQLite)
                $this->pdo = new PDO('sqlite:' . DB_PATH);
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Xatolikni faylga yozamiz
            file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Faqat SQLite uchun (Lokal test)
    private function createTables() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER UNIQUE NOT NULL,
            first_name TEXT,
            username TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {}
    }

    /**
     * Foydalanuvchini saqlash (Laravel jadvali + POINT)
     */
   public function addUser($chatId, $firstName = null, $lastName = null, $username = null, $lat = null, $lon = null) {
        if (!$this->pdo) return false;

        if ($this->driver === 'mysql') {
            $params = [
                ':chat_id' => $chatId,
                ':first_name' => $firstName,
                ':username' => $username
            ];

            // 1. Lokatsiya kelganligini tekshiramiz
            if ($lat && $lon) {
                // Yangi lokatsiya kelsa -> O'sha koordinatani yozamiz
                $pointSql = "ST_GeomFromText(:point, 4326)";
                $params[':point'] = "POINT($lon $lat)"; 
            } else {
                // Lokatsiya kelmasa -> Default Toshkent (Faqat yangi user uchun)
                $pointSql = "ST_GeomFromText('POINT(69.2401 41.2995)', 4326)";
            }
            
            // 2. SQL so'rovi (INSERT ... ON DUPLICATE KEY UPDATE)
            $sql = "INSERT INTO tg_bot_harkunyaxshilik_users 
                    (chat_id, first_name, username, location, created_at, updated_at) 
                    VALUES (:chat_id, :first_name, :username, $pointSql, NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), 
                    username = VALUES(username),
                    updated_at = NOW()";
            
            // 3. MUHIM TUZATISH:
            // Agar yangi lokatsiya ($lat, $lon) kelgan bo'lsa, bazadagi location ustunini ham majburan yangilaymiz!
            if ($lat && $lon) {
                $sql .= ", location = VALUES(location)";
            }

            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return true;
            } catch (PDOException $e) {
                // Xatoni logga yozish
                file_put_contents(__DIR__.'/error.log', date('Y-m-d H:i:s') . " AddUser Error: " . $e->getMessage() . "\n", FILE_APPEND);
                return false;
            }
        }
        return false;
    }

    /**
     * Limitni tekshirish (Vaqtinchalik o'chirib turamiz yoki soddalashtiramiz)
     */
    public function checkLimit($userId, $date) {
        // Hozircha serverni qiynamaslik uchun har doim ruxsat beramiz
        return true; 
    }
    
    public function getUserCount() {
        if ($this->driver === 'mysql') {
             return $this->pdo->query("SELECT COUNT(*) FROM tg_bot_harkunyaxshilik_users")->fetchColumn();
        }
        return 0;
    }
   /**
     * Keshdan ma'lumot olish (YANGILANDI: Manbani ham qaytaradi)
     */
    public function getCache($city, $date) {
        if ($this->driver === 'mysql') {
            try {
                // data va manba ustunlarini olamiz
                $stmt = $this->pdo->prepare("SELECT data, manba FROM tg_prayer_cache WHERE city = :city AND date = :date");
                $stmt->execute([':city' => $city, ':date' => $date]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $data = json_decode($row['data'], true);
                    // Agar bazada manba bo'lsa, uni arrayga qo'shib qo'yamiz
                    if (!empty($row['manba'])) {
                        $data['manba'] = $row['manba'];
                    }
                    return $data;
                }
                return null;
            } catch (PDOException $e) {
                return null;
            }
        }
        return null;
    }   

    /**
     * Keshga ma'lumot yozish (Yangilandi: manba ustuni qo'shildi)
     */
    public function saveCache($city, $date, $data) {
        if ($this->driver === 'mysql') {
            try {
                // $data massivining ichidan 'manba' ni olamiz, yo'q bo'lsa default
                $manba = $data['manba'] ?? 'Internet';
                
                $sql = "INSERT INTO tg_prayer_cache (city, date, data, manba) 
                        VALUES (:city, :date, :data, :manba) 
                        ON DUPLICATE KEY UPDATE 
                        data = :data, 
                        manba = :manba"; // Update bo'lganda ham yangilansin
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':city' => $city, 
                    ':date' => $date, 
                    ':data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ':manba' => $manba
                ]);
            } catch (PDOException $e) {
                file_put_contents(__DIR__.'/error.log', "Cache Save Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
    }
    /**
     * Eski keshni tozalash (Bugungi kundan oldingi hamma narsani o'chiradi)
     */
    public function cleanOldCache() {
        if ($this->driver === 'mysql') {
            try {
                $today = date('Y-m-d');
                // "date" ustuni bugundan kichik (<) bo'lsa o'chirilsin
                $stmt = $this->pdo->prepare("DELETE FROM tg_prayer_cache WHERE date < :today");
                $stmt->execute([':today' => $today]);
                return true;
            } catch (PDOException $e) {
                file_put_contents(__DIR__.'/error.log', "Clean Cache Error: " . $e->getMessage() . "\n", FILE_APPEND);
                return false;
            }
        }
        return false;
    }
    public function getAllUsers() {
        if ($this->driver === 'mysql') {
            // ST_X va ST_Y bu POINT dan koordinatani ajratib olish uchun
            $sql = "SELECT chat_id, first_name, username, 
                    ST_X(location) as lon, ST_Y(location) as lat 
                    FROM tg_bot_harkunyaxshilik_users";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
    public function getUser($chatId) {
        if ($this->driver === 'mysql') {
            // Koordinatalarni ajratib olamiz
            $sql = "SELECT first_name, username, 
                    ST_X(location) as lon, ST_Y(location) as lat 
                    FROM tg_bot_harkunyaxshilik_users 
                    WHERE chat_id = :chat_id LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':chat_id' => $chatId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}
