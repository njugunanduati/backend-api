<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Priorities extends JsonResource
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
            'module_name' => $this->module_name,
            'module_alias' => $this->getAlias(),
            'time' => $this->time,
            'order' => $this->order,
            'path' => $this->getPath(),
            ]; 
    }
}
