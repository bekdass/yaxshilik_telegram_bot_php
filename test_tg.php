<?php
$timestamp = time();
$lat = 47.7;
$lon = 11.6;

$url = "https://api.aladhan.com/v1/timings/{$timestamp}"
     . "?latitude={$lat}&longitude={$lon}"
     . "&method=13"
     . "&school=1"
     . "&timezonestring=Europe/Berlin";

$json = file_get_contents($url);
$res  = json_decode($json, true);

echo "URL:\n$url\n\n";
echo "META:\n" . json_encode($res['data']['meta'] ?? null, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n\n";
echo "TIMINGS:\n" . json_encode($res['data']['timings'] ?? null, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "\n";
