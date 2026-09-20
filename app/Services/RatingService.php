<?php

namespace App\Services;

use App\Models\User;

class RatingService
{
    // Recomputes and persists the denormalized rating_average/rating_count
    // on $user from their ratingsReceived rows. Called after every write to
    // the ratings table (see RatingController@store).
    public function recomputeForUser(User $user): void
    {
        $stats = $user->ratingsReceived()->selectRaw('avg(score) as avg_score, count(*) as total')->first();

        $user->forceFill([
            'rating_average' => $stats->total > 0 ? round($stats->avg_score, 2) : 0,
            'rating_count' => $stats->total,
        ])->save();
    }
}
