<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helper;

class LoginTracker extends Model {

	protected $table = 'login_tracker';

    protected $fillable = [
        'user_id',
        'ip',
        'browser',
        'city',
        'country_name',
        'timezone',
        'latitude',
        'longitude',
    ];

	public $timestamps = true;
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
	}

}
