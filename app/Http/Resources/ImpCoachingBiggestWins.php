<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImpCoachingBiggestWins extends JsonResource
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
            'notes'  => ($this->notes)? $this->notes : '',
            'created_at'  => isset($this->created_at)? formatDate($this->created_at) : null,
            'updated_at'  => isset($this->updated_at)? formatDate($this->updated_at) : null,
            ];
    }
}
