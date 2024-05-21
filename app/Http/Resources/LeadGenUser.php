<?php

namespace App\Http\Resources;

use App\Http\Resources\OnboardingActivity;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadGenUser extends JsonResource
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
                'id' => $this->id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'manager' => isset($this->manager)? $this->manager : 'None',
                'manager_name' => isset($this->getmanager)? $this->getmanager->first_name .' '.$this->getmanager->last_name : 'Focused.com',
                'manager_email' => isset($this->getmanager)? $this->getmanager->email : 'None',
                'lead_gen_advisor' => $this->lead_gen_advisor,
                'advisor_name' => isset($this->getLeadGenAdvisor)? $this->getLeadGenAdvisor->first_name .' '.$this->getLeadGenAdvisor->last_name : 'None',
                'advisor_email' => isset($this->getLeadGenAdvisor)? $this->getLeadGenAdvisor->email : 'None',
                'last_activity' => isset($this->last_lead_gen_activity)? $this->last_lead_gen_activity : 'None',
                'updated_at' => formatDate($this->updated_at),
            ];
    }
}
