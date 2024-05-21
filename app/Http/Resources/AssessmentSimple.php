<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentSimple extends JsonResource
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
            'name' => $this->name,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'module_set_id' => $this->module_set_id,
            'allow_percent' => $this->allow_percent,
            'percent_added' => $this->percent_added,
            'quotum' => ($this->prioritiesQuestionnaire())? true : false,
            'quotum_recommendation' => ($this->prioritiesQuestionnaire())? $this->prioritiesQuestionnaire()->recommendation : false,
            'currency' => $this->currency !== null ? $this->currency : $this->defaultCurrency ,
            'company_name' => $this->company->company_name,
            'business_type' => $this->company->business_type,
            'owner' => $this->owner(),
        ];
    }
}
