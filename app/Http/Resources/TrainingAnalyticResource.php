<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingAnalyticResource extends JsonResource
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
            'group_id' => $this->group_id,
            'user_group_id' => $this->user_group_id,
            'user_name' => isset($this->user)? $this->user->first_name .' '.$this->user->last_name : null,
            'advisor' => isset($this->user)? $this->user->advisor : null,
            'manager' => isset($this->user)? $this->user->manager : null,
            'video_id' => $this->video_id,
            'video_name' => $this->video_name,
            'video_progress' => isset($this->video_progress)? (float)$this->video_progress : 'None',
            'video_time_watched' => isset($this->video_time_watched)? (float)$this->video_time_watched : 'None',
            'video_length' => isset($this->video_length)? (float)$this->video_length : 'None',
            'quiz_score' => isset($this->quiz_score)? (float)$this->quiz_score : 'None',
            'quiz_correct_answers' => isset($this->quiz_correct_answers)? (int)$this->quiz_correct_answers : 'None',
            'quiz_total_questions' => isset($this->quiz_total_questions)? (int)$this->quiz_total_questions : 'None',
            'quiz_answers' => isset($this->quiz_answers)? $this->quiz_answers : 'None',
            'quiz_url' => isset($this->quiz_url)? $this->quiz_url : 'None',
            'created_at'  => isset($this->created_at)? formatHumanDate($this->created_at) : null,
            'updated_at'  => isset($this->updated_at)? formatHumanDate($this->updated_at) : null,
            ];
    }
}
