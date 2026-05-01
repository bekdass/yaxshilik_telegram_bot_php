<?php

require_once __DIR__ . '/Cities.php';
require_once __DIR__ . '/PrayerSourceValidator.php';
class PrayerService {
    use PrayerSourceValidator;
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
        CURLOPT_TIMEOUT => 15, // Vaqtni biroz cho'zdik
        CURLOPT_SSL_VERIFYPEER => $sslVerify,
        CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER => ['Accept-Language: uz-UZ,uz;q=0.9']
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch); // Xatolikni ushlaymiz
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "❌ URL Xatosi: $url | Kod: $httpCode | Xato: $error\n";
        return null;
    }

    if (empty($response)) {
        echo "⚠️ Bo'sh javob qaytdi: $url\n";
        return null;
    }

    // Diagnostika uchun: Javobning bir qismini faylga yozib qo'yamiz
    // file_put_contents(__DIR__ . '/debug_response.html', $response);

    return $response;
}
   private function fetchMuslimUz($citySlug) {
    $region = Cities::$muslimUzcities[$citySlug] ?? $citySlug;
    if (!$region) return null;

    $html = $this->fetchUrl("https://muslim.uz/uz", false);
    if (!$html) return null;

    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    // Sana tekshiruvi
    $dateDiv = $xpath->query("//div[contains(@class, 'black-top-panel-dates')]")->item(0);
    if (!$dateDiv) return null;

    $dateText = trim($dateDiv->textContent);
    $todayDay = (int)date('j');

    if (!preg_match("/\b{$todayDay}\b/u", $dateText)) {
        return null;
    }

    $times = [];
    $mapping = [
        "Tong"   => "bomdod",
        "Quyosh" => "quyosh",
        "Peshin" => "peshin",
        "Asr"    => "asr",
        "Shom"   => "shom",
        "Xufton" => "hufton"
    ];

    $rows = $xpath->query("//div[@id='prayer']//div[contains(@class, 'flex-column')]");
    foreach ($rows as $row) {
        $divs = $xpath->query(".//div", $row);
        if ($divs->length >= 2) {
            $label = trim($divs->item(0)->textContent);
            $time  = trim($divs->item(1)->textContent);
            if (isset($mapping[$label])) {
                $times[$mapping[$label]] = $time;
            }
        }
    }

    // Hijriy sana
    $sanaHijriy = "";
    $parts = explode('|', $dateText);
    if (count($parts) >= 2) $sanaHijriy = $this->cleanTextLatin(trim($parts[1]));

    // Kerakli yagona validatsiya
  [$ok, $reason] = $this->validatePrayerSourceOrReason(
    $html,
    $times,
    ['bomdod','peshin','asr','shom'],
    true,
    ['Tong','Peshin','Asr','Shom'] // HTML label presence
);
if (!$ok) return null;

    return [
        'manba'       => 'Muslim.uz',
        'sana_hijriy' => $sanaHijriy,
        'hudud'       => $this->getCityDisplayName($citySlug),
        'bomdod'      => $times['bomdod'],
        'quyosh'      => $times['quyosh'] ?? '',
        'peshin'      => $times['peshin'],
        'asr'         => $times['asr'],
        'shom'        => $times['shom'],
        'hufton'      => $times['hufton'] ?? ''
    ];
}

