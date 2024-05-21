<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpSimplifiedStep extends JsonResource
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
            'id' => $this->id ?? null,
            'path'  => $this->path ?? '',
            'step'  => $this->step ?? '',
            'header'  => $this->header ?? '',
            'body'  => $this->body ?? '',
            'student_header' => $this->student_header ?? '',
            'student_body' => $this->student_body ?? '',
            ];
    }
}
