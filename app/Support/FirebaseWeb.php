<?php

namespace App\Support;

/** The public Firebase web settings the browser needs for phone OTP. Null until the project is configured. */
class FirebaseWeb
{
    public static function config(): ?array
    {
        $projectId = config('firebase.project_id');
        $apiKey = config('firebase.api_key');
        $appId = config('firebase.app_id');

        if (! filled($projectId) || ! filled($apiKey) || ! filled($appId)) {
            return null;
        }

        return array_filter([
            'apiKey' => $apiKey,
            'authDomain' => config('firebase.auth_domain') ?: $projectId . '.firebaseapp.com',
            'projectId' => $projectId,
            'appId' => $appId,
            'messagingSenderId' => config('firebase.messaging_sender_id'),
        ], fn ($v) => filled($v));
    }
}
