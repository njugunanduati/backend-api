<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model {

	protected $table = 'credits';
	public $timestamps = true;

	public function module_set()
	{
		return $this->belongsTo('App\Models\ModuleSet');
	}

	public function user()
	{
		return $this->belongsTo('App\Models\User');
	}

}
