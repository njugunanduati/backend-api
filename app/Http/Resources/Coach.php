<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserCalendarURL as CalendarResource;
use App\Http\Resources\Role as RoleResource;

class Coach extends JsonResource
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
                'role' => $this->role->name,
                'meeting_url' => $this->meeting_url,
                'calendarurls' => isset($this->calendarurls)? new CalendarResource($this->calendarurls) : null
            ];
    }
}
