<?php
include("envReader.php");
loadEnv('.env.example');
$api_key = env('API_KEY');

function bufferQuery( $q,$api_key): array
{
    $ch = curl_init('https://api.buffer.com');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_POSTFIELDS => json_encode(['query' => $q]),
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}

$result = bufferQuery('query { account { channels { id name service } } }', $api_key);
print_r($result);