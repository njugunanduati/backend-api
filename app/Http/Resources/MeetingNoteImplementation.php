<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MeetingNoteImplementationAction as ActionsResource;

class MeetingNoteImplementation extends JsonResource
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
            'assessment_id'  => $this->assessment_id,
            'company_id'  => $this->company_id,
            'path'  => $this->path,
            'start_date'  => $this->start_date,
            'time'  => $this->time,
            'archived'  => $this->archived,
            'actions'  => isset($this->actions)? ActionsResource::collection($this->actions) : [],
            ];
    }
}
