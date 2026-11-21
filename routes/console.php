<?php

use App\Models\User;
use App\Services\PushNotificationService;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checks the Firebase (push notification) setup without sending anything.
Artisan::command('push:check', function (PushNotificationService $push) {
    [$ok, $message] = $push->checkSetup();
    $ok ? $this->info($message) : $this->error($message);

    return $ok ? 0 : 1;
})->purpose('Check that the Firebase key for push notifications works');

// Give / take away admin-panel access for an existing account, by phone
// number. Lets a tester (e.g. the app developer) approve and reject their own
// test ads with their own account — no admin password is ever shared — and
// be removed again after testing. The account must already exist (register
// in the app or on the website first).
Artisan::command('admin:grant {phone}', function (string $phone) {
    $user = User::where('phone', PhoneNumber::normalize($phone))->first();
    if (! $user) {
        $this->error("No account with phone {$phone}. Register it in the app or on the website first.");

        return 1;
    }

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole('admin');
    $this->info("{$user->name} ({$user->phone}) can now use the admin panel: /admin");

    return 0;
})->purpose('Give an existing account access to the admin panel');

Artisan::command('admin:revoke {phone}', function (string $phone) {
    $user = User::where('phone', PhoneNumber::normalize($phone))->first();
    if (! $user || ! $user->hasRole('admin')) {
        $this->error("No admin account with phone {$phone}.");

        return 1;
    }

    if (User::role('admin')->count() === 1) {
        $this->error('This is the last admin account — refusing, or nobody could reach the admin panel.');

        return 1;
    }

    $user->removeRole('admin');
    $this->info("{$user->name} ({$user->phone}) no longer has admin access.");

    return 0;
})->purpose('Remove admin-panel access from an account');

Artisan::command('admin:list', function () {
    $this->table(['Name', 'Phone'], User::role('admin')->get(['name', 'phone'])->toArray());
})->purpose('List the accounts that can use the admin panel');

// TODO (Phase 2): schedule()->call() here to auto-expire ads whose
// featured_until has passed (proposal §9 "الخدمات المستقبلية").
