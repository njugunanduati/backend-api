<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MetricsClientResource extends JsonResource
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
            'meeting_note_id' => $this->meeting_note_id,
            'revenue' => $this->revenue,
            'profit' => $this->profits,
            'leads' => $this->leads,
            'conversions' => $this->conversions,
            'date' => $this->date 
        ];
    }
}