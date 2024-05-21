<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserFavoritesResource extends JsonResource
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
                'user_id' => $this->user_id,
                'resource_id' => $this->resource_id,
                'is_favorite' => $this->is_favorite
            ];
    }
}
