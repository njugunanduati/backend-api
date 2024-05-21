<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\IntegrationGroupList;

class Integration extends JsonResource
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
            'stripe' => $this->stripe ?? null,
            'mpesa' => $this->mpesa ?? null,
            'paypal' => $this->paypal ?? null,
            'aweber' => $this->aweber ?? null,
            'getresponse' => $this->getresponse ?? null,
            'active_campaign' => $this->active_campaign ?? null,
            'lists' => IntegrationGroupList::collection($this->lists) ?? null,
            'stripe_details' => $this->stripe_details ?? null,
            'aweber_details' => $this->aweber_details ?? null,
            'getresponse_details' => $this->getresponse_details ?? null,
            'active_campaign_details' => $this->active_campaign_details ?? null,
            ]; 
    }
}
