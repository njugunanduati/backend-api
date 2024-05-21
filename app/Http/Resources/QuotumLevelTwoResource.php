<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuotumLevelTwoResource extends JsonResource
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
            'level_two_description' => $this->description,
            'module' => $this->module,
            'path' => $this->path,
            'status' => $this->status,
            '__children' => isset($this->children)? QuotumLevelThreeResource::collection($this->children) : [],
            ];
    }
}
