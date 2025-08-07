<?php
/**
 * Business Lookup API Endpoint
 * Provides business auto-complete and details lookup
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = new Database();
$pdo = $db->connect();

$method = $_SERVER['REQUEST_METHOD'];
$query = $_GET['q'] ?? '';
$action = $_GET['action'] ?? 'search';

if ($method !== 'GET' || empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

/**
 * Mock business data for demonstration
 * In production, this would connect to real business APIs
 */
function getMockBusinessData($query) {
    $businesses = [
        [
            'name' => 'Ford Motor Company',
            'address' => '1 American Rd',
            'city' => 'Dearborn',
            'state' => 'MI',
            'zip' => '48126',
            'country' => 'USA',
            'phone' => '(313) 322-3000',
            'website' => 'https://www.ford.com',
            'industry' => 'Automotive'
        ],
        [
            'name' => 'General Motors Company',
            'address' => '300 Renaissance Center',
            'city' => 'Detroit',
            'state' => 'MI',
            'zip' => '48243',
            'country' => 'USA',
            'phone' => '(313) 665-5000',
            'website' => 'https://www.gm.com',
            'industry' => 'Automotive'
        ],
        [
            'name' => 'Apple Inc.',
            'address' => '1 Apple Park Way',
            'city' => 'Cupertino',
            'state' => 'CA',
            'zip' => '95014',
            'country' => 'USA',
            'phone' => '(408) 996-1010',
            'website' => 'https://www.apple.com',
            'industry' => 'Technology'
        ],
        [
            'name' => 'Microsoft Corporation',
            'address' => '1 Microsoft Way',
            'city' => 'Redmond',
            'state' => 'WA',
            'zip' => '98052',
            'country' => 'USA',
            'phone' => '(425) 882-8080',
            'website' => 'https://www.microsoft.com',
            'industry' => 'Technology'
        ],
        [
            'name' => 'Amazon.com, Inc.',
            'address' => '410 Terry Ave N',
            'city' => 'Seattle',
            'state' => 'WA',
            'zip' => '98109',
            'country' => 'USA',
            'phone' => '(206) 266-1000',
            'website' => 'https://www.amazon.com',
            'industry' => 'E-commerce'
        ],
        [
            'name' => 'Toyota Motor Corporation',
            'address' => '1 Toyota Way',
            'city' => 'Georgetown',
            'state' => 'KY',
            'zip' => '40324',
            'country' => 'USA',
            'phone' => '(502) 868-3000',
            'website' => 'https://www.toyota.com',
            'industry' => 'Automotive'
        ],
        [
            'name' => 'Boeing Company',
            'address' => '100 N Riverside Plaza',
            'city' => 'Chicago',
            'state' => 'IL',
            'zip' => '60606',
            'country' => 'USA',
            'phone' => '(312) 544-2000',
            'website' => 'https://www.boeing.com',
            'industry' => 'Aerospace'
        ],
        [
            'name' => '3M Company',
            'address' => '2501 Hudson Rd',
            'city' => 'St Paul',
            'state' => 'MN',
            'zip' => '55144',
            'country' => 'USA',
            'phone' => '(651) 733-1110',
            'website' => 'https://www.3m.com',
            'industry' => 'Manufacturing'
        ]
    ];
    
    $filtered = array_filter($businesses, function($business) use ($query) {
        $searchIn = strtolower($business['name']);
        return strpos($searchIn, strtolower($query)) !== false;
    });
    
    return array_values($filtered);
}

/**
 * Google Places API integration (requires API key)
 */
function searchGooglePlaces($query) {
    $api_key = 'YOUR_GOOGLE_PLACES_API_KEY'; // Set in config
    
    if (empty($api_key) || $api_key === 'YOUR_GOOGLE_PLACES_API_KEY') {
        return [];
    }
    
    $url = "https://maps.googleapis.com/maps/api/place/textsearch/json";
    $params = [
        'query' => $query,
        'type' => 'establishment',
        'key' => $api_key
    ];
    
    $url .= '?' . http_build_query($params);
    
    $response = file_get_contents($url);
    if ($response === false) {
        return [];
    }
    
    $data = json_decode($response, true);
    if (!isset($data['results'])) {
        return [];
    }
    
    $results = [];
    foreach ($data['results'] as $place) {
        $results[] = [
            'name' => $place['name'],
            'address' => $place['formatted_address'] ?? '',
            'rating' => $place['rating'] ?? 0,
            'place_id' => $place['place_id']
        ];
    }
    
    return $results;
}

/**
 * Get detailed business information
 */
function getBusinessDetails($placeId) {
    $api_key = 'YOUR_GOOGLE_PLACES_API_KEY';
    
    if (empty($api_key) || $api_key === 'YOUR_GOOGLE_PLACES_API_KEY') {
        return null;
    }
    
    $url = "https://maps.googleapis.com/maps/api/place/details/json";
    $params = [
        'place_id' => $placeId,
        'fields' => 'name,formatted_address,formatted_phone_number,website,rating,address_components',
        'key' => $api_key
    ];
    
    $url .= '?' . http_build_query($params);
    
    $response = file_get_contents($url);
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['result'])) {
        return null;
    }
    
    $result = $data['result'];
    $address_components = $result['address_components'] ?? [];
    
    // Parse address components
    $parsed_address = [
        'street_number' => '',
        'route' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'country' => ''
    ];
    
    foreach ($address_components as $component) {
        $types = $component['types'];
        if (in_array('street_number', $types)) {
            $parsed_address['street_number'] = $component['long_name'];
        } elseif (in_array('route', $types)) {
            $parsed_address['route'] = $component['long_name'];
        } elseif (in_array('locality', $types)) {
            $parsed_address['city'] = $component['long_name'];
        } elseif (in_array('administrative_area_level_1', $types)) {
            $parsed_address['state'] = $component['short_name'];
        } elseif (in_array('postal_code', $types)) {
            $parsed_address['zip'] = $component['long_name'];
        } elseif (in_array('country', $types)) {
            $parsed_address['country'] = $component['short_name'];
        }
    }
    
    return [
        'name' => $result['name'],
        'address_line1' => trim($parsed_address['street_number'] . ' ' . $parsed_address['route']),
        'city' => $parsed_address['city'],
        'state' => $parsed_address['state'],
        'zip_code' => $parsed_address['zip'],
        'country' => $parsed_address['country'],
        'phone' => $result['formatted_phone_number'] ?? '',
        'website' => $result['website'] ?? '',
        'rating' => $result['rating'] ?? 0
    ];
}

/**
 * Check existing customers to avoid duplicates
 */
function checkExistingCustomer($name) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT customer_code, customer_name FROM customers WHERE customer_name LIKE ?");
        $stmt->execute(["%$name%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Handle different actions
switch ($action) {
    case 'search':
        // Search for businesses
        $results = getMockBusinessData($query);
        
        // Add Google Places results if API key is configured
        $google_results = searchGooglePlaces($query);
        $results = array_merge($results, $google_results);
        
        // Check for existing customers
        $existing = checkExistingCustomer($query);
        
        echo json_encode([
            'suggestions' => $results,
            'existing_customers' => $existing
        ]);
        break;
        
    case 'details':
        // Get detailed information for a specific business
        $placeId = $_GET['place_id'] ?? '';
        if ($placeId) {
            $details = getBusinessDetails($placeId);
            echo json_encode($details);
        } else {
            echo json_encode(['error' => 'Missing place_id']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>