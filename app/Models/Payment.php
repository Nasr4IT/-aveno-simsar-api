<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to feature an ad, paid manually through Sham Cash: the seller
 * uploads the transfer receipt, an admin approves (status 'paid' → the ad is
 * featured) or rejects it. See App\Services\FeaturePaymentService.
 */
class Payment extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'ad_id', 'ad_package_id', 'amount', 'currency',
        'provider', 'provider_reference', 'receipt_path', 'note', 'status',
        'rejection_reason', 'reviewed_by', 'reviewed_at', 'raw_response', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class)->withTrashed();
    }

    public function adPackage(): BelongsTo
    {
        return $this->belongsTo(AdPackage::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }
}
