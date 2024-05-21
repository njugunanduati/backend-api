<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentUser extends JsonResource
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
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'company' => $this->company,
                'website' => $this->website,
                'title'=> $this->title,
                'phone_number'=> $this->phone_number,
                'meeting_url' => isset($this->meeting_url) ? $this->meeting_url : '',
            ];
    }
}
