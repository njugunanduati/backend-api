<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyFile extends JsonResource
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
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description ?? '',
            'user_type' => $this->user_type,
            'url' => $this->url,
            'key'  => $this->key,
            'size'  => $this->size,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
    }
}