private function fetchNamozvaqtiUz($citySlug)
{
    $citySlug = Cities::$slugAliases[$citySlug] ?? $citySlug;
$slug = Cities::$citysSlugs[$citySlug] ?? $citySlug;
    
    if (!$slug) return null;

    $html = $this->fetchUrl("https://namozvaqti.uz/shahar/{$slug}", false);
    if (!$html) return null;
if (stripos($html, 'cf-browser-verification') !== false
 || stripos($html, 'Cloudflare') !== false
 || stripos($html, 'Please wait') !== false) {
    return null;
}
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Sana blokini olish
    $vilNode = $xpath->query("//h5[contains(concat(' ', normalize-space(@class), ' '), ' vil ')]")->item(0);
    if (!$vilNode) return null;

    $milodiyStrong = $xpath->query(".//strong[1]", $vilNode)->item(0);
    if (!$milodiyStrong) return null;

    $milodiyText = trim(preg_replace('/\s+/u', ' ', $milodiyStrong->textContent));
    $todayDay = (int)date('j');

    if (!preg_match('/(^|\D)' . $todayDay . '(\D|$)/u', $milodiyText)) {
        return null;
    }

    // Vaqtlarni olish
    $ids = ['bomdod', 'quyosh', 'peshin', 'asr', 'shom', 'hufton'];
    $times = [];

    foreach ($ids as $id) {
        $node = $xpath->query("//*[@id='{$id}']")->item(0);
        if (!$node) return null;

        $time = trim($node->textContent);
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) return null;

        $times[$id] = $time;
    }

    // Hijriy sana
    $sanaHijriy = '';
    $hijriStrong = $xpath->query(".//strong[2]", $vilNode)->item(0);
    if ($hijriStrong) {
        $sanaHijriy = $this->cleanTextLatin(trim(preg_replace('/\s+/u', ' ', $hijriStrong->textContent)));
    }

    // Yetishmayotgan (majburiy) validatsiya: bomdod/peshin/asr/shom shart
    if (!$this->validateRequiredTimes($times, ['bomdod','peshin','asr','shom'])) {
        return null;
    }

    return [
        'manba'       => 'Namozvaqti.uz',
        'sana_hijriy' => $sanaHijriy,
        'hudud'       => $this->getCityDisplayName($citySlug),
        'bomdod'      => $times['bomdod'],
        'quyosh'      => $times['quyosh'],
        'peshin'      => $times['peshin'],
        'asr'         => $times['asr'],
        'shom'        => $times['shom'],
        'hufton'      => $times['hufton'],
    ];
}

private function fetchNamozVaqtiJson(string $citySlug): ?array
{
    $region = Cities::$namozVaqtiRegions[$citySlug] ?? $citySlug;
    if (!$region) return null;

    $url = "https://namoz-vaqti.uz/?format=json&lang=lotin&period=today&region=" . rawurlencode($region);
    $json = $this->fetchUrl($url, true);
    if (!$json) return null;

    $res = json_decode($json, true);
    if (!is_array($res)) return null;

    $times = $res['today']['times'] ?? null;
    if (!is_array($times)) return null;

    // API map
    $mapped = [
        'bomdod' => trim((string)($times['bomdod'] ?? '')),
        'quyosh' => trim((string)($times['quyosh'] ?? '')),
        'peshin' => trim((string)($times['peshin'] ?? '')),
        'asr'    => trim((string)($times['asr'] ?? '')),
        'shom'   => trim((string)($times['shom'] ?? '')),
        'hufton' => trim((string)($times['xufton'] ?? '')),
    ];

    // 🔽 SHU YERGA QO'YILADI
    [$ok, $reason] = $this->validatePrayerSourceOrReason(
        null,                              // HTML yo‘q (JSON API)
        $mapped,
        ['bomdod','peshin','asr','shom'],  // majburiy vaqtlar
        true,                               // HH:MM format tekshir
        null                                // HTML word check yo‘q
    );

    if (!$ok) {
        error_log('[NamozVaqtiJson INVALID] ' . $reason);
        return null;
    }
    // 🔼

    $hudud = $res['meta']['region']['name'] ?? $citySlug;
    $offsetMin = (int)($res['meta']['offset_min'] ?? 0);
$hijri = $this->fetchHijriFromAladhan($res['meta']['date'] ?? null, 'Asia/Tashkent');
if ($hijri) {
    $hijri = $this->cleanTextLatin($hijri); // xohlasangiz lotinlashtirish
}
    return [
        'manba'       => 'Namoz-vaqti.uz',
        'hudud'       => $hudud,
        'sana_hijriy' => $hijri ?: '',
        'bomdod'      => $mapped['bomdod'],
        'quyosh'      => $mapped['quyosh'],
        'peshin'      => $mapped['peshin'],
        'asr'         => $mapped['asr'],
        'shom'        => $mapped['shom'],
        'hufton'      => $mapped['hufton'],
        'farq'        => $offsetMin,
    ];
}
public function fetchGlobalAladhan($lat = null, $lon = null) {
    if ($lat === null || $lon === null) return null;
    if ($lat == 0.0 || $lon == 0.0) return null;
    // 1. Timestamp (Bugungi sana)
    $timestamp = time();

    // 2. URL yasash
    // school=1 -> Hanafiy mazhabi (Asr vaqti kechroq kiradi)
    // method=13 -> Diyanet (Turkiya). Bu O'zbekiston mintaqasi uchun eng yaqin va aniq hisoblash usuli (18° burchak).
    // Agar method qo'ymasa, Fajr va Hufton vaqtlari farq qilishi mumkin.
    
    $url = "https://api.aladhan.com/v1/timings/$timestamp?latitude={$lat}&longitude={$lon}&school=1&method=13";

    // 3. So'rov yuborish
    $json = $this->fetchUrl($url, false);
    
    if (!$json) return null;
    $res = json_decode($json, true);
    
    if (!isset($res['data']['timings'])) return null;
    
    $data = $res['data'];
    $t = $data['timings'];
    $meta = $data['meta'];
    $hijri = $data['date']['hijri'];

    // 4. Timezone va Sana (Oldingi javobdagi mantiq)
    $timezoneString = $meta['timezone'];
    try {
        $localDate = new DateTime('now', new DateTimeZone($timezoneString));
        $formattedDate = $localDate->format('Y-m-d');
    } catch (Exception $e) {
        $formattedDate = date('Y-m-d');
    }

    // 5. Hudud nomini tozalash
    $hududNomi = $timezoneString;
    if (strpos($timezoneString, '/') !== false) {
        $parts = explode('/', $timezoneString);
        $hududNomi = str_replace('_', ' ', end($parts));
    }

    // 6. Javob qaytarish
    return [
        'manba' => 'Aladhan (Hanafiy)', 
        'hudud' => $hududNomi,
        'sana'  => $formattedDate,
        'bomdod' => $this->formatTime($t['Fajr']),
        'quyosh' => $this->formatTime($t['Sunrise']),
        'peshin' => $this->formatTime($t['Dhuhr']),
        'asr'    => $this->formatTime($t['Asr']), // Endi Hanafiy vaqti bo'ladi
        'shom'   => $this->formatTime($t['Maghrib']),
        'hufton' => $this->formatTime($t['Isha']),
        'sana_hijriy' => "{$hijri['day']} {$hijri['month']['en']} {$hijri['year']} H.",
        'farq' => 0
    ];
}

