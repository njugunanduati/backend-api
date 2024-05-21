<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomQuestionResponse extends Model {

	protected $table = 'custom_question_responses';
	public $timestamps = true;

	public function question()
	{
		return $this->belongsTo('App\Models\CustomQuestion');
	}

}
