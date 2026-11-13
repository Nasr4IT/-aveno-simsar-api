<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;

// A seller's public profile: what a buyer sees about the person behind an
// ad — name, rating, their live listings, and the ratings others left.
class UserController extends Controller
{
    public function show(User $user)
    {
        return view('site.users.show', [
            'user' => $user,
            'ads' => $user->ads()->approved()->with(['images', 'city'])->latest()->paginate(12, ['*'], 'ads'),
            'ratings' => $user->ratingsReceived()->with('raterUser')->latest()->paginate(10, ['*'], 'ratings'),
        ]);
    }
}
