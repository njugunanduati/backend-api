<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MeetingNoteReminderFile as FileResource;
use App\Http\Resources\TeamMemberTaskCommitment as MemberResource;

class MeetingNoteReminder extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'note' => $this->note,
            'client' => $this->meetingnote->company,
            'reminder_date' => $this->reminder_date,
            'reminder_time' => $this->reminder_time,
            'send_reminder' => $this->send_reminder,
            'time_zone' => $this->time_zone,
            'member' => ($this->teammember)? new MemberResource($this->teammember) : null
        ];
    }
}
