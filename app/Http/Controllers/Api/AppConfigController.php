<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\PageController;
use App\Models\Setting;

// GET /api/app-config — small public settings the app needs: where the
// privacy policy is (show it in the app, link it on sign-up), the contact
// details, and the Sham Cash account for featured-ad payments. All edited
// by the admin (panel ▸ Settings) — no app release needed to change them.
class AppConfigController extends Controller
{
    public function __invoke()
    {
        return response()->json(['data' => [
            'privacy_policy_url' => route('site.privacy'),
            'privacy_policy_url_en' => route('site.privacy', ['lang' => 'en']),
            'privacy_policy_updated_at' => PageController::PRIVACY_UPDATED,
            'contact_email' => Setting::get('contact_email'),
            'contact_phone' => Setting::get('contact_phone'),
            'shamcash' => Setting::shamCash(),
        ]]);
    }
}
