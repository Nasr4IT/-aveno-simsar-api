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

    // Average, total and how many ratings had each score — the 1–5 bars on
    // a seller's ratings screen (API /users/{id}/ratings/summary and the
    // website's seller page).
    public function summary(User $user): array
    {
        $byScore = $user->ratingsReceived()
            ->selectRaw('score, count(*) as total')
            ->groupBy('score')
            ->pluck('total', 'score');

        $stars = [];
        foreach ([5, 4, 3, 2, 1] as $score) {
            $stars[(string) $score] = (int) ($byScore[$score] ?? 0);
        }

        return [
            'average' => (float) $user->rating_average,
            'count' => (int) $user->rating_count,
            'stars' => $stars,
        ];
    }
}
