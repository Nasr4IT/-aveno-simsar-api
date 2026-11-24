<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_privacy_policy_is_public_in_arabic_and_english(): void
    {
        $this->get('/privacy')->assertOk()->assertSee('سياسة الخصوصية')->assertSee('Firebase Cloud Messaging');
        $this->get('/privacy?lang=en')->assertOk()->assertSee('Privacy policy')->assertSee('We do not sell your data');
    }

    public function test_it_shows_the_contact_details_the_admin_set(): void
    {
        $this->get('/privacy')->assertOk()->assertDontSee('mailto:');

        Setting::put(['contact_email' => 'privacy@simsar-sy.com']);
        $this->get('/privacy')->assertOk()->assertSee('mailto:privacy@simsar-sy.com', false);
    }

    public function test_it_is_linked_from_the_footer_and_the_sign_up_page(): void
    {
        $this->get('/')->assertSee(route('site.privacy'), false);
        $this->get('/register')->assertSee(route('site.privacy'), false);
    }
}
