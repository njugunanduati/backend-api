<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
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
            'first_name' => $this->first_name, 
            'last_name' => $this->last_name, 
            'email' => $this->email, 
            'phone_number' => $this->phone_number, 
            'referred_to' => $this->referred_to, 
            'referred_by' => $this->referred_by
        ];
    }
}
