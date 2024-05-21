<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpCoachingActions extends JsonResource
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
            'coaching_id'  => $this->coaching_id,
            'aid'  => $this->aid,
            'complete'  => $this->complete,
            'notes'  => ($this->notes)? $this->notes : '',
            'created_at'  => isset($this->created_at)? formatDate($this->created_at) : null,
            'updated_at'  => isset($this->updated_at)? formatDate($this->updated_at) : null,
            ];
    }
}
