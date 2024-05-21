<?php

namespace App\Http\Resources;

use App\Http\Resources\OnboardingActivity;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingBusinessUser extends JsonResource
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
                'advisor' => $this->business_advisor,
                'business_onboarding_status' => $this->business_onboarding_status,
                'advisor_name' => isset($this->getbusinessadvisor)? $this->getbusinessadvisor->first_name .' '.$this->getbusinessadvisor->last_name : 'None',
                'advisor_email' => isset($this->getbusinessadvisor)? $this->getbusinessadvisor->email : 'None',
                'last_activity' => isset($this->lastonboardingactivityweb)? new OnboardingActivity($this->lastonboardingactivityweb) : 'None',
            ];
    }
}
