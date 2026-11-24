<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

// Static pages. The privacy policy lives on the website and the app links
// to it (GET /api/app-config → privacy_policy_url) — one policy for both,
// and the public URL Google Play / the App Store require.
class PageController extends Controller
{
    // Bump when the policy text changes (resources/views/site/legal/).
    public const PRIVACY_UPDATED = '2026-11-24';

    public function privacy()
    {
        return view('site.legal.privacy');
    }
}
