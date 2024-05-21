<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ImpSettingsMeetingResource as MeetingResource;

class ImpSettingsResource extends JsonResource
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
            'id' => $this->id ?? null,
            'assessment_id'  => $this->assessment_id ?? null, 
            'three_days'  => $this->three_days ?? null, 
            'one_day'  => $this->one_day ?? null, 
            'one_hour'  => $this->one_hour ?? null, 
            'meeting_sequence'  => $this->meeting_sequence ?? null,
            'meeting_type'  => $this->meeting_type ?? '',
            'zoom_url'  => $this->zoom_url ?? '',
            'phone_number'  => $this->phone_number ?? '',
            'meeting_address'  => $this->meeting_address ?? '',
            'meetings'  => isset($this->meetings)? MeetingResource::collection($this->meetings) : [],
            'created_at'  => isset($this->created_at)? formatDate($this->created_at) : null,
            'updated_at'  => isset($this->updated_at)? formatDate($this->updated_at) : null,
            'start_date' => isset($this->assessment->implementation_start_date)? formatDate($this->assessment->implementation_start_date) : null,
            ];
    }
}
