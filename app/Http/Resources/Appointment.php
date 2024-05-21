<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AppointmentUser as UserResource;
use App\Http\Resources\MiniCompany as CompanyResource;

class Appointment extends JsonResource
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
            'date' => $this->next_meeting_time,
            'time_zone' => $this->time_zone,
            'meeting_url' => isset($this->meeting_url) ? $this->meeting_url : '',
            'user'  => isset($this->user) ? new UserResource($this->user) : null,
            'company'  => isset($this->company) ? new CompanyResource($this->company) : null,
        ];
    }
}