// Yordamchi formatlash funksiyasi
private function formatTime($time) {
    return date('H:i', strtotime($time));
}


    // --- ASOSIY ---

   // PrayerService.php ichida
public function getPrayerTimes($citySlug = 'toshkent', $lat = null, $lon = null, $is_global = null)
{
    $todayStr = date('Y-m-d');

    // cache key
    $cacheKey = $citySlug ?: 'unknown';
    if ($is_global && $lat !== null && $lon !== null) {
        $cacheKey = 'geo:' . $lat . ':' . $lon;
    }

    // 1) cache
    if ($cacheKey) {
        $cached = $this->db->getCache($cacheKey, $todayStr);
        if ($cached) return $cached;
    }

    $data = null;

    // 2) GLOBAL mode: faqat Aladhan
    if ($is_global) {
        [$rLat, $rLon] = $this->resolveCoordinatesFromCity((string)$citySlug, $lat, $lon);
        if ($rLat !== null && $rLon !== null) {
            $data = $this->fetchGlobalAladhan($rLat, $rLon);
            if ($data) $cacheKey = 'geo:' . $rLat . ':' . $rLon;
        }
    } else {
        // 3) LOCAL chain (avval JSON API)
        $data = $this->fetchNamozVaqtiJson((string)$citySlug);

        if (!$data && $citySlug === 'toshkent' && ($lat === null || $lon === null)) {
            $data = $this->fetchMuslimUz((string)$citySlug);
        }
        if (!$data) {
            $data = $this->fetchNamozvaqtiUz((string)$citySlug);
        }

        // 4) local topilmasa global fallback (Cities coords bilan)
        if (!$data) {
            [$rLat, $rLon] = $this->resolveCoordinatesFromCity((string)$citySlug, $lat, $lon);
            if ($rLat !== null && $rLon !== null) {
                $data = $this->fetchGlobalAladhan($rLat, $rLon);
                if ($data) $cacheKey = 'geo:' . $rLat . ':' . $rLon;
            }
        }
    }

    // 5) format + cache save
    if ($data) {
        if (!empty($data['sana'])) {
            $todayStr = $data['sana'];
        }

        $data['sana'] = $this->formatDateUz($todayStr);

        if (!empty($data['sana_hijriy'])) {
            $data['sana_hijriy'] = $this->cleanTextLatin($data['sana_hijriy']);
        }

        try {
            $this->db->saveCache($cacheKey, $todayStr, $data);
        } catch (Throwable $e) {
            error_log("CACHE SAVE FAILED: " . $e->getMessage());
        }

        return $data;
    }

    error_log("All sources failed for: {$citySlug}");
    return null;
}

