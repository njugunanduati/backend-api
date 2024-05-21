<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company as CompanyResource;
use App\Http\Resources\ModuleSet as ModuleSetResource;



class AssessmentAll extends JsonResource
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
            'module_set_id' => $this->module_set_id,
            'module_set' => $this->moduleSet,
            'allow_percent' => $this->allow_percent,
            'percent_added' => $this->percent_added,
            'currency_symbol' => $this->currency_symbol,
            'back_count' => $this->back_count,
            'implementation_start_date' => $this->implementation_start_date,
            'add_planning_meetings' => $this->add_planning_meetings,
            'add_review_meetings' => $this->add_review_meetings,
            'planning_meetings' => $this->planning_meetings,
            'initial_coaching_cost' => ((int)$this->initial_coaching_cost > 0)? (int)$this->initial_coaching_cost: '',
            'monthly_coaching_cost' => ((int)$this->monthly_coaching_cost > 0)? (int)$this->monthly_coaching_cost: '',
            'agreements' => $this->agreements,
            'otherindustry' => ($this->otherindustry)? json_decode($this->otherindustry) : $this->otherindustry,
            'revenue_share' => $this->revenue_share,
            'shared' => $this->shared,
            'users' => $this->users()->get(),
            'owner' => $this->owner(),
            'trails' => $this->trails,
            ];

    }
}
