<?php

namespace App\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Sending;

class CaptureReplyMiddleware implements Sending
{
    public static array $captured = [];

    public static function reset(): void
    {
        self::$captured = [];
    }

    public function sending($payload, $next, BotMan $bot)
{
    $text = null;

    if (is_object($payload) && method_exists($payload, 'getText')) {
        $text = $payload->getText();
    } elseif (is_string($payload)) {
        $text = $payload;
    } elseif (is_array($payload)) {
        if (isset($payload['message']) && is_object($payload['message']) && method_exists($payload['message'], 'getText')) {
            // Handles Question objects (at katulad nito) na naka-nest sa 'message'
            $text = $payload['message']->getText();
        } elseif (isset($payload['message']) && is_array($payload['message']) && isset($payload['message']['text'])) {
            $text = $payload['message']['text'];
        } elseif (isset($payload['text'])) {
            $text = $payload['text'];
        } elseif (isset($payload['message']) && is_string($payload['message'])) {
            $text = $payload['message'];
        }
    } else {
        $text = is_scalar($payload) ? (string) $payload : null;
    }

    if ($text !== null && $text !== '') {
        self::$captured[] = $text;
    }

    return $next($payload);
}
}