<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlashCoachingAnalysis extends JsonResource
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
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'path' => $this->path,
            'video_id' => $this->video_id,
            'video_name' => $this->video_name,
            'video_progress' => isset($this->video_progress)? (float)$this->video_progress : 0,
            'video_time_watched' => isset($this->video_time_watched)? (float)$this->video_time_watched : 0,
            'video_length' => isset($this->video_length)? (float)$this->video_length : 0,
            'notes' => $this->notes,
            ];
    }
}
