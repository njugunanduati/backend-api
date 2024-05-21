<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PricesExtraResponse extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'current_customer_number' => $this->current_customer_number,
            'leaving_customer_number' => $this->leaving_customer_number,
            'may_happen' => $this->may_happen
            ];
    }
}
