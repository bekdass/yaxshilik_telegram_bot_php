<?php

class FakeBuilder {
    public function getMainMenuKeyboard() { return null; }
    public function getWelcomeMessage($firstName) { return "Salom, {$firstName}!"; }
    public function getGuideMessage() { return "Guide"; }

    public function getPrayerTimesMessage(array $data) {
        return "{$data['manba']} | {$data['hudud']}\n" .
               "Bomdod: {$data['bomdod']}\nPeshin: {$data['peshin']}\nAsr: {$data['asr']}\nShom: {$data['shom']}\n";
    }
}