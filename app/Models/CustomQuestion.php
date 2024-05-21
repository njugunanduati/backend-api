<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomQuestion extends Model {

	protected $table = 'custom_questions';
	public $timestamps = true;

	public function user()
	{
		return $this->belongsTo('App\Models\User');
	}

	public function responses()
	{
		return $this->hasMany('App\Models\CustomQuestionResponse');
	}

}
