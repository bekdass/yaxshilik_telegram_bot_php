<?php

class FakeDb {
    private array $users = [];
    private array $cache = [];

    public function addUser($chatId, $firstName, $username, $lat=null, $lon=null) {
        $this->users[$chatId] = ['chat_id'=>$chatId,'first_name'=>$firstName,'username'=>$username,'lat'=>$lat,'lon'=>$lon];
    }
    public function getUser($chatId) { return $this->users[$chatId] ?? null; }

    public function checkLimit($userId, $todayStr) { return true; }

    public function getCache($key, $date) {
        return $this->cache[$key][$date] ?? null;
    }
    public function saveCache($key, $date, $data) {
        $this->cache[$key][$date] = $data;
    }

    public function deactivateUser($chatId) {}
}