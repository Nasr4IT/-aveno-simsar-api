<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\NewRatingReceived;
use App\Services\PushNotificationService;
use App\Services\RatingService;
use Illuminate\Http\Request;

// Mirrors Api\RatingController@store: one rating per (rater, rated, ad).
class RatingController extends Controller
{
    public function store(Request $request, User $user, RatingService $ratingService, PushNotificationService $push)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['score' => __('لا يمكنك تقييم نفسك.')]);
        }

        $data = $request->validate([
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

        return back()->with('status', __('شكرًا لتقييمك.'));
    }
}
