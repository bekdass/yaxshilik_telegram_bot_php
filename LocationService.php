<?php

require_once __DIR__ . '/Cities.php';

class LocationService {

    /**
     * Eng yaqin shaharni topish, masofani o'lchash va daqiqa farqini hisoblash
     */
    public function findNearestCity($lat, $lon) {
        $nearestCitySlug = 'toshkent';
        $minDistKm = PHP_FLOAT_MAX; 
        $nearestCityLon = 69.2401;

        foreach (Cities::$cities as $slug => $coords) {
            list($cityLat, $cityLon) = $coords;

            // Masofani aniq kilometrda o'lchaymiz (Haversine)
            $dist = $this->haversineDistance($lat, $lon, $cityLat, $cityLon);

            if ($dist < $minDistKm) {
                $minDistKm = $dist;
                $nearestCitySlug = $slug;
                $nearestCityLon = $cityLon;
            }
        }

        // 1. Quyosh harakati farqi: (UserLon - CityLon) * 4
        // Agar User sharqroqda (Lon kattaroq) bo'lsa, vaqt musbat (+) chiqadi
        $diffMinutes = ($nearestCityLon - $lon ) * 4;
        $roundedDiff = (int)round($diffMinutes);
        $farq = 0; 
        // Belgini aniqlash
        if ($roundedDiff > 0) {
            $farq = "+$roundedDiff";
        } elseif ($roundedDiff < 0) {
            $farq = "$roundedDiff";
        } 

        /**
         * 2. RADIUS MANTIG'I (300 km):
         * Agar foydalanuvchi eng yaqin shahardan 300 km dan uzoq bo'lsa, 
         * bot "Global" rejimga (Aladhan API) o'tadi.
         */
        $isGlobal=false;
        if($minDistKm > 300){
            // 1. User yuborgan aniq nuqtani yaxlitlaymiz (Grid tizimi)
            $latRound = round((float)$lat, 1);
            $lonRound = round((float)$lon, 1);
            
            // 2. Kesh uchun unikal "ID" (Key) yasaymiz
            $nearestCitySlug = "global_{$latRound}_{$lonRound}";
        
            $isGlobal=true;
            $farq=0;
        }

        return [
            'city'      => $nearestCitySlug,
            'farq'      => $farq,
            'is_global' => $isGlobal,
            'distance'  => (int)$minDistKm
        ];
    }

    /**
     * Masofani kilometrda hisoblash uchun Haversine formulasi
     */
    public function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }


    
    public function getLocation(string $cityName): ?array
{
    $url = "https://nominatim.openstreetmap.org/search"
         . "?q=" . urlencode($cityName)
         . "&format=json"
         . "&limit=1";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "User-Agent: NamozBot/1.0 (contact@example.com)" // majburiy
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    if (!$response) return null;

    $data = json_decode($response, true);

    if (!is_array($data) || empty($data[0])) {
        return null;
    }

    return [
        'city' => $data[0]['display_name'] ?? $cityName,
        'lat'  => (float)$data[0]['lat'],
        'lon'  => (float)$data[0]['lon'],
    ];
}

}