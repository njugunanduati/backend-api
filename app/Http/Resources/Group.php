<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Group extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'group_img' => $this->group_img,
            'owner' => $this->owner,
            'price' => $this->price,
            'status' => $this->status,
            'payment_frequency' => $this->payment_frequency,
            'template' => $this->template,
            'intro_image' => $this->intro_image,
            'template_video' => $this->template_video,
            'active' => $this->active,
            'access' => $this->access ?? false,
            'meets_on' => $this->meets_on,
            'meeting_time' => $this->meeting_time,
            'time_zone' => $this->time_zone,
            'meeting_url' => $this->meeting_url,
            ];
    }
}