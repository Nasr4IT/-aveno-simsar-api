<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RatingService;

// A seller's public profile: what a buyer sees about the person behind an
// ad — name, rating (with the per-star breakdown), their live listings, and
// the ratings others left. Same rules as the API's GET /users/{id}.
class UserController extends Controller
{
    public function show(User $user, RatingService $ratings)
    {
        abort_if($user->is_banned, 404);

        return view('site.users.show', [
            'user' => $user,
            'summary' => $ratings->summary($user),
            'soldCount' => $user->ads()->where('status', 'sold')->count(),
            'ads' => $user->ads()->approved()->with(['images', 'city'])->latest()->paginate(12, ['*'], 'ads'),
            'ratings' => $user->ratingsReceived()->with('raterUser')->latest()->paginate(10, ['*'], 'ratings'),
        ]);
    }
}
