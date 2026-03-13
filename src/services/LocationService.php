<?php
/**
 * Location Service - SaaS Koperasi Harian
 * 
 * Handles GPS tracking, geofencing, and location validation
 * with anti-fake GPS detection and spatial queries
 * 
 * @author KSP Lam Gabe Jaya Development Team
 * @version 1.0
 */

class LocationService
{
    private $db;
    private $googleMapsApiKey;
    private $osmBaseUrl;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->googleMapsApiKey = env('GOOGLE_MAPS_API_KEY');
        $this->osmBaseUrl = 'https://nominatim.openstreetmap.org';
    }
    
    /**
     * Get Nearby Members within specified radius
     */
    public function getNearbyMembers(float $lat, float $lng, int $radiusMeters = 5000, int $tenantId = null): array
    {
        try {
            // Use PostGIS for spatial query if available
            if ($this->isPostGISAvailable()) {
                return $this->getNearbyMembersPostGIS($lat, $lng, $radiusMeters, $tenantId);
            } else {
                return $this->getNearbyMembersHaversine($lat, $lng, $radiusMeters, $tenantId);
            }
        } catch (Exception $e) {
            throw new Exception('Failed to get nearby members: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate Location for Transaction
     */
    public function validateTransactionLocation(int $memberId, float $mantriLat, float $mantriLng, int $tenantId): array
    {
        try {
            // Get member location
            $member = $this->getMemberLocation($memberId, $tenantId);
            
            if (!$member) {
                throw new Exception('Member not found');
            }
            
            // Calculate distance
            $distance = $this->calculateDistance($mantriLat, $mantriLng, $member['gps_lat'], $member['gps_lng']);
            
            // Check geofence radius (default 50m)
            $geofenceRadius = $this->getGeofenceRadius($tenantId);
            
            $validation = [
                'valid' => $distance <= $geofenceRadius,
                'distance' => $distance,
                'max_distance' => $geofenceRadius,
                'member_location' => [
                    'lat' => $member['gps_lat'],
                    'lng' => $member['gps_lng'],
                    'address' => $member['address']
                ],
                'mantri_location' => [
                    'lat' => $mantriLat,
                    'lng' => $mantriLng
                ]
            ];
            
            // Log location validation
            $this->logLocationValidation($memberId, $validation, $tenantId);
            
            return $validation;
            
        } catch (Exception $e) {
            throw new Exception('Location validation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Detect Fake GPS
     */
    public function detectFakeGPS(array $locationData): array
    {
        try {
            $detection = [
                'is_fake' => false,
                'confidence' => 0,
                'reasons' => []
            ];
            
            // Check for impossible speeds
            if (isset($locationData['previous_location']) && isset($locationData['timestamp'])) {
                $speed = $this->calculateSpeed(
                    $locationData['previous_location']['lat'],
                    $locationData['previous_location']['lng'],
                    $locationData['lat'],
                    $locationData['lng'],
                    $locationData['timestamp']
                );
                
                if ($speed > 200) { // More than 200 km/h is suspicious
                    $detection['is_fake'] = true;
                    $detection['confidence'] += 0.4;
                    $detection['reasons'][] = 'Impossible speed detected: ' . round($speed, 2) . ' km/h';
                }
            }
            
            // Check for location jumping
            if (isset($locationData['location_history']) && count($locationData['location_history']) > 1) {
                $jumpDetection = $this->detectLocationJumps($locationData['location_history']);
                if ($jumpDetection['is_suspicious']) {
                    $detection['is_fake'] = true;
                    $detection['confidence'] += 0.3;
                    $detection['reasons'][] = 'Suspicious location jumps detected';
                }
            }
            
            // Check GPS accuracy
            if (isset($locationData['accuracy']) && $locationData['accuracy'] > 100) {
                $detection['confidence'] += 0.2;
                $detection['reasons'][] = 'Low GPS accuracy: ' . $locationData['accuracy'] . 'm';
            }
            
            // Check for mock locations (Android)
            if (isset($locationData['is_mock_location']) && $locationData['is_mock_location']) {
                $detection['is_fake'] = true;
                $detection['confidence'] = 1.0;
                $detection['reasons'][] = 'Mock location detected';
            }
            
            // Check for altitude anomalies
            if (isset($locationData['altitude'])) {
                $altitude = $locationData['altitude'];
                if ($altitude < -500 || $altitude > 10000) {
                    $detection['confidence'] += 0.1;
                    $detection['reasons'][] = 'Suspicious altitude: ' . $altitude . 'm';
                }
            }
            
            return $detection;
            
        } catch (Exception $e) {
            throw new Exception('Fake GPS detection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Optimize Route for Mantri
     */
    public function optimizeRoute(array $memberLocations, float $startLat, float $startLng): array
    {
        try {
            if (count($memberLocations) < 2) {
                return [
                    'optimized_route' => $memberLocations,
                    'total_distance' => 0,
                    'estimated_time' => 0
                ];
            }
            
            // Simple nearest neighbor algorithm for route optimization
            $optimized = $this->nearestNeighborAlgorithm($memberLocations, $startLat, $startLng);
            
            // Calculate total distance
            $totalDistance = 0;
            $currentLat = $startLat;
            $currentLng = $startLng;
            
            foreach ($optimized as $member) {
                $distance = $this->calculateDistance($currentLat, $currentLng, $member['gps_lat'], $member['gps_lng']);
                $totalDistance += $distance;
                $currentLat = $member['gps_lat'];
                $currentLng = $member['gps_lng'];
            }
            
            // Estimate time (assuming average speed of 30 km/h in urban areas)
            $estimatedTime = ($totalDistance / 1000) / 30 * 60; // in minutes
            
            return [
                'optimized_route' => $optimized,
                'total_distance' => round($totalDistance, 2),
                'estimated_time' => round($estimatedTime, 2),
                'waypoints' => $this->generateWaypoints($optimized)
            ];
            
        } catch (Exception $e) {
            throw new Exception('Route optimization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Location Analytics
     */
    public function getLocationAnalytics(int $tenantId, string $dateFrom, string $dateTo): array
    {
        try {
            $analytics = [
                'total_locations' => 0,
                'unique_locations' => 0,
                'average_accuracy' => 0,
                'fake_gps_attempts' => 0,
                'geofence_violations' => 0,
                'coverage_areas' => [],
                'peak_hours' => [],
                'location_heatmap' => []
            ];
            
            // Get location logs
            $sql = "SELECT 
                        COUNT(*) as total_locations,
                        COUNT(DISTINCT CONCAT(ROUND(lat, 4), ',', ROUND(lng, 4))) as unique_locations,
                        AVG(accuracy) as average_accuracy
                    FROM location_logs 
                    WHERE tenant_id = ? 
                    AND created_at BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tenantId, $dateFrom, $dateTo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $analytics['total_locations'] = (int)$result['total_locations'];
            $analytics['unique_locations'] = (int)$result['unique_locations'];
            $analytics['average_accuracy'] = round($result['average_accuracy'], 2);
            
            // Get fake GPS attempts
            $sql = "SELECT COUNT(*) as fake_attempts 
                    FROM geofence_violations 
                    WHERE tenant_id = ? 
                    AND violation_type = 'fake_gps'
                    AND created_at BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tenantId, $dateFrom, $dateTo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['fake_gps_attempts'] = (int)$result['fake_attempts'];
            
            // Get geofence violations
            $sql = "SELECT COUNT(*) as violations 
                    FROM geofence_violations 
                    WHERE tenant_id = ? 
                    AND violation_type = 'out_of_range'
                    AND created_at BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tenantId, $dateFrom, $dateTo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $analytics['geofence_violations'] = (int)$result['violations'];
            
            // Generate heatmap data
            $analytics['location_heatmap'] = $this->generateHeatmapData($tenantId, $dateFrom, $dateTo);
            
            // Get peak hours
            $analytics['peak_hours'] = $this->getPeakHours($tenantId, $dateFrom, $dateTo);
            
            return $analytics;
            
        } catch (Exception $e) {
            throw new Exception('Location analytics failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update Mantri Location
     */
    public function updateMantriLocation(int $mantriId, float $lat, float $lng, array $metadata = []): void
    {
        try {
            // Detect fake GPS
            $fakeDetection = $this->detectFakeGPS([
                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $metadata['accuracy'] ?? null,
                'altitude' => $metadata['altitude'] ?? null,
                'timestamp' => time(),
                'is_mock_location' => $metadata['is_mock_location'] ?? false
            ]);
            
            // Log location
            $sql = "INSERT INTO location_logs (uuid, mantri_id, location, accuracy, altitude, speed, heading, timestamp, device_info, created_at) 
                    VALUES (?, ?, POINT(?, ?), ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->generateUuid(),
                $mantriId,
                $lng,
                $lat,
                $metadata['accuracy'] ?? null,
                $metadata['altitude'] ?? null,
                $metadata['speed'] ?? null,
                $metadata['heading'] ?? null,
                json_encode($metadata['device_info'] ?? [])
            ]);
            
            // Update mantri current location
            $sql = "UPDATE mantris SET last_location = POINT(?, ?), last_location_update = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lng, $lat, $mantriId]);
            
            // Log fake GPS detection
            if ($fakeDetection['is_fake']) {
                $this->logFakeGPSDetection($mantriId, $fakeDetection);
            }
            
        } catch (Exception $e) {
            throw new Exception('Failed to update mantri location: ' . $e->getMessage());
        }
    }
    
    /**
     * Private Helper Methods
     */
    
    private function isPostGISAvailable(): bool
    {
        try {
            $result = $this->db->query("SELECT 1 FROM information_schema.columns WHERE table_name = 'members' AND column_name = 'location' AND data_type = 'point'")->fetch();
            return $result !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getNearbyMembersPostGIS(float $lat, float $lng, int $radiusMeters, ?int $tenantId): array
    {
        $sql = "SELECT 
                    id, uuid, name, phone, credit_score, 
                    gps_lat, gps_lng, address,
                    ST_Distance(location, POINT(?, ?)) as distance
                FROM members 
                WHERE ST_DWithin(location, POINT(?, ?), ?)
                AND status = 'active'" .
                ($tenantId ? " AND tenant_id = ?" : "") .
                " ORDER BY distance
                LIMIT 10";
        
        $params = [$lng, $lat, $lng, $lat, $radiusMeters];
        if ($tenantId) {
            $params[] = $tenantId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = [
                'id' => $row['id'],
                'uuid' => $row['uuid'],
                'name' => $row['name'],
                'phone' => $row['phone'],
                'credit_score' => $row['credit_score'],
                'location' => [
                    'lat' => $row['gps_lat'],
                    'lng' => $row['gps_lng'],
                    'address' => $row['address']
                ],
                'distance' => round($row['distance'], 2)
            ];
        }
        
        return $members;
    }
    
    private function getNearbyMembersHaversine(float $lat, float $lng, int $radiusMeters, ?int $tenantId): array
    {
        $sql = "SELECT 
                    id, uuid, name, phone, credit_score, 
                    gps_lat, gps_lng, address,
                    (6371 * acos(cos(radians(?)) * cos(radians(gps_lat)) * 
                    cos(radians(gps_lng) - radians(?)) + sin(radians(?)) * 
                    sin(radians(gps_lat)))) * 1000 as distance
                FROM members 
                WHERE status = 'active'" .
                ($tenantId ? " AND tenant_id = ?" : "") .
                " HAVING distance <= ?
                ORDER BY distance
                LIMIT 10";
        
        $params = [$lat, $lng, $lat];
        if ($tenantId) {
            $params[] = $tenantId;
        }
        $params[] = $radiusMeters;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $members[] = [
                'id' => $row['id'],
                'uuid' => $row['uuid'],
                'name' => $row['name'],
                'phone' => $row['phone'],
                'credit_score' => $row['credit_score'],
                'location' => [
                    'lat' => $row['gps_lat'],
                    'lng' => $row['gps_lng'],
                    'address' => $row['address']
                ],
                'distance' => round($row['distance'], 2)
            ];
        }
        
        return $members;
    }
    
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);
        
        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    private function calculateSpeed(float $lat1, float $lng1, float $lat2, float $lng2, int $timestamp): float
    {
        $distance = $this->calculateDistance($lat1, $lng1, $lat2, $lng2);
        $timeDiff = $timestamp - (time() - 60); // Assume previous location was 60 seconds ago
        
        if ($timeDiff <= 0) return 0;
        
        $speedMetersPerSecond = $distance / $timeDiff;
        return $speedMetersPerSecond * 3.6; // Convert to km/h
    }
    
    private function detectLocationJumps(array $locationHistory): array
    {
        $jumps = 0;
        $totalDistance = 0;
        
        for ($i = 1; $i < count($locationHistory); $i++) {
            $prev = $locationHistory[$i - 1];
            $curr = $locationHistory[$i];
            
            $distance = $this->calculateDistance($prev['lat'], $prev['lng'], $curr['lat'], $curr['lng']);
            $timeDiff = $curr['timestamp'] - $prev['timestamp'];
            
            if ($timeDiff > 0) {
                $speed = ($distance / $timeDiff) * 3.6; // km/h
                if ($speed > 100) { // Suspicious if > 100 km/h
                    $jumps++;
                }
            }
            
            $totalDistance += $distance;
        }
        
        return [
            'is_suspicious' => $jumps > count($locationHistory) * 0.3, // More than 30% suspicious jumps
            'jump_count' => $jumps,
            'total_distance' => $totalDistance
        ];
    }
    
    private function nearestNeighborAlgorithm(array $locations, float $startLat, float $startLng): array
    {
        $unvisited = $locations;
        $route = [];
        $currentLat = $startLat;
        $currentLng = $startLng;
        
        while (!empty($unvisited)) {
            $nearestIndex = 0;
            $nearestDistance = PHP_FLOAT_MAX;
            
            foreach ($unvisited as $index => $location) {
                $distance = $this->calculateDistance($currentLat, $currentLng, $location['gps_lat'], $location['gps_lng']);
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }
            
            $nearest = $unvisited[$nearestIndex];
            $route[] = $nearest;
            $currentLat = $nearest['gps_lat'];
            $currentLng = $nearest['gps_lng'];
            array_splice($unvisited, $nearestIndex, 1);
        }
        
        return $route;
    }
    
    private function generateWaypoints(array $route): array
    {
        $waypoints = [];
        
        foreach ($route as $index => $location) {
            $waypoints[] = [
                'order' => $index + 1,
                'lat' => $location['gps_lat'],
                'lng' => $location['gps_lng'],
                'name' => $location['name'],
                'address' => $location['address']
            ];
        }
        
        return $waypoints;
    }
    
    private function getMemberLocation(int $memberId, int $tenantId): ?array
    {
        $sql = "SELECT id, name, gps_lat, gps_lng, address FROM members WHERE id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getGeofenceRadius(int $tenantId): int
    {
        // Get geofence radius from settings (default 50 meters)
        $sql = "SELECT setting_value FROM system_settings WHERE tenant_id = ? AND setting_key = 'geofence_radius'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['setting_value'] : 50;
    }
    
    private function logLocationValidation(int $memberId, array $validation, int $tenantId): void
    {
        if (!$validation['valid']) {
            $sql = "INSERT INTO geofence_violations (uuid, member_id, mantri_id, violation_type, expected_location, actual_location, distance_meters, description, severity, created_at) 
                    VALUES (?, ?, ?, 'out_of_range', POINT(?, ?), POINT(?, ?), ?, ?, 'medium', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->generateUuid(),
                $memberId,
                null, // mantri_id would be passed from context
                $validation['member_location']['lng'],
                $validation['member_location']['lat'],
                $validation['mantri_location']['lng'],
                $validation['mantri_location']['lat'],
                $validation['distance'],
                "Distance exceeded: {$validation['distance']}m > {$validation['max_distance']}m"
            ]);
        }
    }
    
    private function logFakeGPSDetection(int $mantriId, array $detection): void
    {
        $sql = "INSERT INTO geofence_violations (uuid, mantri_id, violation_type, description, severity, created_at) 
                VALUES (?, ?, 'fake_gps', ?, 'high', NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->generateUuid(),
            $mantriId,
            implode('; ', $detection['reasons'])
        ]);
    }
    
    private function generateHeatmapData(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT 
                    ROUND(location_lat, 4) as lat,
                    ROUND(location_lng, 4) as lng,
                    COUNT(*) as intensity
                FROM location_logs 
                WHERE tenant_id = ? 
                AND created_at BETWEEN ? AND ?
                GROUP BY ROUND(location_lat, 4), ROUND(location_lng, 4)
                ORDER BY intensity DESC
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId, $dateFrom, $dateTo]);
        
        $heatmapData = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $heatmapData[] = [
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'intensity' => (int)$row['intensity']
            ];
        }
        
        return $heatmapData;
    }
    
    private function getPeakHours(int $tenantId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as location_count
                FROM location_logs 
                WHERE tenant_id = ? 
                AND created_at BETWEEN ? AND ?
                GROUP BY HOUR(created_at)
                ORDER BY location_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId, $dateFrom, $dateTo]);
        
        $peakHours = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $peakHours[] = [
                'hour' => (int)$row['hour'],
                'count' => (int)$row['location_count']
            ];
        }
        
        return $peakHours;
    }
    
    private function generateUuid(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

/**
 * Usage Examples:
 * 
 * $locationService = new LocationService();
 * 
 * // Get nearby members
 * $members = $locationService->getNearbyMembers(-6.2088, 106.8456, 5000, 1);
 * 
 * // Validate transaction location
 * $validation = $locationService->validateTransactionLocation(123, -6.2088, 106.8456, 1);
 * 
 * // Detect fake GPS
 * $detection = $locationService->detectFakeGPS([
 *     'lat' => -6.2088,
 *     'lng' => 106.8456,
 *     'accuracy' => 10,
 *     'timestamp' => time(),
 *     'is_mock_location' => false
 * ]);
 * 
 * // Optimize route
 * $route = $locationService->optimizeRoute($memberLocations, -6.2088, 106.8456);
 * 
 * // Update mantri location
 * $locationService->updateMantriLocation(456, -6.2088, 106.8456, [
 *     'accuracy' => 10,
 *     'altitude' => 50,
 *     'device_info' => ['device_id' => '12345']
 * ]);
 */
