<?php

namespace App\Http\Resources;

use App\Models\ImpCoaching;
use Illuminate\Http\Resources\Json\JsonResource;

class ImpCoachingSimple extends JsonResource
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
            'nid'  => $this->nid ?? null, 
            'path'  => $this->path ?? null,
            ];
    }
}
