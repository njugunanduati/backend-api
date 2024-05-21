<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingNoteTask extends JsonResource
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
            'meeting_note_id' => $this->meeting_note_id,
            'note' => $this->note,
            'created_at' => formatHumanDate($this->created_at),
        ];
    }
}
