<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonRecording extends JsonResource
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
            'owner_id' => $this->owner_id,
            'lesson_id' => $this->lesson_id,
            // 'user_group_id' => $this->user_group_id,
            'video_url' => $this->video_url,
            'lesson' => $this->lesson,
            'user_group' => $this->usergroup,
            // 'user' => $this->user,
        ];
    }
}