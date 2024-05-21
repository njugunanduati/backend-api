<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AISuggestion extends JsonResource
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
            '_id' => $this->_id,
            'question' => $this->question,
            'path' => $this->path,
            'alias' => $this->alias,
            'responses' => $this->responses,
        ];
    }
}
