<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotumCommitmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'commitment_id' => $this->commitment_id,
            'note_id' => $this->note_id,
            'assessment_id' => $this->assessment_id,
            'coach_id' => $this->coach_id,
            'company_id' => $this->company_id,
            'content_id' => $this->content_id,
            'type' => $this->type,
        ];
    }
}
