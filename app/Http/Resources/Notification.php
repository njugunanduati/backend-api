<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\JsonResource;

class Notification extends JsonResource
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
            'type' => $this->type ?? '',
            'title' => $this->title ?? '',
            'description' => Str::markdown($this->description) ?? '',
            'created' => formatHumanDate($this->created_at) ?? '',
            'readby' => $this->analysis->count() ?? 0,
            ];
    }
}