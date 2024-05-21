<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Question extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

    protected $module_name;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, $module_name)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;

        $this->module_name = $module_name;
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'question' => $this->question_text,
            'question_type' => $this->question_type,
            'next_question' => $this->next_question,
            'question_number' => $this->question_number ?? null,
            'impact' => $this->impact,
            'notes' => $this->note()->count() ? $this->note()->first()->note_text : '',
            ];
    }
}
