<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotumLevelThreeResource extends JsonResource
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
            'level_three_description' => $this->description,
            'module' => $this->module,
            'path' => $this->path,
            'status' => $this->status,
            '__children' => QuotumLevelFourResource::collection($this->children),
            ];
    }
}
