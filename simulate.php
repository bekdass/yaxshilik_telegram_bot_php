<?php

require_once __DIR__ . '/Cities.php';
require_once __DIR__ . '/PrayerSourceValidator.php';
require_once __DIR__ . '/PrayerService.php';
require_once __DIR__ . '/TelegramBot.php';

// ---- FAKES (bitta faylda) ----

class FakeTelegram {
    public function sendMessage($chatId, $text, $parseMode = 'HTML', $kb = null) {
        echo "\n--- SEND to {$chatId} ---\n{$text}\n";
        return ['ok' => true];
    }
    public function deleteMessage($chatId, $messageId) {
        return ['ok' => true];
    }
}

class FakeBuilder {
    public function getMainMenuKeyboard() { return null; }
    public function getWelcomeMessage($firstName) { return "Salom, {$firstName}!"; }
    public function getGuideMessage() { return "Guide"; }

    public function getPrayerTimesMessage(array $data) {
        return "{$data['manba']} | {$data['hudud']}\n" .
            "Bomdod: {$data['bomdod']}\n" .
            "Quyosh: {$data['quyosh']}\n" .
            "Peshin: {$data['peshin']}\n" .
            "Asr: {$data['asr']}\n" .
            "Shom: {$data['shom']}\n" .
            "Hufton: {$data['hufton']}\n";
    }
}

class FakeDb {
    private array $users = [];
    private array $cache = [];

    public function addUser($chatId, $firstName, $username, $lat=null, $lon=null) {
        $this->users[$chatId] = ['chat_id'=>$chatId,'first_name'=>$firstName,'username'=>$username,'lat'=>$lat,'lon'=>$lon];
    }
    public function getUser($chatId) { return $this->users[$chatId] ?? null; }
    public function checkLimit($userId, $todayStr) { return true; }

    public function getCache($key, $date) { return $this->cache[$key][$date] ?? null; }
    public function saveCache($key, $date, $data) { $this->cache[$key][$date] = $data; }

    public function deactivateUser($chatId) {}
}

class FakeLocationService {
    public function findNearestCity($lat, $lon) {
        return ['city' => 'fargona', 'is_global' => false, 'farq' => 0];
    }
}

// ---- SIMULATE ----

$db = new FakeDb();
$telegram = new FakeTelegram();
$builder = new FakeBuilder();
$location = new FakeLocationService();

$prayerService = new PrayerService($db);
$bot = new TelegramBot('TEST', $db, $prayerService, $location, $telegram, $builder);

function makeTextUpdate($chatId, $userId, $text, $mid=1) {
    return [
        'message' => [
            'message_id' => $mid,
            'chat' => ['id' => $chatId],
            'from' => ['id' => $userId, 'first_name' => 'Ivan', 'username' => 'ivan'],
            'text' => $text,
        ]
    ];
}

function makeLocationUpdate($chatId, $userId, $lat, $lon, $mid=1) {
    return [
        'message' => [
            'message_id' => $mid,
            'chat' => ['id' => $chatId],
            'from' => ['id' => $userId, 'first_name' => 'Ivan', 'username' => 'ivan'],
            'location' => ['latitude' => $lat, 'longitude' => $lon],
        ]
    ];
}

echo "\n== TEST 1: /start ==\n";
$bot->handleUpdate(makeTextUpdate(1001, 9001, '/start', 10));

echo "\n== TEST 2: vaqtlar ==\n";
$bot->handleUpdate(makeTextUpdate(1001, 9001, 'vaqtlar', 11));

echo "\n== TEST 3: city text 'toshkent' ==\n";
$bot->handleUpdate(makeTextUpdate(1001, 9001, 'toshkent', 12));

echo "\n== TEST 4: location update (Toshkent coords) ==\n";
$bot->handleUpdate(makeLocationUpdate(1001, 9001, 41.2995, 69.2401, 13));