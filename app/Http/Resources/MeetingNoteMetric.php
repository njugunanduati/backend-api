<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MeetingNoteMetric extends JsonResource
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
            'setting_id' => $this->setting_id,
            'value' => $this->value,
            'label' => $this->settings->label,
            'name' => $this->settings->name
        ];
    }
}
