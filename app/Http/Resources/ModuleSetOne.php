<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ModuleSetModule as ModuleResource;


class ModuleSetOne extends JsonResource
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
            'module_set_name' => $this->name,
            'modules' => ModuleResource::collection($this->modules),
        ];
    }
}
