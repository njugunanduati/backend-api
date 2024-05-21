<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingSurvey extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = $this->user()->first();
        return
            [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'type' => $this->type,
                'survey_answers' => json_decode($this->survey_answers),
                'survey_url' => $this->survey_url,
                'created_at' => $this->created_at,
                'user' => [
                    'id' => ($user) ? $user->id : '',
                    'first_name' => ($user) ? $user->first_name : '',
                    'last_name' => ($user) ? $user->last_name : '',
                    'email' => ($user) ? $user->email : '',
                ]
            ];
    }
}
