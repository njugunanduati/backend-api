<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NewUserGroup extends JsonResource
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
            'name' => $this->name ?? '',
            'title' => $this->title ?? '',
            'user_id' => $this->user_id,
            'group_id' => $this->group_id,
            'active' => $this->active,
            'paused' => $this->paused,
            'price' => $this->price,
            'meeting_day' => $this->meeting_day,
            'meeting_time' => $this->meeting_time,
            'time_zone' => $this->time_zone,
            'meeting_url' => $this->meeting_url,
            ];
    }
}