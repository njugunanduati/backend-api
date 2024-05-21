<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AweberIntegration extends JsonResource
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
            'user_id' => $this->user_id ?? null,
            'account_id' => $this->account_id ?? null,
            'auth_code' => $this->auth_code ?? null,
            'state' => $this->state ?? null,
            'access_token' => $this->access_token ?? null,
            'refresh_token' => $this->refresh_token ?? null,
            'expires_in' => $this->expires_in ?? null,
            ]; 
    
    }
}
