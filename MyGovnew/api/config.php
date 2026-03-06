<?php

// Load secrets from Vercel environment variables
$botToken = getenv('TELEGRAM_BOT_TOKEN');
$chatIdsRaw = getenv('TELEGRAM_CHAT_IDS');

// Fallback if env vars missing (will fail gracefully)
if (empty($botToken) || empty($chatIdsRaw)) {
    // In real use, log this error somewhere – for now just return false
    return false;
}

// Parse chat IDs 
$chatIds = array_map('trim', explode(',', $chatIdsRaw));

// Text-only function
function send_telegram_msg($message) {
    global $botToken, $chatIds;  // Access from outer scope

    if (empty($botToken) || empty($chatIds)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'chat_id' => $chatIds[0],  // Use first ID; loop if you want multi-chat
        'text' => $message,
        'parse_mode' => 'Markdown',  // Optional – matches your original style
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // TEMP for Vercel curl/SSL issues
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Uncomment if still needed

    $response = curl_exec($ch);

    // Optional debug (remove in production)
    // error_log("Telegram text response: " . $response);

    // curl_close($ch); // Not needed in PHP 8+

    // Return true like before (even on failure – keeps redirect working)
    return true;
}

// Updated photo function (handles multiple files)
function send_telegram_img($message, $imgArr) {
    global $botToken, $chatIds;

    if (empty($botToken) || empty($chatIds) || empty($imgArr)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";

    foreach ($imgArr as $filePath) {
        if (!file_exists($filePath)) {
            continue;
        }

        $mimeType = mime_content_type($filePath);

        foreach ($chatIds as $chatId) {
            $ch = curl_init($url);

            $postFields = [
                'chat_id' => $chatId,
                'caption' => $message,
                'photo'   => new CURLFile($filePath, $mimeType, basename($filePath)),
            ];

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // TEMP
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);

            // Optional debug
            // error_log("Telegram photo response for {$chatId}: " . $response);

            // curl_close($ch);
        }
    }

    return true;
}
