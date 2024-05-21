<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertiseJS40 extends JsonResource
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
            'strategy' => $this->strategy,
            'trust' => $this->trust,
            'policies' => $this->policies,
            'referral' => $this->referral,
            'publicity' => $this->publicity,
            'mail' => $this->mail,
            'advertising' => $this->advertising,
            'scripts' => $this->scripts,
            'initialclose' => $this->initialclose,
            'followupclose' => $this->followupclose,
            'formercustomers' => $this->formercustomers,
            'appointments' => $this->appointments,
            'purchase' => $this->purchase,
            'longevity' => $this->longevity,

        ];
    }
}
