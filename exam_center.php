<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start session for authentication

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header('location:login.php');
    exit();
}

// Exam center coordinates
$examCenter = [
    "name" => "Kathmandu Examination Center",
    "lat" => 27.7172,
    "lng" => 85.3240,
    "opening_time" => "08:00",
    "closing_time" => "17:00"
];

// OpenRouteService API key
$apiKey = "5b3ce3597851110001cf6248";

if (isset($_POST['action']) && $_POST['action'] === 'calculate') {
    $userLat = floatval($_POST['lat'] ?? 0);
    $userLng = floatval($_POST['lng'] ?? 0);

    if (!$userLat || !$userLng) {
        echo json_encode(["status" => "ERROR", "message" => "Invalid coordinates"]);
        exit;
    }

    // Enhanced Haversine calculation server-side
    function calculateHaversine($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return [
            'km' => round($distance, 2),
            'meters' => round($distance * 1000),
            'miles' => round($distance * 0.621371, 2)
        ];
    }

    // Calculate straight-line distance using Haversine
    $straightDistance = calculateHaversine($userLat, $userLng, $examCenter['lat'], $examCenter['lng']);

    function getORSData($mode, $start, $end, $apiKey) {
        $url = "https://api.openrouteservice.org/v2/directions/$mode";
        
        try {
            $body = [
                "coordinates" => [
                    [$start[1], $start[0]], // ORS uses [lng, lat]
                    [$end[1], $end[0]]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: ' . $apiKey
                ],
                CURLOPT_TIMEOUT => 15
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("API returned status code: " . $httpCode);
            }
            
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to parse JSON response");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("OpenRouteService API error: " . $e->getMessage());
            return ["error" => $e->getMessage()];
        }
    }

    // Get routing data
    $driving = getORSData("driving-car", [$userLat, $userLng], [$examCenter['lat'], $examCenter['lng']], $apiKey);
    $walking = getORSData("foot-walking", [$userLat, $userLng], [$examCenter['lat'], $examCenter['lng']], $apiKey);

    // Enhanced time estimation with traffic factors
    function estimateTravelTime($distanceKm, $mode, $apiData = null) {
        $baseSpeeds = [
            'driving' => 30, // km/h - average city driving speed
            'walking' => 5,  // km/h - average walking speed
            'cycling' => 15   // km/h - average cycling speed
        ];
        
        $trafficFactor = 1.2; // 20% extra time for traffic/lights
        
        if ($apiData && isset($apiData['routes'][0]['summary']['duration'])) {
            // Use API data if available
            $apiDuration = round($apiData['routes'][0]['summary']['duration'] / 60); // Convert to minutes
            return $apiDuration;
        } else {
            // Fallback calculation
            $speed = $baseSpeeds[$mode] ?? 5;
            $timeHours = $distanceKm / $speed;
            $timeMinutes = round($timeHours * 60 * $trafficFactor);
            return max(1, $timeMinutes); // At least 1 minute
        }
    }

    // Calculate times
    $drivingTime = estimateTravelTime($straightDistance['km'], 'driving', $driving);
    $walkingTime = estimateTravelTime($straightDistance['km'], 'walking', $walking);
    
    // Add buffer times
    $drivingTimeWithBuffer = $drivingTime + 10; // 10 min buffer for parking/traffic
    $walkingTimeWithBuffer = $walkingTime + 5;  // 5 min buffer for walking

    // Calculate arrival times
    $currentTime = time();
    $drivingArrival = $currentTime + ($drivingTimeWithBuffer * 60);
    $walkingArrival = $currentTime + ($walkingTimeWithBuffer * 60);

    // Prepare response
    $response = [
        "status" => "OK",
        "straight_distance" => $straightDistance,
        "routing" => [
            "driving" => [
                "distance_km" => isset($driving['routes'][0]['summary']['distance']) ? 
                    round($driving['routes'][0]['summary']['distance'] / 1000, 2) : $straightDistance['km'],
                "time_minutes" => $drivingTimeWithBuffer,
                "arrival_time" => date('H:i', $drivingArrival),
                "depart_by" => date('H:i', $currentTime - 300) // 5 minutes ago for "now"
            ],
            "walking" => [
                "distance_km" => isset($walking['routes'][0]['summary']['distance']) ? 
                    round($walking['routes'][0]['summary']['distance'] / 1000, 2) : $straightDistance['km'],
                "time_minutes" => $walkingTimeWithBuffer,
                "arrival_time" => date('H:i', $walkingArrival),
                "depart_by" => date('H:i', $currentTime - 300)
            ]
        ],
        "exam_center_info" => [
            "opening_time" => $examCenter['opening_time'],
            "closing_time" => $examCenter['closing_time'],
            "current_time" => date('H:i')
        ]
    ];

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Examination Center Distance & Travel Time Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --dark-bg: #121212;
            --darker-bg: #0a0a0a;
            --card-bg: #1e1e1e;
            --card-border: #333333;
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --text-muted: #808080;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --map-tile-filter: brightness(0.6) invert(1) contrast(3) hue-rotate(200deg) saturate(0.3) brightness(0.7);
        }

        body { 
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%); 
            color: var(--text-primary);
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        #map { 
            height: 500px; 
            border-radius: 12px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
            z-index: 1;
        }

        /* Dark mode map tiles */
        .leaflet-tile {
            filter: var(--map-tile-filter);
        }

        .leaflet-container {
            background: #2d2d2d;
        }

        .info-card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }

        .transport-card { 
            background: rgba(30, 30, 30, 0.8); 
            border-left: 4px solid var(--primary);
            margin-bottom: 15px; 
            padding: 20px; 
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .transport-card:hover {
            background: rgba(40, 40, 40, 0.9);
            transform: translateX(5px);
        }

        .transport-card.walking { 
            border-left-color: var(--success);
        }

        .transport-card.walking:hover {
            border-left-color: #34d399;
        }

        .time-badge { 
            background: linear-gradient(135deg, var(--info) 0%, #3b82f6 100%);
            color: white; 
            padding: 8px 16px; 
            border-radius: 25px; 
            font-size: 0.9em; 
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        .distance-badge { 
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white; 
            padding: 8px 16px; 
            border-radius: 25px; 
            font-size: 0.9em; 
            font-weight: 600;
        }

        .arrival-time { 
            font-size: 1.3em; 
            font-weight: bold; 
            color: var(--success);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .exam-info { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
            color: white; 
            border-radius: 15px; 
            padding: 30px 20px;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }

        .exam-info::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: float 20s linear infinite;
            opacity: 0.3;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-30px, -30px) rotate(360deg); }
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 600;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .alert {
            background: rgba(30, 30, 30, 0.9);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .alert-info {
            border-left: 4px solid var(--info);
        }

        .alert-success {
            border-left: 4px solid var(--success);
        }

        .alert-warning {
            border-left: 4px solid var(--warning);
        }

        .alert-danger {
            border-left: 4px solid var(--danger);
        }

        .border {
            border-color: var(--card-border) !important;
        }

        .bg-light {
            background-color: rgba(40, 40, 40, 0.8) !important;
        }

        .small {
            color: var(--text-secondary);
        }

        .fw-bold {
            color: var(--text-primary);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Loading animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Glass morphism effect */
        .glass-effect {
            background: rgba(30, 30, 30, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="exam-info text-center glass-effect">
                <h2 class="mb-3"><i class="fas fa-graduation-cap me-3"></i>Examination Center Navigator</h2>
                <p class="mb-0">Calculate your travel time and distance to the exam center</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="info-card glass-effect">
                <h5 class="mb-3"><i class="fas fa-location-crosshairs me-2"></i>Live Location Tracking</h5>
                <p class="text-muted small mb-4">Enable location access to calculate real-time travel information</p>
                <button onclick="getCurrentLocation()" class="btn btn-primary w-100 mb-4">
                    <i class="fas fa-location-dot me-2"></i> Start Live Tracking
                </button>
                <div id="locationStatus" class="alert" style="display:none;"></div>
                
                <!-- Status Indicator -->
                <div class="d-flex align-items-center mt-3 text-muted small">
                    <div id="statusIndicator" class="me-2" style="width: 10px; height: 10px; border-radius: 50%; background: var(--text-muted);"></div>
                    <span id="statusText">Location tracking inactive</span>
                </div>
            </div>

            <div id="routeDetails" style="display:none;">
                <div class="info-card glass-effect">
                    <h5 class="mb-4"><i class="fas fa-route me-2"></i>Travel Information</h5>
                    
                    <!-- Straight Line Distance -->
                    <div class="mb-4 p-3 border rounded glass-effect">
                        <h6 class="mb-3"><i class="fas fa-ruler-combined me-2"></i>Direct Distance</h6>
                        <div id="straightDistance" class="fw-bold text-primary fs-5"></div>
                    </div>

                    <!-- Driving Information -->
                    <div class="transport-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fas fa-car me-2"></i>By Vehicle</h6>
                            <span class="time-badge" id="drivingTime"></span>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Distance</small>
                                <div id="drivingDistance" class="fw-bold fs-6"></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Arrival Time</small>
                                <div class="arrival-time" id="drivingArrival"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Walking Information -->
                    <div class="transport-card walking">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0"><i class="fas fa-walking me-2"></i>Walking</h6>
                            <span class="time-badge" id="walkingTime"></span>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Distance</small>
                                <div id="walkingDistance" class="fw-bold fs-6"></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Arrival Time</small>
                                <div class="arrival-time" id="walkingArrival"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Exam Center Info -->
                    <div class="mt-4 p-3 border rounded glass-effect">
                        <h6 class="mb-3"><i class="fas fa-building me-2"></i>Exam Center Hours</h6>
                        <div class="small">
                            <div class="mb-2 d-flex justify-content-between">
                                <span>Opening Time:</span>
                                <strong id="openingTime" class="text-success"></strong>
                            </div>
                            <div class="mb-2 d-flex justify-content-between">
                                <span>Closing Time:</span>
                                <strong id="closingTime" class="text-danger"></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current Time:</span>
                                <strong id="currentTime" class="text-info"></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div id="map"></div>
            <div class="text-center mt-3 text-muted small">
                <i class="fas fa-info-circle me-2"></i>Blue line shows direct path, markers show your location and exam center
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, userMarker, routingLine;
let distanceMarker = null;
let isTracking = false;
const examCenter = { 
    lat: <?php echo $examCenter['lat']; ?>, 
    lng: <?php echo $examCenter['lng']; ?>, 
    name: "<?php echo $examCenter['name']; ?>"
};

function updateStatusIndicator(status, text) {
    const indicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    
    switch(status) {
        case 'active':
            indicator.style.background = 'var(--success)';
            indicator.classList.add('pulse');
            break;
        case 'warning':
            indicator.style.background = 'var(--warning)';
            indicator.classList.add('pulse');
            break;
        case 'error':
            indicator.style.background = 'var(--danger)';
            indicator.classList.remove('pulse');
            break;
        default:
            indicator.style.background = 'var(--text-muted)';
            indicator.classList.remove('pulse');
    }
    statusText.textContent = text;
}

// Enhanced Haversine calculation client-side
function calculateHaversine(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in kilometers
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    
    return {
        km: Math.round(distance * 100) / 100,
        meters: Math.round(distance * 1000),
        miles: Math.round(distance * 0.621371 * 100) / 100
    };
}

function initMap() {
    map = L.map('map').setView([examCenter.lat, examCenter.lng], 13);
    
    // Use CartoDB dark tiles
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '©OpenStreetMap, ©CartoDB',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);
    
    // Exam center marker with custom icon
    const examIcon = L.divIcon({
        className: 'exam-marker',
        html: `<div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
                         width: 40px; height: 40px; border-radius: 50%; 
                         display: flex; align-items: center; justify-content: center;
                         box-shadow: 0 4px 15px rgba(99, 102, 241, 0.5); border: 3px solid white;">
                <i class="fas fa-graduation-cap" style="color: white; font-size: 18px;"></i>
              </div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    
    L.marker([examCenter.lat, examCenter.lng], {
        icon: examIcon
    }).addTo(map).bindPopup(`
        <div style="background: var(--card-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--card-border);">
            <h5 style="color: var(--primary); margin-bottom: 8px;">${examCenter.name}</h5>
            <p style="color: var(--text-secondary); margin: 0;">Your Exam Center</p>
            <hr style="border-color: var(--card-border); margin: 10px 0;">
            <small style="color: var(--text-muted);">
                <i class="fas fa-clock me-1"></i> ${examCenter.opening_time} - ${examCenter.closing_time}
            </small>
        </div>
    `).openPopup();
}

let locationWatcher = null;

function getCurrentLocation() {
    if (isTracking) {
        stopTracking();
        return;
    }
    
    const status = $('#locationStatus');
    status.removeClass().addClass('alert alert-info').html('<i class="fas fa-sync fa-spin me-2"></i>Initializing live location tracking...').show();
    updateStatusIndicator('active', 'Initializing tracking...');

    if (!navigator.geolocation) {
        status.removeClass().addClass('alert alert-danger').text('Geolocation not supported by your browser.');
        updateStatusIndicator('error', 'Browser not supported');
        return;
    }

    if (locationWatcher !== null) {
        navigator.geolocation.clearWatch(locationWatcher);
    }

    locationWatcher = navigator.geolocation.watchPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const accuracy = position.coords.accuracy;

            updateUserLocation(lat, lng, accuracy, status);
        },
        error => {
            handleLocationError(error, status);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 20000,
            timeout: 15000
        }
    );
    
    isTracking = true;
    document.querySelector('button[onclick="getCurrentLocation()"]').innerHTML = 
        '<i class="fas fa-stop-circle me-2"></i> Stop Tracking';
}

function stopTracking() {
    if (locationWatcher !== null) {
        navigator.geolocation.clearWatch(locationWatcher);
        locationWatcher = null;
    }
    
    isTracking = false;
    updateStatusIndicator('', 'Location tracking inactive');
    document.querySelector('button[onclick="getCurrentLocation()"]').innerHTML = 
        '<i class="fas fa-location-dot me-2"></i> Start Live Tracking';
    
    $('#locationStatus').removeClass().addClass('alert alert-warning')
        .html('<i class="fas fa-pause-circle me-2"></i>Live tracking stopped').show();
}

function updateUserLocation(lat, lng, accuracy, status) {
    // Update or create user marker
    if (userMarker) {
        map.removeLayer(userMarker);
    }
    
    const userIcon = L.divIcon({
        className: 'user-marker',
        html: `<div style="background: linear-gradient(135deg, var(--success) 0%, #059669 100%); 
                         width: 36px; height: 36px; border-radius: 50%; 
                         display: flex; align-items: center; justify-content: center;
                         box-shadow: 0 4px 15px rgba(16, 185, 129, 0.5); border: 3px solid white;
                         animation: pulse 2s infinite;">
                <i class="fas fa-user" style="color: white; font-size: 16px;"></i>
              </div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 36]
    });
    
    userMarker = L.marker([lat, lng], {
        icon: userIcon
    }).addTo(map).bindPopup(`
        <div style="background: var(--card-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--card-border);">
            <h6 style="color: var(--success); margin-bottom: 8px;">Your Current Location</h6>
            <p style="color: var(--text-secondary); margin: 0;">
                <i class="fas fa-crosshairs me-1"></i> Accuracy: ${Math.round(accuracy)} meters
            </p>
        </div>
    `).openPopup();

    // Update routing line
    if (routingLine) {
        map.removeLayer(routingLine);
    }
    
    routingLine = L.polyline([[lat, lng], [examCenter.lat, examCenter.lng]], {
        color: 'var(--primary)',
        weight: 5,
        opacity: 0.8,
        dashArray: '10, 10',
        lineCap: 'round'
    }).addTo(map);
    
    map.fitBounds(routingLine.getBounds(), { padding: [50, 50] });
    updateStatusIndicator('active', 'Live tracking active');

    // Calculate and display distances
    calculateAndDisplayDistances(lat, lng, accuracy, status);
}

function calculateAndDisplayDistances(lat, lng, accuracy, status) {
    // Immediate Haversine calculation
    const straightDistance = calculateHaversine(lat, lng, examCenter.lat, examCenter.lng);
    
    // Update straight distance display immediately
    $('#straightDistance').html(`
        <div class="d-flex align-items-center">
            <i class="fas fa-arrow-right me-2" style="color: var(--primary);"></i>
            <div>
                <span class="fs-3">${straightDistance.km} km</span><br>
                <small class="text-muted">${straightDistance.meters} meters • ${straightDistance.miles} miles</small>
            </div>
        </div>
    `);

    // Update distance marker
    updateDistanceMarker(lat, lng, straightDistance);

    // Get detailed routing from server
    $.ajax({
        url: '',
        method: 'POST',
        data: { action: 'calculate', lat, lng },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            if (response.status === 'OK') {
                displayRouteDetails(response);
                status.removeClass().addClass('alert alert-success')
                    .html('<i class="fas fa-check-circle me-2"></i>Location updated! Travel information calculated.');
            } else {
                throw new Error(response.message || 'Server error');
            }
        },
        error: function(xhr, statusObj, error) {
            console.error('Routing API error:', error);
            // Fallback to Haversine-only estimates
            displayFallbackEstimates(straightDistance);
            status.removeClass().addClass('alert alert-warning')
                .html('<i class="fas fa-exclamation-triangle me-2"></i>Using estimated travel times (routing service unavailable)');
            updateStatusIndicator('warning', 'Using estimated data');
        }
    });
}

function updateDistanceMarker(lat, lng, distance) {
    const midLat = (lat + examCenter.lat) / 2;
    const midLng = (lng + examCenter.lng) / 2;
    
    if (distanceMarker) {
        map.removeLayer(distanceMarker);
    }
    
    distanceMarker = L.marker([midLat, midLng], {
        icon: L.divIcon({
            className: 'distance-marker',
            html: `<div style="background: var(--card-bg); padding: 12px 20px; border-radius: 12px; 
                         box-shadow: 0 6px 20px rgba(0,0,0,0.4); border: 2px solid var(--primary); 
                         font-weight: bold; color: var(--primary); backdrop-filter: blur(10px);">
                    <i class="fas fa-ruler me-2"></i>${distance.km} km
                  </div>`,
            iconSize: [100, 45],
            iconAnchor: [50, 22]
        })
    }).addTo(map).bindPopup(`
        <div style="background: var(--card-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--card-border);">
            <h6 style="color: var(--primary); margin-bottom: 8px;"><i class="fas fa-ruler-combined me-2"></i>Direct Distance</h6>
            <p style="color: var(--text-secondary); margin: 0;">
                ${distance.km} kilometers<br>
                ${distance.meters} meters<br>
                ${distance.miles} miles
            </p>
        </div>
    `);
}

function displayRouteDetails(data) {
    $('#routeDetails').show();
    
    // Update driving information
    $('#drivingDistance').html(`<i class="fas fa-road me-2"></i>${data.routing.driving.distance_km} km`);
    $('#drivingTime').text(data.routing.driving.time_minutes + ' min');
    $('#drivingArrival').html(`<i class="fas fa-clock me-2"></i>${data.routing.driving.arrival_time}`);
    
    // Update walking information
    $('#walkingDistance').html(`<i class="fas fa-person-walking me-2"></i>${data.routing.walking.distance_km} km`);
    $('#walkingTime').text(data.routing.walking.time_minutes + ' min');
    $('#walkingArrival').html(`<i class="fas fa-clock me-2"></i>${data.routing.walking.arrival_time}`);
    
    // Update exam center info
    $('#openingTime').text(data.exam_center_info.opening_time);
    $('#closingTime').text(data.exam_center_info.closing_time);
    $('#currentTime').text(data.exam_center_info.current_time);
}

function displayFallbackEstimates(distance) {
    $('#routeDetails').show();
    
    // Estimate times based on distance only
    const drivingTime = Math.round((distance.km / 30) * 60) + 10; // 30 km/h + buffer
    const walkingTime = Math.round((distance.km / 5) * 60) + 5;   // 5 km/h + buffer
    
    const now = new Date();
    const drivingArrival = new Date(now.getTime() + drivingTime * 60000);
    const walkingArrival = new Date(now.getTime() + walkingTime * 60000);
    
    $('#drivingDistance').html(`<i class="fas fa-road me-2"></i>${distance.km} km`);
    $('#drivingTime').text(drivingTime + ' min');
    $('#drivingArrival').html(`<i class="fas fa-clock me-2"></i>${drivingArrival.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`);
    
    $('#walkingDistance').html(`<i class="fas fa-person-walking me-2"></i>${distance.km} km`);
    $('#walkingTime').text(walkingTime + ' min');
    $('#walkingArrival').html(`<i class="fas fa-clock me-2"></i>${walkingArrival.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}`);
    
    // Exam center info
    $('#openingTime').text('08:00');
    $('#closingTime').text('17:00');
    $('#currentTime').text(now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
}

function handleLocationError(error, status) {
    let message = 'Unknown location error';
    switch(error.code) {
        case error.PERMISSION_DENIED:
            message = 'Location access denied. Please enable location permissions.';
            break;
        case error.POSITION_UNAVAILABLE:
            message = 'Location information unavailable.';
            break;
        case error.TIMEOUT:
            message = 'Location request timed out.';
            break;
    }
    status.removeClass().addClass('alert alert-danger').html(`<i class="fas fa-exclamation-circle me-2"></i>${message}`);
    updateStatusIndicator('error', 'Location error');
}

// Initialize map when document is ready
$(document).ready(function() {
    initMap();
    
    // Add CSS for pulse animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
});
</script>
</body>
</html>