<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertiseSales extends JsonResource
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
            'salesmanager' => $this->salesmanager,
            'salescompensation' => $this->salescompensation,
            'salessuperstars' => $this->salessuperstars,
            'salestraining' => $this->salestraining,
            'salesprospecting' => $this->salesprospecting,
            'salesclients' => $this->salesclients,
            'salestrade' => $this->salestrade,
            'salesdm' => $this->salesdm,
            'salesclosing' => $this->salesclosing,
            'salesorder' => $this->salesorder,
            'salesremorse' => $this->salesremorse,
        ];
    }
}
