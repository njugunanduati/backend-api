<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingMiniAnalyticsResource extends JsonResource
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
            'id' => $this->video_id,
            'type' => formatAnalyticsType($this->type),
            'type_id' => $this->type,
            'video_progress' => isset($this->video_progress)? (float)$this->video_progress : 'None',
            'quiz_score' => isset($this->quiz_score)? (float)$this->quiz_score : 'None',
            ];
    }
}
