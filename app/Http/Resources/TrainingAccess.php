<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrainingAccess extends JsonResource
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
            'training_software' => $this->training_software ?? null,
            'training_100k' => $this->training_100k ?? null,
            'training_lead_gen' => $this->training_lead_gen ?? null,
            'training_jumpstart' => $this->training_jumpstart ?? null,
            'prep_roleplay' => $this->prep_roleplay ?? null,
            'group_coaching' => $this->group_coaching ?? null,
            'flash_coaching' => $this->flash_coaching ?? null,
            'licensee_onboarding' => $this->licensee_onboarding ?? null,
            'licensee_advisor' => $this->licensee_advisor ?? null,
            'lead_generation' => $this->lead_generation ?? null,
            'quotum_access' => $this->quotum_access ?? null,
            'simulator' => $this->simulator ?? null,
            ];
    }
}
