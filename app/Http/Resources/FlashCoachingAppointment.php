<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlashCoachingAppointment extends JsonResource
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
            'coach_id' => $this->coach_id,
            'student_id' => $this->student_id,
            'company_id' => $this->company_id,
            'meeting_time' => $this->meeting_time,
            'type' => $this->type ?? '',
            'meeting_url' => $this->meeting_url ?? '',
            'time_zone' => $this->time_zone,
            'student_first_name' => $this->student_first_name,
            'student_last_name' => $this->student_last_name,
            'company_name' => $this->company_name,
            'student_email' => $this->student_email,
        ];
    }
}
