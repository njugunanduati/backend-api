<?php

namespace App\Http\Resources;

use App\Http\Resources\OnboardingActivity;
use Illuminate\Http\Resources\Json\JsonResource;

class LicenseeOnboardingUser extends JsonResource
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
                'licensee_onboarding_advisor' => $this->licensee_onboarding_advisor,
                'licensee_onboarding_status' => $this->licensee_onboarding_status,
                'licensee_advisor_name' => isset($this->getlicenseeadvisor)? $this->getlicenseeadvisor->first_name .' '.$this->getlicenseeadvisor->last_name : 'None',
                'licensee_advisor_email' => isset($this->getlicenseeadvisor)? $this->getlicenseeadvisor->email : 'None',
                'last_activity' => isset($this->lastonboardingactivitylicensee)? new OnboardingActivity($this->lastonboardingactivitylicensee) : 'None',
                'updated_at' => formatDate($this->updated_at),
            ];
    }
}
