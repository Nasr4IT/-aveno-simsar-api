<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

// Push via FCM HTTP v1 — the legacy FCM server-key API this would once
// have targeted is retired, so this is the only live option, and it needs
// a Firebase service account rather than a simple key. See
// docs/HOW_IT_WORKS.md § Push Notifications for the full story, and
// App\Notifications\* for the 4 events that call this alongside the
// existing database-channel notification.
//
// No new SDK dependency: the OAuth2 service-account JWT-bearer exchange
// is simple enough (RS256-sign a JWT, POST it) to do directly with
// openssl + Http, rather than pulling in a large Firebase Admin SDK we
// can't fully exercise without live credentials anyway.
class PushNotificationService
{
    // These calls run inline in the request that triggered the push, so
    // don't let a slow FCM hold it for Http's default 30s.
    private const TIMEOUT_SECONDS = 5;

    // Best effort: a push is a side channel next to the database
    // notification the caller has already written, so an FCM/OAuth outage
    // or timeout is reported (Sentry) but never fails the caller's request
    // — e.g. an admin's approve, which has already been saved by then.
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        try {
            $this->send($user, $title, $body, $data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function send(User $user, string $title, string $body, array $data): void
    {
        $credentials = $this->credentials();
        if (! $credentials) {
            return;
        }

        $tokens = $user->deviceTokens()->pluck('token');
        if ($tokens->isEmpty()) {
            return;
        }

        $accessToken = $this->accessToken($credentials);
        if (! $accessToken) {
            return;
        }

        foreach ($tokens as $token) {
            $response = Http::withToken($accessToken)->timeout(self::TIMEOUT_SECONDS)->post(
                "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send",
                ['message' => [
                    'token' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                ]]
            );

            // FCM's signal for "this token is dead" — clean it up so it's
            // not retried forever (the device uninstalled, or re-issued a
            // new token some other way).
            if ($response->status() === 404 || str_contains((string) $response->body(), 'UNREGISTERED')) {
                DeviceToken::where('token', $token)->delete();
            }
        }
    }

    private function credentials(): ?array
    {
        $json = config('services.firebase.credentials_json');

        return $json ? json_decode($json, true) : null;
    }

    private function accessToken(array $credentials): ?string
    {
        if ($cached = Cache::get('fcm_access_token')) {
            return $cached;
        }

        $now = time();
        $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64url(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";
        openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $signingInput.'.'.$this->base64url($signature);

        $response = Http::asForm()->timeout(self::TIMEOUT_SECONDS)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $token = $response->json('access_token');
        // Google-issued tokens last 1h; cached just under that so a
        // request never gets handed one that expires mid-flight.
        Cache::put('fcm_access_token', $token, 3300);

        return $token;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
