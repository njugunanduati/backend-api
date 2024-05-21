<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationGroupList extends JsonResource
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
            'id' => $this->id ?? null,
            'list_id' => $this->list_id ?? null,
            'group_id' => $this->group_id ?? null,
            'integration_id' => $this->integration_id ?? null,
            'responses' => $this->responses ?? null,
            ]; 
    }
}
