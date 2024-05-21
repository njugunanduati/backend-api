<?php

namespace App\Http\Resources;

use App\Http\Resources\ExpertiseJS12;
use App\Http\Resources\ExpertiseJS40;
use App\Http\Resources\ExpertiseDigital;
use App\Http\Resources\ExpertiseSales;
use Illuminate\Http\Resources\Json\JsonResource;

class PriorityQuestionnaire extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $recommend = (
            (empty($this->q1) == true) && 
            (empty($this->q1b) == true) &&
            (empty($this->q2) == true) &&
            (empty($this->q3) == true) &&
            (empty($this->q4) == true) &&
            (empty($this->q5) == true)
        ); 
        
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'q1' => $this->q1 ?? '',
            'q1b' => $this->q1b ?? '',
            'q2' => $this->q2 ?? '',
            'q3' => $this->q3 ?? '',
            'q4' => $this->q4 ?? '',
            'q5' => $this->q5 ?? '',
            'recommendation' => ($recommend)? null : $this->recommendation,
            'js_12_expertise' => $this->js_12_expertise()? new ExpertiseJS12($this->js_12_expertise()) : null,
            'js_40_expertise' => $this->js_40_expertise()? new ExpertiseJS40($this->js_40_expertise()) : null,
            'sales_expertise' => $this->sales_expertise()? new ExpertiseSales($this->sales_expertise()) : null,
            'digital_expertise' => $this->digital_expertise()? new ExpertiseDigital($this->digital_expertise()) : null,
            ]; 
    }
}
