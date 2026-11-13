<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RatingService;

// Public seller profiles. See docs/API_CONTRACT.md § Users.
class UserController extends Controller
{
    // GET /api/users/{user} — public: no phone/email (UserResource hides
    // them from anyone but the owner). Banned and deleted accounts → 404.
    public function show(User $user)
    {
        abort_if($user->is_banned, 404);

        $user->load(['governorate', 'city'])->loadCount([
            'ads as active_ads_count' => fn ($q) => $q->where('status', 'approved'),
            'ads as sold_ads_count' => fn ($q) => $q->where('status', 'sold'),
        ]);

        return new UserResource($user);
    }

    // GET /api/users/{user}/ratings/summary — counts per star for the bars.
    public function ratingsSummary(User $user, RatingService $ratings)
    {
        abort_if($user->is_banned, 404);

        return response()->json(['data' => $ratings->summary($user)]);
    }
}
