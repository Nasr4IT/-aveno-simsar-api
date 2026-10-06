<?php

namespace App\Http\Resources;

use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'reportable_type' => $this->reportable_type === Ad::class ? 'ad' : 'user',
            'reportable' => $this->whenLoaded('reportable', fn () => $this->reportable_type === Ad::class
                ? new AdResource($this->reportable)
                : new UserResource($this->reportable)
            ),
            'created_at' => $this->created_at,
            'reviewed_at' => $this->reviewed_at,
        ];
    }
}
