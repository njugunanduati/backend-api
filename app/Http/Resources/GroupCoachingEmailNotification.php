<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupCoachingEmailNotification extends JsonResource
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
            'lesson_id' => $this->lesson_id,
            'three_days_before_coach' => $this->three_days_before_coach,
            'three_days_before_coach_sub' => $this->three_days_before_coach_sub,
            'three_days_before' => $this->three_days_before,
            'three_days_before_sub' => $this->three_days_before_sub,
            'one_day_before' => $this->one_day_before,
            'one_day_before_sub' => $this->one_day_before_sub,
            'one_hour_before' => $this->one_hour_before,
            'one_hour_before_sub' => $this->one_hour_before_sub,
            'three_min_after' => $this->three_min_after,
            'three_min_after_sub' => $this->three_min_after_sub,
            'ten_min_after' => $this->ten_min_after,
            'ten_min_after_sub' => $this->ten_min_after_sub,
            'one_day_after' => $this->one_day_after,
            'one_day_after_sub' => $this->one_day_after_sub,
           
        ];
    }
}
