<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithAdmin
{
    protected function adminUser(): User
    {
        // Spatie caches role/permission lookups across requests. With
        // RefreshDatabase recreating tables between tests, a cache warmed by
        // an earlier test can go stale and make hasRole('admin') incorrectly
        // return false for a role/user created fresh in this one —
        // intermittently, depending on suite run order. Force a fresh read.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
