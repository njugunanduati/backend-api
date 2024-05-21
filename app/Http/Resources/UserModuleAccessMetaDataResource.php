<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;



class UserModuleAccessMetaDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = User::find($this->changed_by);
        return
            [
                'id' => $this->id,
                "module_name" => $this->module_name,
                "description" => $this->description,
                "changed_by" => $user->first_name.' '.$user->last_name,
                "changed_at" => $this->changed_at
            ];
    }
}
