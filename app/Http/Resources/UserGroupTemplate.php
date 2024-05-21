<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Role as RoleResource;

class UserGroupTemplate extends JsonResource
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
                'id' => $this->id,
                'user_id' => $this->user_id,
                'group_id' => $this->group_id,
                'group' => $this->group()->first()
            ];
    }
}
