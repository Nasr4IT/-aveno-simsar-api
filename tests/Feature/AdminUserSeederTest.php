<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_admin_gets_the_configured_password_and_follows_changes_to_it(): void
    {
        config(['admin.password' => 'first-long-secret']);
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('phone', AdminUserSeeder::PHONE)->sole();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('first-long-secret', $admin->password));

        config(['admin.password' => 'second-long-secret']);
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('second-long-secret', $admin->fresh()->password));
    }

    public function test_reseeding_with_the_same_password_does_not_rewrite_it(): void
    {
        config(['admin.password' => 'long-secret']);
        $this->seed(AdminUserSeeder::class);
        $hash = User::where('phone', AdminUserSeeder::PHONE)->value('password');

        $this->seed(AdminUserSeeder::class);

        $this->assertSame($hash, User::where('phone', AdminUserSeeder::PHONE)->value('password'));
    }

    public function test_without_a_configured_password_no_known_password_works(): void
    {
        config(['admin.password' => null]);
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('phone', AdminUserSeeder::PHONE)->sole();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse(Hash::check('change-me-now', $admin->password));
    }

    public function test_an_admin_still_on_the_old_public_default_is_moved_off_it(): void
    {
        User::factory()->create(['phone' => AdminUserSeeder::PHONE, 'password' => 'change-me-now']);

        config(['admin.password' => null]);
        $this->seed(AdminUserSeeder::class);

        $this->assertFalse(Hash::check('change-me-now', User::where('phone', AdminUserSeeder::PHONE)->value('password')));
    }

    public function test_a_deleted_seeded_admin_stays_deleted_instead_of_breaking_the_seeder(): void
    {
        User::factory()->create(['phone' => AdminUserSeeder::PHONE])->delete();

        config(['admin.password' => 'long-secret']);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::where('phone', AdminUserSeeder::PHONE)->count());
        $this->assertSame(1, User::withTrashed()->where('phone', AdminUserSeeder::PHONE)->count());
    }

    public function test_an_admin_password_set_some_other_way_is_left_alone_when_none_is_configured(): void
    {
        User::factory()->create(['phone' => AdminUserSeeder::PHONE, 'password' => 'chosen-by-hand']);

        config(['admin.password' => null]);
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(Hash::check('chosen-by-hand', User::where('phone', AdminUserSeeder::PHONE)->value('password')));
    }
}
