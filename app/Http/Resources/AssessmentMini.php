<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Company as CompanyResource;
use App\Http\Resources\ModuleSet as ModuleSetResource;
use App\Http\Resources\Priorities as PrioritiesResource;



class AssessmentMini extends JsonResource
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
            'company_name' => $this->company->company_name,
            'contact_title' => $this->company->contact_title,
            'contact_name' => $this->company->contact_name,
            'contact_phone' => $this->company->contact_phone,
            'contact_email' => $this->company->contact_email,
            'module_set_name' => $this->moduleSet->name,
            'allow_percent' => $this->allow_percent,
            'percent_added' => $this->percent_added,
            'module_set' => $this->moduleSet,
            'module_set_id' => $this->module_set_id,
            'quotum' => ($this->prioritiesQuestionnaire())? true : false,
            'quotum_recommendation' => ($this->prioritiesQuestionnaire())? $this->prioritiesQuestionnaire()->recommendation : false,
            'implementation_start_date' => ($this->implementation_start_date)? formatDate($this->implementation_start_date): getImplementationStartDate($this->created_at),
            'add_planning_meetings' => $this->add_planning_meetings,
            'add_review_meetings' => $this->add_review_meetings,
            'planning_meetings' => $this->planning_meetings,
            'initial_coaching_cost' => ((int)$this->initial_coaching_cost > 0)? (int)$this->initial_coaching_cost: '',
            'monthly_coaching_cost' => ((int)$this->monthly_coaching_cost > 0)? (int)$this->monthly_coaching_cost: '',
            'agreements' => $this->agreements,
            'revenue_share' => $this->revenue_share,
            'shared' => $this->shared,
            'pivot' => $this->pivot,
            'users' => $this->users()->get(),
            'owner' => $this->owner(),
            'created_at' => formatDate($this->created_at),
            ];

    }
}
