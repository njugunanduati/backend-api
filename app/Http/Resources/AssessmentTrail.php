<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentTrail extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'path' => $this->path,
            'module_name' => $this->module_name,
            'trail' => $this->trail,
            ];
    }
}
