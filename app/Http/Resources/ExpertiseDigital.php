<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertiseDigital extends JsonResource
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
            'dgcontent' => $this->dgcontent,
            'dgwebsite' => $this->dgwebsite,
            'dgemail' => $this->dgemail,
            'dgseo' => $this->dgseo,
            'dgadvertising' => $this->dgadvertising,
            'dgsocial' => $this->dgsocial,
            'dgvideo' => $this->dgvideo,
            'dgmetrics' => $this->dgmetrics,
        ];
    }
}
