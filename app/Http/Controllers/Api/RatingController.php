<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RatingResource;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\NewRatingReceived;
use App\Services\PushNotificationService;
use App\Services\RatingService;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Ratings.
class RatingController extends Controller
{
    // GET /api/users/{user}/ratings — public, shown on a seller's profile.
    public function index(User $user)
    {
        return RatingResource::collection($user->ratingsReceived()->with('raterUser')->latest()->paginate(20));
    }

    // POST /api/users/{user}/ratings — one rating per (rater, rated, ad) triple.
    public function store(Request $request, User $user, RatingService $ratingService, PushNotificationService $push)
    {
        abort_if($user->id === $request->user()->id, 422, 'لا يمكنك تقييم نفسك');

        $data = $request->validate([
            // No proof of an actual transaction/conversation is required —
            // a rating can reference any ad the rater could have organically
            // seen (home feed or search), which by definition means approved.
            'ad_id' => ['nullable', 'exists:ads,id,status,approved'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $rating = Rating::updateOrCreate(
            ['rater_user_id' => $request->user()->id, 'rated_user_id' => $user->id, 'ad_id' => $data['ad_id'] ?? null],
            ['score' => $data['score'], 'comment' => $data['comment'] ?? null]
        );

        $ratingService->recomputeForUser($user);

        $user->notify(new NewRatingReceived($rating));
        $push->sendToUser($user, 'تقييم جديد', "قيّمك {$request->user()->name} بـ {$data['score']} نجوم", ['type' => 'new_rating']);

        return new RatingResource($rating);
    }
}
