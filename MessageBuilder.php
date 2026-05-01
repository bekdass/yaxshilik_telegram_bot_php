<?php

class MessageBuilder {
    
    public function getWelcomeMessage($name) {
        $nameStr = $name ? " $name" : "";
        return "Assalomu alaykum{$nameStr}! 🌙\n\n" .
               "Men namoz vaqtlari botiman.\n" .
               "Quyidagi tugmalardan foydalaning:";
    }

    // --- YANGI: Qo'llanma matni ---
    public function getGuideMessage() {
        return "<b>📘 Botdan foydalanish qo'llanmasi:</b>\n\n" .
               "1️⃣ <b>📍 Lokatsiya yangilash:</b>\n" .
               "Tugmani bosib, lokatsiya yuborsangiz, bot siz turgan joyni aniqlaydi va eng to'g'riroq vaqtni ko'rsatadi.\n\n" .
               "2️⃣ <b>✍️ Shahar nomi:</b>\n" .
               "Shunchaki shahar nomini yozing (masalan: <i>Namangan</i>, <i>Buxoro</i>, <i>Nuks</i>) va bot o'sha shahar vaqtini ko'rsatadi. <i>Lekin sizning lokatsiyangiz yangilanmaydi</i>\n\n" .
               "3️⃣ <b>🤲 Namoz vaqtlari:</b>\n" .
               "Bu tugmani bossangiz, bot oxirgi saqlangan hududingiz bo'yicha bugungi vaqtlarni chiqarib beradi.\n\n" .
               "ℹ️ <i>Eslatma: Bot har kuni soat 01:00 da avtomatik ravishda yangi (faqat O'zbekiston) vaqtlarni yuboradi.</i>";
    }

   public function getPrayerTimesMessage($data) {
    // Farqni olish (agar ma'lumot bo'lmasa bo'sh qoladi)
   $farq = (!empty($data['farq'])) ? " ({$data['farq']})" : "";

            return 
                "Assalomu alaykum! 🌤\n\n" .
                "📍 <b>Hudud:</b> {$data['hudud']} {$farq}\n" .
                "📅 <b>Bugun:</b> {$data['sana']}\n" .
                "🌙 <b>Hijriy:</b> " . ($data['sana_hijriy'] ?? '') . "\n\n" .
                "➖➖➖➖➖➖➖➖\n" .
                "🏙 <b>Saharlik:</b>  {$data['bomdod']}\n" .
                "🌆 <b>Iftorlik:</b>     {$data['shom']}\n" .
                "➖➖➖➖➖➖➖➖\n" .
                "🏙 <b>Bomdod:</b>   {$data['bomdod']}\n" .
                "☀️ <b>Quyosh:</b>     {$data['quyosh']}\n" .
                "🏞 <b>Peshin:</b>      {$data['peshin']}\n" .
                "🌇 <b>Asr:</b>            {$data['asr']}\n" .
                "🌆 <b>Shom:</b>        {$data['shom']}\n" .
                "🌌 <b>Xufton:</b>      {$data['hufton']}\n" .
                "➖➖➖➖➖➖➖➖\n\n" .
                "<i>Manba: " . ($data['manba'] ?? 'Internet') . "</i>";
        }

    public function getMainMenuKeyboard() {
        $keyboard = [
            [
                ['text' => '🤲 Namoz vaqtlari']
            ],
            [
                ['text' => '📍 Lokatsiya yangilash', 'request_location' => true]
            ],
            // --- YANGI: Qo'llanma tugmasi ---
            [
                ['text' => '📘 Qo\'llanma'] 
            ]
        ];

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false // Doim ko'rinib turadi
        ];
    }
}