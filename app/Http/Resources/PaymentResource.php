<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// A "feature my ad" request paid manually through Sham Cash.
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status, // pending | paid | rejected
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'transaction_reference' => $this->provider_reference,
            'note' => $this->note,
            'rejection_reason' => $this->rejection_reason,
            'receipt_url' => $this->receipt_path ? url("/api/payments/{$this->id}/receipt") : null,
            'package' => $this->whenLoaded('adPackage', fn () => [
                'id' => $this->adPackage->id,
                'name_ar' => $this->adPackage->name_ar,
                'name_en' => $this->adPackage->name_en,
                'duration_days' => $this->adPackage->duration_days,
            ]),
            'ad' => $this->whenLoaded('ad', fn () => [
                'id' => $this->ad->id,
                'title' => $this->ad->title,
                'is_featured' => $this->ad->isFeaturedNow(),
                'featured_until' => $this->ad->isFeaturedNow() ? $this->ad->featured_until : null,
            ]),
            'reviewed_at' => $this->reviewed_at,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
