<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadGenAnalysis extends JsonResource
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
                'id'=>$this->id,
                'status'=> "$this->human_page: $this->human_tab  ($this->human_step)",
                "date" => $this->adate,
                'user' => [
                    'id' => $this->user->id,
                    'name' => $this->user->first_name.' '.$this->user->last_name,
                ]
            ];
    }
}
