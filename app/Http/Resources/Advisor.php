<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Role as RoleResource;

class Advisor extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return
            [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'first_email' => $this->user->email,
                'second_email' => $this->email_address,
                'time_zone' => $this->time_zone,
                'calendar_link' => $this->calendar_link,
                'image_url' => $this->image_url,
            ];
    }
}
