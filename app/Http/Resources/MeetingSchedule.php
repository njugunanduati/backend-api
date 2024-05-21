<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingSchedule extends JsonResource
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
            'coaching' => $this->coaching,
            'company_id' => $this->company_id,
            'notes' => $this->notes,
            'closed' => $this->closed,
            'meeting_time' => $this->meeting_time,
            'next_meeting_time' => $this->next_meeting_time,
            'meeting_url' => ($this->meeting_url)? $this->meeting_url: '',
            'time_zone' => $this->time_zone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
}
