<?php

require_once __DIR__ . '/Cities.php';

class PrayerService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // --- FORMATLASH ---

    private function formatDateUz($dateStr) {
        $weeks = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
        $months = ['', 'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
        $timestamp = strtotime($dateStr);
        return date('Y', $timestamp) . " yil " . date('d', $timestamp) . " " . $months[(int)date('m', $timestamp)] . ", " . $weeks[date('w', $timestamp)];
    }

    private $cyrillicToLatin = [
        "январь" => "Yanvar", "февраль" => "Fevral", "март" => "Mart",
        "апрель" => "Aprel", "май" => "May", "июнь" => "Iyun",
        "июль" => "Iyul", "август" => "Avgust", "сентябрь" => "Sentabr",
        "октябрь" => "Oktabr", "ноябрь" => "Noyabr", "декабрь" => "Dekabr",
        "душанба" => "Dushanba", "сешанба" => "Seshanba", "чоршанба" => "Chorshanba",
        "пайшанба" => "Payshanba", "жума" => "Juma", "шанба" => "Shanba", "якшанба" => "Yakshanba",
        "муҳаррам" => "Muharram", "сафар" => "Safar", "рабиъул аввал" => "Rabi'ul avval",
        "рабиъул охир" => "Rabi'ul oxir", "жумодул аввал" => "Jumodul avval", "жумодул охир" => "Jumodul oxir",
        "ражаб" => "Rajab", "шаъбон" => "Sha'bon", "рамазон" => "Ramazon",
        "шаввол" => "Shavvol", "зулқаъда" => "Zulqa'da", "зулҳижжа" => "Zulhijja",
        "йил" => "yil", "бугун" => "Bugun"
    ];
    
    private function cleanTextLatin($text) {
        if (empty($text)) return "";
        $textLower = mb_strtolower($text, 'UTF-8');
        foreach ($this->cyrillicToLatin as $cyr => $lat) {
            if (mb_stripos($textLower, $cyr) !== false) {
                $text = preg_replace('/' . preg_quote($cyr, '/') . '/iu', $lat, $text);
            }
        }
        return trim($text);
    }
    
    private function fetchUrl($url, $sslVerify = false) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Bot/1.0)',
            CURLOPT_HTTPHEADER => ['Accept-Language: uz-UZ,uz;q=0.9']
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode === 200 && $response) ? $response : null;
    }

    // --- MANBALAR ---

    private function getCityDisplayName($slug) {
        // Agar Cities.php da chiroyli nomi bo'lsa o'shani olamiz, bo'lmasa slugni o'zini
        return Cities::$citysNames[$slug] ?? ucfirst($slug);
    }

    private function fetchMuslimUz($citySlug) {
        if ($citySlug !== 'toshkent') return null;

        $html = $this->fetchUrl("https://muslim.uz/uz", false);
        if (!$html) return null;
        
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        $times = [];
        $mapping = ["Tong" => "bomdod", "Quyosh" => "quyosh", "Peshin" => "peshin", "Asr" => "asr", "Shom" => "shom", "Xufton" => "hufton"];
        
        $rows = $xpath->query("//div[@id='prayer']//div[contains(@class, 'flex-column')]");
        foreach ($rows as $row) {
            $divs = $xpath->query(".//div", $row);
            if ($divs->length >= 2) {
                $label = trim($divs->item(0)->textContent);
                $time = trim($divs->item(1)->textContent);
                if (isset($mapping[$label])) $times[$mapping[$label]] = $time;
            }
        }
        
        $sanaHijriy = "";
        $dateDiv = $xpath->query("//div[contains(@class, 'black-top-panel-dates')]")->item(0);
        if ($dateDiv) {
            $parts = explode('|', trim($dateDiv->textContent));
            if (count($parts) >= 2) $sanaHijriy = $this->cleanTextLatin(trim($parts[1]));
        }
        
        if (isset($times['bomdod'])) {
            return [
                'manba' => 'Muslim.uz',
                'sana_hijriy' => $sanaHijriy,
                'hudud' => $this->getCityDisplayName($citySlug), // <-- O'ZGARDI
                'bomdod' => $times['bomdod'],
                'quyosh' => $times['quyosh'] ?? '',
                'peshin' => $times['peshin'] ?? '',
                'asr' => $times['asr'] ?? '',
                'shom' => $times['shom'] ?? '',
                'hufton' => $times['hufton'] ?? ''
            ];
        }
        return null;
    }
    
    private function fetchIslomUz($citySlug) {
        $regionId = Cities::$islomUzIds[$citySlug] ?? null;
        if (!$regionId) return null;

        $currentMonth = date('n');
        $html = $this->fetchUrl("https://islom.uz/vaqtlar/{$regionId}/{$currentMonth}", true);
        if (!$html) return null;
        
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        // Validatsiya
        $titleNode = $xpath->query("//h2[@class='region_name']")->item(0) ?? $xpath->query("//title")->item(0);
        if ($titleNode) {
            $pageTitle = mb_strtolower(trim($titleNode->textContent), 'UTF-8');
            $cityName = mb_strtolower(Cities::$citysNames[$citySlug] ?? '', 'UTF-8');
            if (strpos($pageTitle, $citySlug) === false && strpos($pageTitle, $cityName) === false) {
                if (strpos($pageTitle, 'toshkent') !== false && $citySlug !== 'toshkent') return null;
            }
        }
        
        $todayRow = $xpath->query("//tr[contains(@class, 'bugun')]")->item(0);
        if (!$todayRow) {
            $todayDay = date('j');
            $rows = $xpath->query("//table[contains(@class, 'prayer_table')]//tbody//tr");
            foreach ($rows as $row) {
                $cols = $xpath->query(".//td", $row);
                if ($cols->length > 1 && trim($cols->item(1)->textContent) == $todayDay) {
                    $todayRow = $row;
                    break;
                }
            }
        }
        
        if (!$todayRow) return null;
        $cols = $xpath->query(".//td", $todayRow);
        if ($cols->length < 9) return null;
        
        $times = [
            'bomdod' => trim($cols->item(3)->textContent),
            'quyosh' => trim($cols->item(4)->textContent),
            'peshin' => trim($cols->item(5)->textContent),
            'asr' => trim($cols->item(6)->textContent),
            'shom' => trim($cols->item(7)->textContent),
            'hufton' => trim($cols->item(8)->textContent)
        ];
        
        $sanaHijriyLat = "";
        $dateDiv = $xpath->query("//div[contains(@class, 'date_time')]")->item(0);
        if ($dateDiv) {
            $parts = explode('|', trim($dateDiv->textContent));
            if (count($parts) >= 1) $sanaHijriyLat = $this->cleanTextLatin(trim($parts[0]));
        }
        
        return [
            'manba' => 'Islom.uz',
            'sana_hijriy' => $sanaHijriyLat,
            'hudud' => $this->getCityDisplayName($citySlug), // <-- O'ZGARDI
            'bomdod' => $times['bomdod'],
            'quyosh' => $times['quyosh'],
            'peshin' => $times['peshin'],
            'asr' => $times['asr'],
            'shom' => $times['shom'],
            'hufton' => $times['hufton']
        ];
    }
    
    private function fetchNamozvaqtiUz($citySlug) {
        $slug = Cities::$citysSlugs[$citySlug] ?? null;
        if (!$slug) return null;

        $html = $this->fetchUrl("https://namozvaqti.uz/shahar/{$slug}", false);
        if (!$html) return null;
        
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        $h1 = $xpath->query("//h1")->item(0);
        if ($h1) {
            $h1Text = mb_strtolower(trim($h1->textContent), 'UTF-8');
            if (strpos($h1Text, $citySlug) === false && strpos($h1Text, 'topilmadi') !== false) return null;
        }

        $times = [];
        $ids = ['bomdod', 'quyosh', 'peshin', 'asr', 'shom', 'hufton'];
        foreach ($ids as $id) {
            $element = $xpath->query("//*[@id='{$id}']")->item(0);
            if (!$element) return null;
            $times[$id] = trim($element->textContent);
        }
        
        $sanaHijriy = "";
        $dateElements = $xpath->query("//div[contains(@class, 'vil')]//strong");
        if ($dateElements->length >= 2) {
             $sanaHijriy = $this->cleanTextLatin(trim($dateElements->item(1)->textContent));
        }
        
        return [
            'manba' => 'Namozvaqti.uz',
            'sana_hijriy' => $sanaHijriy,
            'hudud' => $this->getCityDisplayName($citySlug), // <-- O'ZGARDI
            'bomdod' => $times['bomdod'],
            'quyosh' => $times['quyosh'],
            'peshin' => $times['peshin'],
            'asr' => $times['asr'],
            'shom' => $times['shom'],
            'hufton' => $times['hufton']
        ];
    }
    
    // --- ASOSIY ---
    
    public function getPrayerTimes($citySlug = 'toshkent') {
        $todayStr = date('Y-m-d');
        
        // 1. Keshni tekshirish
        $cachedData = $this->db->getCache($citySlug, $todayStr);
        if ($cachedData) {
            return $cachedData;
        }
        
        // 2. Scraping
        $data = null;
        
        if ($citySlug === 'toshkent') {
            $data = $this->fetchMuslimUz($citySlug);
        }
        if (!$data) {
            $data = $this->fetchIslomUz($citySlug);
        }
        if (!$data) {
            $data = $this->fetchNamozvaqtiUz($citySlug);
        }
        
        // 3. Formatlash va Saqlash
        if ($data) {
            $data['sana'] = $this->formatDateUz($todayStr);
            if (!empty($data['sana_hijriy'])) {
                $data['sana_hijriy'] = $this->cleanTextLatin($data['sana_hijriy']);
            }
            
            $this->db->saveCache($citySlug, $todayStr, $data);
            return $data;
        }
        
        error_log("All sources failed for: {$citySlug}");
        return null;
    }
}