/**
 * Cities::$cities format: ['toshkent' => [41.2995, 69.2401], ...]
 */
private function resolveCoordinatesFromCity(string $citySlug, $lat, $lon): array
{
    if ($lat !== null && $lon !== null && $lat != 0.0 && $lon != 0.0) {
        return [(float)$lat, (float)$lon];
    }

    $coords = Cities::$cities[$citySlug] ?? null;
    if (is_array($coords) && count($coords) >= 2) {
        return [(float)$coords[0], (float)$coords[1]];
    }

    return [null, null];
}

private function trySources(string $citySlug, ?float $lat, ?float $lon, bool $isGlobal): ?array
{
    $sources = [
        [
            'name' => 'Namoz-vaqti.uz',
            'enabled' => !$isGlobal,
            'fn' => fn() => $this->fetchNamozVaqtiJson($citySlug),
        ],
        [
            'name' => 'Muslim.uz',
            // sizning shart: Toshkent bo'lsa va koordinata kelmagan bo'lsa
            'enabled' => ($citySlug === 'toshkent' && !$isGlobal && ($lat === null || $lon === null)),
            'fn' => fn() => $this->fetchMuslimUz($citySlug),
        ],
        
        [
            'name' => 'Namozvaqti.uz',
            'enabled' => !$isGlobal,
            'fn' => fn() => $this->fetchNamozvaqtiUz($citySlug),
        ],
         
    ];

    foreach ($sources as $s) {
        if (!$s['enabled']) continue;

        try {
            $data = ($s['fn'])();

            // null bo'lsa -> continue; (keyingi manbaga o'tadi)
            if (is_array($data) && !empty($data['bomdod'])) {
                return $data;
            }
        } catch (\Throwable $e) {
            // exception bo'lsa ham chain buzilmaydi
            // $this->log("{$s['name']} failed: ".$e->getMessage());
            continue;
        }
    }

    return null;
}

private function fetchHijriFromAladhan(?string $ymd = null, string $tz = 'Asia/Tashkent'): ?string
{
    // Sana: timezone bo‘yicha "bugun"
    if ($ymd === null) {
        try {
            $dt = new DateTime('now', new DateTimeZone($tz));
            $ymd = $dt->format('Y-m-d');
        } catch (Throwable $e) {
            $ymd = date('Y-m-d');
        }
    }

    // Aladhan gToH DD-MM-YYYY format xohlaydi
    $d = DateTime::createFromFormat('Y-m-d', $ymd);
    if (!$d) return null;
    $dateParam = $d->format('d-m-Y');

    $url = "https://api.aladhan.com/v1/gToH?date=" . rawurlencode($dateParam);
    $json = $this->fetchUrl($url, false);
    if (!$json) return null;

    $res = json_decode($json, true);
    if (!is_array($res) || ($res['code'] ?? 0) !== 200) return null;

    $h = $res['data']['hijri'] ?? null;
    if (!is_array($h)) return null;

    // Masalan: "20 Sha'ban 1447 H." kabi
    $day = $h['day'] ?? null;
    $monthEn = $h['month']['en'] ?? null;
    $year = $h['year'] ?? null;

    if (!$day || !$monthEn || !$year) return null;

    return "{$day} {$monthEn} {$year} H.";
}

}