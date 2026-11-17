<?php

namespace Tests\Feature;

use App\Services\PushNotificationService;
use Tests\TestCase;

// Server-setup conveniences raised by the Flutter team on 2026-11-18.
class ServerConfigTest extends TestCase
{
    private function credentials(): ?array
    {
        $method = new \ReflectionMethod(PushNotificationService::class, 'credentials');

        return $method->invoke(app(PushNotificationService::class));
    }

    public function test_firebase_credentials_can_be_read_from_an_uploaded_file(): void
    {
        $file = storage_path('framework/testing-firebase.json');
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, json_encode(['project_id' => 'simsar-test', 'client_email' => 'a@b.iam.gserviceaccount.com', 'private_key' => 'KEY']));

        config(['services.firebase.credentials_json' => null, 'services.firebase.credentials_path' => 'storage/framework/testing-firebase.json']);
        $this->assertSame('simsar-test', $this->credentials()['project_id']);

        config(['services.firebase.credentials_path' => $file]);
        $this->assertSame('simsar-test', $this->credentials()['project_id'], 'absolute path');

        @unlink($file);
    }

    public function test_missing_or_broken_firebase_credentials_disable_push_quietly(): void
    {
        config(['services.firebase.credentials_json' => null, 'services.firebase.credentials_path' => 'storage/does-not-exist.json']);
        $this->assertNull($this->credentials());

        config(['services.firebase.credentials_json' => '{"not":"a service account"}']);
        $this->assertNull($this->credentials());
    }

    // The app is Arabic-only: the top-level 422 "message" must not end in
    // English "(and 2 more errors)".
    public function test_the_validation_summary_message_is_fully_arabic(): void
    {
        $message = $this->postJson('/api/auth/register', [])->assertStatus(422)->json('message');

        $this->assertStringNotContainsString('more error', $message);
        $this->assertStringContainsString('أخطاء أخرى', $message);
    }
}
