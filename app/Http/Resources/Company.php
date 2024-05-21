<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
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
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'contact_phone' => $this->contact_phone,
            'contact_secondary_phone' => $this->contact_secondary_phone,
            'contact_title' => $this->contact_title,
            'contact_email' => $this->contact_email,
            'business_type' => $this->business_type,
            'whatsup_number' => $this->whatsup_number,
            'status' => $this->status,
            'address' => $this->address,
            'company_website' => $this->company_website,
            'company_name' => $this->company_name,
            'country' => $this->country,
            'time_to_call' => $this->time_to_call,
            'time_zone' => $this->time_zone,
            'image' => $this->image,
            'assessments' => $this->assessments()->count(),
            'created_at' => formatDate($this->created_at),
        ];
    }
}
