<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamMemberTaskImplementation extends JsonResource
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
                'team_member_id' => $this->team_member_id,
                'task_id' => $this->task_id,
                'first_name' => $this->member->first_name,
                'last_name' => $this->member->last_name,
                'email' => $this->member->email,
            ];
    }
}
