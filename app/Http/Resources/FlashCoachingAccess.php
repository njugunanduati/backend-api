<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlashCoachingAccess extends JsonResource
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
            'coach_id' => $this->coach_id,
            'student_id' => $this->student_id,
            'access' => $this->access,
        ];
    }
}
