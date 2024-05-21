<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Role as RoleResource;

class OnboardingRoleplayPrepMini extends JsonResource
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
                'step_2' => $this->step_2,
                'step_3' => $this->step_3,
                'step_4' => $this->step_4,
                'step_5' => $this->step_5,
                'step_6' => $this->step_6,
                'step_7' => $this->step_7
            ];
    }
}
