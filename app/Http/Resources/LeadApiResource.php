<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
class LeadApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'source' => $this->source,
            'stage' => $this->stage,
            'assigned_to' => $this->assigned_to,
            'owner' => $this->assignee?->name,
            'next_follow_up_at' => optional($this->next_follow_up_at)?->toIso8601String(),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
