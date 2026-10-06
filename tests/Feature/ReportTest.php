<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use InteractsWithAdmin, RefreshDatabase;

    public function test_a_user_can_report_an_ad(): void
    {
        $seller = User::factory()->create();
        $reporter = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        Sanctum::actingAs($reporter);
        $this->postJson("/api/ads/{$ad->id}/report", ['reason' => 'scam', 'details' => 'Asked for payment outside the app'])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Ad::class,
            'reportable_id' => $ad->id,
            'reason' => 'scam',
            'status' => 'pending',
        ]);
    }

    public function test_a_user_cannot_report_their_own_ad(): void
    {
        $seller = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

        Sanctum::actingAs($seller);
        $this->postJson("/api/ads/{$ad->id}/report", ['reason' => 'spam'])->assertStatus(422);
    }

    public function test_a_pending_ad_cannot_be_reported(): void
    {
        $seller = User::factory()->create();
        $reporter = User::factory()->create();
        $ad = Ad::factory()->create(['user_id' => $seller->id, 'status' => 'pending']);

        Sanctum::actingAs($reporter);
        $this->postJson("/api/ads/{$ad->id}/report", ['reason' => 'spam'])->assertNotFound();
    }

    public function test_a_user_can_report_another_user(): void
    {
        $reported = User::factory()->create();
        $reporter = User::factory()->create();

        Sanctum::actingAs($reporter);
        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'inappropriate'])->assertOk();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => User::class,
            'reportable_id' => $reported->id,
        ]);
    }

    public function test_a_user_cannot_report_themselves(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson("/api/users/{$user->id}/report", ['reason' => 'spam'])->assertStatus(422);
    }

    public function test_reason_must_be_one_of_the_known_values(): void
    {
        $reported = User::factory()->create();
        $reporter = User::factory()->create();

        Sanctum::actingAs($reporter);
        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'because I feel like it'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
    }

    public function test_reporting_the_same_target_again_reopens_it_instead_of_duplicating(): void
    {
        $reported = User::factory()->create();
        $reporter = User::factory()->create();

        Sanctum::actingAs($reporter);
        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'spam'])->assertOk();

        $report = Report::sole();
        $report->update(['status' => 'dismissed']);

        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'scam', 'details' => 'escalating'])->assertOk();

        $this->assertDatabaseCount('reports', 1);
        $this->assertSame('pending', $report->fresh()->status);
        $this->assertSame('scam', $report->fresh()->reason);
    }

    public function test_non_admin_cannot_access_report_management(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/reports')->assertForbidden();
    }

    public function test_admin_can_list_and_resolve_a_report(): void
    {
        // adminUser() must run (incl. assignRole, which resolves against
        // Spatie's *default* guard) before any Sanctum::actingAs() call —
        // actingAs() switches the default guard to 'sanctum', which would
        // make assignRole() look for the role under the wrong guard. See
        // the same note on AdminUserTest.
        $admin = $this->adminUser();
        $reported = User::factory()->create();
        $reporter = User::factory()->create();

        Sanctum::actingAs($reporter);
        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'spam'])->assertOk();
        $report = Report::sole();

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/reports?status=pending')->assertOk()->assertJsonCount(1, 'data');

        $this->postJson("/api/admin/reports/{$report->id}/resolve")
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');
    }

    public function test_admin_can_dismiss_a_report(): void
    {
        $admin = $this->adminUser();
        $reported = User::factory()->create();
        $reporter = User::factory()->create();

        Sanctum::actingAs($reporter);
        $this->postJson("/api/users/{$reported->id}/report", ['reason' => 'spam'])->assertOk();
        $report = Report::sole();

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/reports/{$report->id}/dismiss")
            ->assertOk()
            ->assertJsonPath('data.status', 'dismissed');
    }
}
