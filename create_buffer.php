<?php
include ('functions.php');
include ('envReader.php');
loadEnv();
$api_key        = env('API_KEY');
$channel_id     = env('CHANNEL_ID');
$wall_haven_key = env('WALL_HAVEN_KEY');
$base_url       = env('BASE_URL');


define('BUFFER_TOKEN',   $api_key);
define('CHANNEL_ID',     $channel_id);
define('WALL_HAVEN_KEY', $wall_haven_key);
define('BASE_URL',        $base_url);
logMessage('Token being used: ' . BUFFER_TOKEN); // add this

const SEED_FILE = __DIR__ . '/wallhaven_seed.txt';
const LOG_FILE  = __DIR__ . '/buffer_log.txt';


$images = getPortraitsFromWallhaven(6);


$image_s = createUrls($images);

createPost($image_s);


deleteImages();