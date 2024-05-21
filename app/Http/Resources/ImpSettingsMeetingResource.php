<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpSettingsMeetingResource extends JsonResource
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
            'settings_id'  => $this->settings_id ?? null,
            'meeting_day'  => $this->meeting_day ?? '',
            'meeting_time'  => $this->meeting_time ?? '',
            'time_zone'  => $this->time_zone ?? '',
            ];
    }
}
