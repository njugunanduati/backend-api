<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotumLevelOneResource extends JsonResource
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
            'id' => (string)$this->id,
            'parent_id' => (string)$this->parent_id,
            'level_one_description' => $this->description,
            'step' => (int)$this->step,
            'module' => $this->module,
            'path' => $this->path,
            'status' => $this->status,
            '__children' => QuotumLevelTwoResource::collection($this->children),
            ];
    }
}
