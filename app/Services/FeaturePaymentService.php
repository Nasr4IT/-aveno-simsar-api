<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdPackage;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\FeatureRequestApproved;
use App\Notifications\FeatureRequestRejected;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * "Feature my ad" paid manually through Sham Cash — shared by the API, the
 * website and the admin panel so all three follow the same rules:
 *
 *  1. request(): the seller of an approved ad picks a package, pays the
 *     package price in the Sham Cash app to the account shown (admin panel ▸
 *     Settings), and uploads a screenshot of the receipt. One pending request
 *     per ad at a time.
 *  2. approve(): an admin checks the receipt against Sham Cash; the ad is
 *     featured for the package's days (added on top of any featured time it
 *     still has left). The seller is notified.
 *  3. reject(): with a reason the seller sees. The seller is notified.
 *
 * Receipts are stored on the private "local" disk — never publicly
 * reachable; only the seller and admins can open them (see receiptResponse).
 */
class FeaturePaymentService
{
    public const RECEIPT_DISK = 'local';

    public function __construct(private ImageService $images, private PushNotificationService $push) {}

    public function request(User $seller, Ad $ad, AdPackage $package, UploadedFile $receipt, ?string $reference, ?string $note): Payment
    {
        $errors = match (true) {
            $ad->user_id !== $seller->id => ['ad' => __('يمكنك تمييز إعلاناتك فقط.')],
            $ad->status !== 'approved' => ['ad' => __('يمكن تمييز الإعلانات المنشورة فقط.')],
            ! $package->is_active => ['ad_package_id' => __('هذه الباقة غير متاحة حاليًا.')],
            $ad->hasPendingFeatureRequest() => ['ad' => __('لديك طلب تمييز قيد المراجعة لهذا الإعلان.')],
            default => null,
        };
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $path = $this->images->storeResized($receipt, "receipts/{$seller->id}", self::RECEIPT_DISK);

        return Payment::create([
            'user_id' => $seller->id,
            'ad_id' => $ad->id,
            'ad_package_id' => $package->id,
            'amount' => $package->price,
            'currency' => $package->currency,
            'provider' => 'shamcash_manual',
            'provider_reference' => $reference ?: null,
            'receipt_path' => $path,
            'note' => $note ?: null,
            'status' => Payment::PENDING,
        ]);
    }

    public function approve(Payment $payment, User $admin): void
    {
        $this->assertPending($payment);

        DB::transaction(function () use ($payment, $admin) {
            $ad = $payment->ad;
            $from = $ad->isFeaturedNow() ? $ad->featured_until : now();

            $ad->forceFill([
                'is_featured' => true,
                'featured_until' => $from->copy()->addDays($payment->adPackage->duration_days),
                'ad_package_id' => $payment->ad_package_id,
            ])->save();

            $payment->update([
                'status' => Payment::PAID,
                'paid_at' => now(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
        });

        $payment->refresh();
        $payment->user->notify(new FeatureRequestApproved($payment));
        // Push goes to the seller's phone — Arabic regardless of the admin's language.
        $this->push->sendToUser($payment->user, 'تم تمييز إعلانك', $payment->ad->title, ['type' => 'feature_approved', 'ad_id' => $payment->ad_id]);
    }

    public function reject(Payment $payment, User $admin, string $reason): void
    {
        $this->assertPending($payment);

        $payment->update([
            'status' => Payment::REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $payment->user->notify(new FeatureRequestRejected($payment));
        $this->push->sendToUser($payment->user, 'تم رفض طلب تمييز إعلانك', $reason, ['type' => 'feature_rejected', 'ad_id' => $payment->ad_id]);
    }

    /** The receipt image, for its seller or an admin only. */
    public function receiptResponse(Payment $payment, ?User $viewer)
    {
        $allowed = $viewer && ($viewer->id === $payment->user_id || $viewer->getRoleNames()->contains('admin'));
        abort_unless($allowed && $payment->receipt_path && Storage::disk(self::RECEIPT_DISK)->exists($payment->receipt_path), 404);

        return Storage::disk(self::RECEIPT_DISK)->response($payment->receipt_path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function assertPending(Payment $payment): void
    {
        if ($payment->status !== Payment::PENDING) {
            throw ValidationException::withMessages(['payment' => __('تمت مراجعة هذا الطلب مسبقًا.')]);
        }
    }
}
