<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MeetingNoteOther as OtherResource;
use App\Http\Resources\MeetingNoteMetric as MetricsResource;
use App\Http\Resources\MeetingNoteReminder as ReminderResource;

class MeetingNote extends JsonResource
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
            'others'  => isset($this->others) ? OtherResource::collection($this->others) : [],
            'metrics'  => isset($this->metrics) ? MetricsResource::collection($this->metrics) : [],
            'reminder'  => isset($this->reminder) ? ReminderResource::collection($this->reminder) : [],
        ];
    }
}
