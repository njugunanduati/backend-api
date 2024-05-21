<?php

namespace App\Http\Resources;

use App\Http\Resources\OnboardingActivity;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingUser extends JsonResource
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
                'advisor' => $this->advisor,
                'onboarding_status' => $this->onboarding_status,
                'advisor_name' => isset($this->getadvisor)? $this->getadvisor->first_name .' '.$this->getadvisor->last_name : 'None',
                'advisor_email' => isset($this->getadvisor)? $this->getadvisor->email : 'None',
                'last_activity' => isset($this->lastonboardingactivitypas)? new OnboardingActivity($this->lastonboardingactivitypas) : 'None',
                'updated_at' => formatDate($this->updated_at),
            ];
    }
}
