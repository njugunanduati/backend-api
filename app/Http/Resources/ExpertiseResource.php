<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ExpertiseJS12;
use App\Http\Resources\ExpertiseJS40;
use App\Http\Resources\ExpertiseDigital;
use App\Http\Resources\ExpertiseSales;

class ExpertiseResource extends JsonResource
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
            'user_id' => $this->user_id,
            'js_12_expertise' => isset($this->js_12_expertise)? new ExpertiseJS12($this->js_12_expertise) : null,
            'js_40_expertise' => isset($this->js_40_expertise)? new ExpertiseJS40($this->js_40_expertise) : null,
            'sales_expertise' => isset($this->sales_expertise)? new ExpertiseSales($this->sales_expertise) : null,
            'digital_expertise' => isset($this->digital_expertise)? new ExpertiseDigital($this->digital_expertise) : null,
        ];
    }
}
