<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AIInternResource extends JsonResource
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
            '_id'=>$this->_id,
            'first_name'=>$this->first_name,
            'last_name'=> $this->last_name,
            'email' => $this->email,
        ];
    }
}