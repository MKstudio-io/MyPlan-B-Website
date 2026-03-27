<?php
// Nominatim geocoding proxy for place autocomplete

header('Content-Type: application/json; charset=utf-8');

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$url = 'https://nominatim.openstreetmap.org/search?'
     . http_build_query([
         'q'              => $q,
         'format'         => 'json',
         'limit'          => 5,
         'addressdetails' => 1,
     ]);

$context = stream_context_create([
    'http' => [
        'header' => implode("\r\n", [
            'User-Agent: myplanb-questionnaire/1.0 (fragebogen@myplanb.at)',
            'Accept-Language: de',
        ]),
        'timeout' => 5,
    ],
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    // Fallback to curl if file_get_contents is disabled for URLs
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: myplanb-questionnaire/1.0 (fragebogen@myplanb.at)',
                'Accept-Language: de',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    if ($response === false) {
        echo json_encode([]);
        exit;
    }
}

$data = json_decode($response, true);

if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

$results = array_map(function ($item) {
    return [
        'display_name' => $item['display_name'] ?? '',
        'lat'          => $item['lat'] ?? '',
        'lon'          => $item['lon'] ?? '',
    ];
}, $data);

echo json_encode($results);
