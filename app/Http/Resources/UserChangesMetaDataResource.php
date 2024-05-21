<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\User;


class UserChangesMetaDataResource extends JsonResource
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
                "id" => $this->id,
                "current_value" => $this->current_value,
                "new_value" => $this->new_value,
                "column_name" => $this->column_name,
                "description" => $this->description,
                "changed_by" => $user->first_name.' '.$user->last_name,
                "changed_date" => $this->changed_date
            ];
    }
}
