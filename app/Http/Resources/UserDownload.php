<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDownload extends JsonResource
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
                'First Name' => $this->first_name,
                'Last Name' => $this->last_name,
                'Email' => $this->email,
                'Company' => $this->company,
                'Website' => $this->website,
                'Role' => $this->role->name,
            ];
    }
}
