<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCalendarURL extends JsonResource
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
            'fifteen_url' => $this->fifteen_url ?? '',
            'thirty_url' => $this->thirty_url ?? '',
            'forty_five_url' => $this->forty_five_url ?? '',
            'sixty_url' => $this->sixty_url ?? '',
            ];
    }
}