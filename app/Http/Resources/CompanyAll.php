<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyAll extends JsonResource
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
            'contact_title' => $this->contact_title,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'address' => $this->address,
            'company_website' => $this->company_website,
            'company_name' => $this->company_name,
            'business_type' => $this->business_type,
            'time_to_call' => $this->time_to_call
            ];
    }
}
