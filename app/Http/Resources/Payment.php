<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Payment extends JsonResource
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
            'paid' => $this->paid,
            'amount' => $this->amount,  
            'paid_on' => $this->paid_on,  
            'group_id' => $this->group_id,
            'log' => $this->log,  
            ];
    }
}
