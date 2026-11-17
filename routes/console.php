<?php

use App\Services\PushNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checks the Firebase (push notification) setup without sending anything.
Artisan::command('push:check', function (PushNotificationService $push) {
    [$ok, $message] = $push->checkSetup();
    $ok ? $this->info($message) : $this->error($message);

    return $ok ? 0 : 1;
})->purpose('Check that the Firebase key for push notifications works');

// TODO (Phase 2): schedule()->call() here to auto-expire ads whose
// featured_until has passed (proposal §9 "الخدمات المستقبلية").
