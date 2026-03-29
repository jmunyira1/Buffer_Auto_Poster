<?php
function bufferQuery(string $query): array
{
    $ch = curl_init('https://api.buffer.com');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . BUFFER_TOKEN,
        ],
        CURLOPT_POSTFIELDS => json_encode(['query' => $query]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
function createPost(string $images_urls, string $caption = 'Free wallpapers, save & share! 📲
#wallpaper #phonebackground #iphonewallpaper #androidwallpaper #wallpaperdump #jmunyira1'): array
{
    $mutation = '
    mutation CreatePost {
      createPost(input: {
        text: ' . json_encode($caption) . ',
        channelId: "' . CHANNEL_ID . '",
        schedulingType: automatic,
        mode: shareNow,
        assets: {
          images: [' . $images_urls . ']
        }
      }) {
        ... on PostActionSuccess {
          post { id text }
        }
        ... on MutationError {
          message
        }
      }
    }';


    $result = bufferQuery($mutation);


    if (isset($result['errors'])) {
        logMessage('GraphQL errors: ' . print_r($result['errors'], true));
    }

    if (isset($result['data']['createPost']['message'])) {
        logMessage('MutationError: ' . $result['data']['createPost']['message']);
    }

    if (isset($result['data']['createPost']['post'])) {
        logMessage('Post created successfully. ID: ' . $result['data']['createPost']['post']['id']);
    }

    return $result ?? [];
}
function downloadImage(string $url): string|false
{
    $dir = __DIR__ . '/wallpapers/';

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        logMessage('Failed to create wallpapers directory');
        return false;
    }

    // ─── 1. Download image into memory via cURL ─────────────────────────
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 50,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_REFERER        => 'https://wallhaven.cc/',
    ]);

    $imageData = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    if ($error !== '' || $httpCode !== 200 || $imageData === false) {
        logMessage("Download failed (HTTP {$httpCode}, error: {$error}): {$url}");
        return false;
    }

    // ─── 2. Load into GD ────────────────────────────────────────────────
    $source = imagecreatefromstring($imageData);
    if ($source === false) {
        logMessage('GD could not decode image: ' . $url);
        return false;
    }

    $origW = imagesx($source);
    $origH = imagesy($source);
    logMessage("Downloaded {$origW}x{$origH}: {$url}");

    // ─── 3. Rotate landscape to portrait ────────────────────────────────
    // if wider than tall, rotate 90 degrees clockwise
    if ($origW > $origH) {
        $source = imagerotate($source, -90, 0);
        // swap dimensions after rotation
        [$origW, $origH] = [$origH, $origW];
        logMessage("Rotated landscape to portrait: now {$origW}x{$origH}");
    }

    // ─── 4. Downscale if larger than 1080x1920 ──────────────────────────
    $maxW  = 1080;
    $maxH  = 1920;

    // only resize if it actually exceeds the limits — never upscale
    if ($origW > $maxW || $origH > $maxH) {
        $ratio = min($maxW / $origW, $maxH / $origH);
        $newW  = (int)($origW * $ratio);
        $newH  = (int)($origH * $ratio);
        logMessage("Resizing from {$origW}x{$origH} to {$newW}x{$newH}");

        $resized = imagecreatetruecolor($newW, $newH);

        // preserve PNG transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        imagedestroy($source);
        $source = $resized;
        $origW  = $newW;
        $origH  = $newH;
    } else {
        logMessage("Image within limits ({$origW}x{$origH}), no resize needed");
    }

    // ─── 5. Save to disk ────────────────────────────────────────────────
    $ext      = str_ends_with(strtolower(parse_url($url, PHP_URL_PATH)), '.png') ? 'png' : 'jpg';
    $filename = uniqid('wh_', more_entropy: true) . '.' . $ext;
    $savePath = $dir . $filename;

    $saved = match($ext) {
        'png'   => imagepng($source, $savePath, 6),
        default => imagejpeg($source, $savePath, 88),
    };

    imagedestroy($source);

    if (!$saved) {
        logMessage('Failed to save image to: ' . $savePath);
        return false;
    }

    logMessage("Saved to: {$savePath}");
    return $filename; // return filename so caller can build the public URL
}

function logMessage(string $message): void
{
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . ' — ' . $message . PHP_EOL, FILE_APPEND);
}
function getPortraitsFromWallhaven(int $count = 6): array
{
    $params = [
        'categories' => '100',  // general + people, no anime
        'ratios'     => '9x16',
        'sorting'    => 'random',
        'purity'     => '100',
        'apikey'     => WALL_HAVEN_KEY,
    ];

    if (file_exists(SEED_FILE)) {
        $savedSeed = trim(file_get_contents(SEED_FILE));
        if (!empty($savedSeed)) {
            $params['seed'] = $savedSeed;
            logMessage('Using saved seed: ' . $savedSeed);
        }
    }

    $ch = curl_init('https://wallhaven.cc/api/v1/search?' . http_build_query($params));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $newSeed = $data['meta']['seed'] ?? null;
    if ($newSeed) {
        file_put_contents(SEED_FILE, $newSeed);
        logMessage('Saved new seed: ' . $newSeed);
    }

    $currentPage = $data['meta']['current_page'] ?? 1;
    $lastPage    = $data['meta']['last_page'] ?? 1;
    if ($currentPage >= $lastPage) {
        unlink(SEED_FILE);
        logMessage('Reached last page, seed reset for next run.');
    }

    // shuffle once, slice $count — guaranteed unique within the batch
    shuffle($data['data']);
    $selected = array_slice($data['data'], 0, $count);
    $images = [];
    foreach ($selected as $image) {
        $images[] = downloadImage($image['path']);
    }
return $images;}
function createUrls(array $images): string
{
    $parts = array_map(
        fn($image) => '{ url: ' . json_encode(BASE_URL . '/wallpapers/' . $image) . ' }',
        $images
    );
    return implode(', ', $parts);
}
function deleteImages() {
    $folder = 'wallpapers/';

    // Get a list of all files in the directory
    $files = glob($folder . '*');

    foreach($files as $file) {
        // Check if it is a file (and not a sub-directory)
        if(is_file($file)) {
            unlink($file);
        }
    }
}