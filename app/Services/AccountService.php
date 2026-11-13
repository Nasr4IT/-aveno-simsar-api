<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Account self-service shared by the API (AuthController) and the website
 * (Site\ProfileController): profile update with avatar, and account
 * deletion. Keeping it in one place is what keeps the app and the site
 * behaving identically.
 */
class AccountService
{
    public const DELETED_NAME = 'مستخدم محذوف';

    public function __construct(private ImageService $images) {}

    /** Applies validated profile fields; replaces or removes the avatar. */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar, bool $removeAvatar = false): User
    {
        $user->fill(collect($data)->only(['name', 'email', 'governorate_id', 'city_id'])->all());

        if ($avatar || $removeAvatar) {
            $this->deleteAvatarFile($user);
            $user->avatar_path = $avatar ? $this->images->storeResized($avatar, "avatars/{$user->id}") : null;
        }

        $user->save();

        return $user->load(['governorate', 'city']);
    }

    /**
     * Deletes the account as the app stores require, without breaking other
     * people's history:
     *  - the user's ads (and so their public listings), favorites, ratings
     *    they gave and received, device tokens, notifications and API tokens
     *    are removed; ratings they gave are taken out of the sellers' averages;
     *  - conversations stay, so the other person keeps the chat, which now
     *    shows the sender as "مستخدم محذوف" (Deleted user);
     *  - the user row is anonymised (name, phone, email, password, avatar,
     *    location wiped) and then soft-deleted, which also frees the phone
     *    number and email for a future sign-up.
     */
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $ratedUserIds = $user->ratingsGiven()->pluck('rated_user_id')->unique();

            $user->ads()->each(fn ($ad) => $ad->delete());
            $user->favorites()->delete();
            Rating::where('rater_user_id', $user->id)->orWhere('rated_user_id', $user->id)->delete();
            $user->deviceTokens()->delete();
            $user->notifications()->delete();
            $user->tokens()->delete();
            $user->roles()->detach();

            $this->deleteAvatarFile($user);

            $user->forceFill([
                'name' => self::DELETED_NAME,
                'phone' => 'deleted-'.$user->id.'-'.Str::random(8),
                'email' => null,
                'password' => Str::random(64),
                'avatar_path' => null,
                'governorate_id' => null,
                'city_id' => null,
                'rating_average' => 0,
                'rating_count' => 0,
                'remember_token' => null,
            ])->save();

            $user->delete();

            $ratings = app(RatingService::class);
            User::whereIn('id', $ratedUserIds)->get()->each(fn (User $rated) => $ratings->recomputeForUser($rated));
        });
    }

    private function deleteAvatarFile(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
        }
    }
}
