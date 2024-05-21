<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TeamMemberTaskImplementation as MemberResource;

class MeetingNoteImplementationAction extends JsonResource
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
            'implementation_id'  => $this->implementation_id,
            'aid'  => $this->aid,
            'complete'  => $this->complete,
            'deadline' => $this->deadline,
            'member' => ($this->teammember)? new MemberResource($this->teammember) : null
            ];
    }
}
