<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {

	protected $table = 'transactions';
	public $timestamps = true;

	public function payment_method()
	{
		return $this->hasOne('App\Models\PaymentMethod');
	}

	public function credits()
	{
		return $this->hasMany('App\Models\Credit');
	}

}
