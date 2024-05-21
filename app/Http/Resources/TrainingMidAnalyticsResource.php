<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingMidAnalyticsResource extends JsonResource
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
            'type' => formatAnalyticsType($this->type),
            'type_id' => $this->type,
            'user_id' => $this->user_id,
            'video_id' => $this->video_id,
            'video_progress' => isset($this->video_progress)? (float)$this->video_progress : 'None',
            ];
    }
}
