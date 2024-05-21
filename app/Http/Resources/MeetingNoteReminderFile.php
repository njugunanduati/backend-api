<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingNoteReminderFile extends JsonResource
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
            'meeting_note_reminder_id' => $this->meeting_note_reminder_id,
            'name' => $this->name,
            'url' => $this->url,
            'key' => $this->key,
            'type' => $this->type,
        ];
    }
}
