<?php
declare(strict_types=1);

trait PrayerSourceValidator
{
    /**
     * Universal: times ichida requiredKeys borligi + bo'sh emasligi + (optional) HH:MM format.
     * @return array{0: bool, 1: string}
     */
    protected function validateTimesOrReason(
        array $times,
        array $requiredKeys = ['bomdod','peshin','asr','shom'],
        bool $checkFormat = false,
        string $timePattern = '/^\d{1,2}:\d{2}$/'
    ): array {
        $missing = [];
        $empty = [];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $times)) {
                $missing[] = $key;
                continue;
            }
            $val = trim((string)$times[$key]);
            if ($val === '') $empty[] = $key;
        }

        if ($missing) return [false, 'TIMES_MISSING_KEYS: ' . implode(', ', $missing)];
        if ($empty)   return [false, 'TIMES_EMPTY_VALUES: ' . implode(', ', $empty)];

        if ($checkFormat) {
            $bad = [];
            foreach ($requiredKeys as $key) {
                $val = trim((string)$times[$key]);
                if (!preg_match($timePattern, $val)) {
                    $bad[] = "{$key}={$val}";
                }
            }
            if ($bad) return [false, 'TIMES_BAD_FORMAT: ' . implode(', ', $bad)];
        }

        return [true, 'OK'];
    }

    /**
     * Faqat HTML scraping manbalarga: sahifada label/so'zlar borligini tekshiradi.
     * @return array{0: bool, 1: string}
     */
    protected function validateHtmlWordsOrReason(string $html, array $requiredWords): array
    {
        $missing = [];
        foreach ($requiredWords as $word) {
            $word = trim((string)$word);
            if ($word === '') continue;
            if (mb_stripos($html, $word) === false) {
                $missing[] = $word;
            }
        }
        if ($missing) return [false, 'HTML_MISSING_WORDS: ' . implode(', ', $missing)];
        return [true, 'OK'];
    }

    /**
     * Full validator: HTML word check (optional) + times check
     * @return array{0: bool, 1: string}
     */
    protected function validatePrayerSourceOrReason(
        ?string $html,
        array $times,
        array $requiredKeys = ['bomdod','peshin','asr','shom'],
        bool $checkFormat = false,
        ?array $requiredWords = null,
        string $timePattern = '/^\d{1,2}:\d{2}$/'
    ): array {
        // 1) HTML tekshiruv (faqat requiredWords berilsa)
        if ($requiredWords !== null) {
            if ($html === null || $html === '') {
                return [false, 'HTML_EMPTY'];
            }
            [$ok, $reason] = $this->validateHtmlWordsOrReason($html, $requiredWords);
            if (!$ok) return [false, $reason];
        }

        // 2) Times tekshiruv
        return $this->validateTimesOrReason($times, $requiredKeys, $checkFormat, $timePattern);
    }

    // Qulay wrapper (bool)
    protected function validatePrayerSource(
        ?string $html,
        array $times,
        array $requiredKeys = ['bomdod','peshin','asr','shom'],
        bool $checkFormat = false,
        ?array $requiredWords = null
    ): bool {
        [$ok,] = $this->validatePrayerSourceOrReason($html, $times, $requiredKeys, $checkFormat, $requiredWords);
        return $ok;
    }
}