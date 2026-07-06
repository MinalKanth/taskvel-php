<?php
require_once __DIR__ . '/../config/webhooks.php';

function post_webhook(string $url, array $body): void
{
    if ($url === '') return;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function notify_slack(string $text): void
{
    if (SLACK_WEBHOOK_URL === '') return;
    try { post_webhook(SLACK_WEBHOOK_URL, ['text' => $text]); } catch (Throwable $e) {}
}

function notify_teams(string $title, string $text): void
{
    if (TEAMS_WEBHOOK_URL === '') return;
    // MS Teams "Office 365 Connector" incoming webhook card format.
    try {
        post_webhook(TEAMS_WEBHOOK_URL, [
            '@type' => 'MessageCard',
            '@context' => 'https://schema.org/extensions',
            'summary' => $title,
            'title' => $title,
            'text' => $text,
        ]);
    } catch (Throwable $e) {}
}

// Convenience: fire both at once (whichever is configured; no-op otherwise).
function notify_chat(string $title, string $text): void
{
    notify_slack("*$title*\n$text");
    notify_teams($title, $text);
}
