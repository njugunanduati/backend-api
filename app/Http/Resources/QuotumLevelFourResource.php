<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotumLevelFourResource extends JsonResource
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
            'level_four_description' => Str::markdown($this->description), // Return HTML
            'module' => $this->module,
            'path' => $this->path,
            'status' => $this->status,
            '__children' => isset($this->children)? $this->children : [],
            ];
    }
}
