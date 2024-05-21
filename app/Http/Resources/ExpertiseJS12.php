<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertiseJS12 extends JsonResource
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
            'costs' => $this->costs,
            'mdp' => $this->mdp,
            'offer' => $this->offer,
            'prices' => $this->prices,
            'upsell' => $this->upsell,
            'bundling' => $this->bundling,
            'downsell' => $this->downsell,
            'products' => $this->products,
            'alliances' => $this->alliances,
            'campaign' => $this->campaign,
            'leads' => $this->leads,
            'internet' => $this->internet,
        ];
    }
}
