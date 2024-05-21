<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomGroupLesson extends JsonResource
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
            'user_group_id' => $this->user_group_id ?? '',
            'lesson_id' => $this->lesson_id,
            'lesson_length' => $this->lesson_length,
            'lesson_order' => $this->lesson_order,
            ];
    }
}