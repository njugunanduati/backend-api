<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Session extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'meeting_title' => $this->meeting_title,
            'meeting_notes' => $this->meeting_notes,
            'current_revenue' => $this->current_revenue,
            'coach_action_steps' => $this->coach_action_steps,
            'client_action_steps' => $this->client_action_steps,
            'meeting_date' => $this->meeting_date,
            'next_meeting_date' => $this->next_meeting_date,
            'next_meeting_location' => $this->next_meeting_location,
            'meeting_keywords' => $this->meeting_keywords,
            'audio_file_path' => $this->audio_file_path,
            'created_at' => ($this->created_at)? $this->created_at->format('dS M Y') : ''
            ];
    }
}
