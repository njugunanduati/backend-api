<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FlashCoachingProgress extends JsonResource
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
            'coach_id' => $this->coach_id,
            'student_id' => $this->student_id,
            'company_id' => $this->company_id,
            'assessment_id' => $this->assessment_id,
            'lesson_id' => $this->lesson_id,
            'path' => $this->path,
            'access' => $this->access,
            'progress' => $this->progress,
            'student_first_name' => $this->student_first_name,
            'student_last_name' => $this->student_last_name,
            'company_name' => $this->company_name,
            'student_email' => $this->student_email,
        ];
    }
}

/*
    'flash_coaching_progress.id', 
    'flash_coaching_progress.coach_id', 
    'flash_coaching_progress.company_id', 
    'flash_coaching_progress.student_id', 
    'users.first_name as student_first_name', 
    'users.last_name as student_last_name', 
    'users.company as company_name', 
    'users.email as student_email',
    'flash_coaching_progress.assessment_id',
    'flash_coaching_progress.lesson_id',
    'flash_coaching_progress.path',
    'flash_coaching_progress.access',
    'flash_coaching_progress.progress',
*/
