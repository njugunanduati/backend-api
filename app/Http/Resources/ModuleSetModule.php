<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ModuleSet as ModuleSetResource;


class ModuleSetModule extends JsonResource
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
            'module_name' => $this->module_name,
            'module_alias' => $this->module_alias,
            'module_order'=>$this->order,
            'module_path'=>$this->path,
            'module_type'=>$this->type,
            'module_set' => new ModuleSetResource($this->moduleSet),
            'module_meta' => $this->meta()
        ];
    }
}
