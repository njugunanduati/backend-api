<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Lesson extends JsonResource
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
            'next_lesson' => $this->next_lesson,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_desc' => $this->short_desc,
            'full_desc' => $this->full_desc,
            'owner' => $this->owner,
            'lesson_img' => $this->lesson_img,
            'lesson_length' => ($this->lesson_length)? $this->lesson_length : null,
            'lesson_video' => $this->lesson_video,
            'quiz_url' => $this->quiz_url,
            'published' => $this->published,
            'free_lesson' => $this->free_lesson,
            'price' => $this->price
        ];
    }
}