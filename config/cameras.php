<?php

return [
    'go2rtc_url'        => env('GO2RTC_URL', 'http://localhost:1984'),
    'go2rtc_public_url' => env('GO2RTC_PUBLIC_URL', env('GO2RTC_URL', 'http://localhost:1984')),
    'go2rtc_yaml_path'  => env('GO2RTC_YAML_PATH', ''),
    'ffmpeg_path'       => env('FFMPEG_PATH', 'ffmpeg'),

    // Alertas de câmera
    'alert_email'    => env('CAMERAS_ALERT_EMAIL', ''),
    'webhook_token'  => env('CAMERAS_WEBHOOK_TOKEN', ''),

    // Evolution API (WhatsApp)
    'evolution_api_url'      => env('EVOLUTION_API_URL', ''),
    'evolution_api_instance' => env('EVOLUTION_API_INSTANCE', ''),
    'evolution_api_key'      => env('EVOLUTION_API_KEY', ''),
];
