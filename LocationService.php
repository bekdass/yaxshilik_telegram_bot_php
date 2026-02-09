<?php
/**
 * Location Service
 * Handles location-based calculations (nearest city by meridian)
 * Ported from handlers/prayer.py
 */

require_once __DIR__ . '/Cities.php';

class LocationService {
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * Replaces Python's geopy.distance.geodesic
     * 
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in kilometers
     */
    public function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);
        
        // Haversine formula
        $dlat = $lat2Rad - $lat1Rad;
        $dlon = $lon2Rad - $lon1Rad;
        
        $a = sin($dlat / 2) * sin($dlat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($dlon / 2) * sin($dlon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    /**
     * Find nearest city by MERIDIAN (longitude difference)
     * Ported from find_nearest_city in handlers/prayer.py
     * 
     * This matches the Python logic exactly:
     * - Primary criterion: meridian difference (longitude only)
     * - Secondary: calculate actual distance for information
     * 
     * @param float $lat User's latitude
     * @param float $lon User's longitude
     * @return array ['city' => slug, 'distance' => km]
     */
    public function findNearestCity($lat, $lon) {
        $nearestCitySlug = 'toshkent';
        $minDiff = PHP_FLOAT_MAX;
        $finalDistKm = 0;
        
        foreach (Cities::$cities as $slug => $coords) {
            list($cityLat, $cityLon) = $coords;
            
            // 1. MERIDIAN DIFFERENCE (Only longitude difference)
            $diffDeg = abs($lon - $cityLon);
            
            if ($diffDeg < $minDiff) {
                $minDiff = $diffDeg;
                $nearestCitySlug = $slug;
                
                // Calculate actual distance for information purposes
                $finalDistKm = $this->haversineDistance($lat, $lon, $cityLat, $cityLon);
            }
        }
        
        return [
            'city' => $nearestCitySlug,
            'distance' => (int)$finalDistKm
        ];
    }
    
    /**
     * Alternative method: Find nearest city by actual distance
     * This is NOT used in the Python code, but provided as an option
     * 
     * @param float $lat User's latitude
     * @param float $lon User's longitude
     * @return array ['city' => slug, 'distance' => km]
     */
    public function findNearestCityByDistance($lat, $lon) {
        $nearestCitySlug = 'toshkent';
        $minDistance = PHP_FLOAT_MAX;
        
        foreach (Cities::$cities as $slug => $coords) {
            list($cityLat, $cityLon) = $coords;
            
            $distance = $this->haversineDistance($lat, $lon, $cityLat, $cityLon);
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestCitySlug = $slug;
            }
        }
        
        return [
            'city' => $nearestCitySlug,
            'distance' => (int)$minDistance
        ];
    }
}
