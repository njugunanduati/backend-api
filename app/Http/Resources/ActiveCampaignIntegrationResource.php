<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActiveCampaignIntegrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return
            [
                'user_id' => $this->user_id,
                'url' => $this->url,
                'api_key' => $this->api_key,
                'is_active' => $this->is_active
            ];
    }
}
