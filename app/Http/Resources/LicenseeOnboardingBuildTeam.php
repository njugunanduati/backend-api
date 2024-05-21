<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Role as RoleResource;

class LicenseeOnboardingBuildTeam extends JsonResource
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
                'user_id' => $this->user_id,
                'step_1' => $this->step_1,
                'step_1_date' => $this->step_1_date,
                'step_1_note' => $this->step_1_note,
                'step_2' => $this->step_2,
                'step_2_date' => $this->step_2_date,
                'step_2_note' => $this->step_2_note,
                'step_3' => $this->step_3,
                'step_3_date' => $this->step_3_date,
                'step_3_note' => $this->step_3_note,
                'step_4' => $this->step_4,
                'step_4_date' => $this->step_4_date,
                'step_4_note' => $this->step_4_note,
                'step_5' => $this->step_5,
                'step_5_date' => $this->step_5_date,
                'step_5_note' => $this->step_5_note,
            ];
    }
}
