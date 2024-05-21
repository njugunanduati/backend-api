<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model {

	protected $table = 'login_otps';
	
	protected $fillable = ['user_id', 'otp', 'otp_date', 'remember_me', 'device_id'];

	public $timestamps = true;

	public function user() {
        return $this->hasOne('App\Models\User');
    }
}