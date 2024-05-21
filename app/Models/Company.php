<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class Company extends Model {

	protected $table = 'companies';
	
	protected $fillable = [
		'contact_first_name',
		'contact_last_name',
		'company_name',
		'contact_title',
		'contact_phone',
		'contact_secondary_phone',
		'whatsup_number',
		'time_to_call',
		'status',
		'contact_email',
		'address',
		'company_website',
		'country',
		'time_zone',
		'business_type',
		'image'
	];
	public $timestamps = true;

	public function __toString() {
		return $this->company_name;
	}

	  /**
     * Set the company's fname. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setContactFirstNameAttribute($value)
    {
        $this->attributes['contact_first_name'] = trimSpecial(strip_tags($value));
		
    }

	  /**
     * Set the company's lname. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setContactLastNameAttribute($value)
    {
        $this->attributes['contact_last_name'] = trimSpecial(strip_tags($value));
		
    }

	public function assessments()
	{
		return $this->hasMany('App\Models\Assessment');
	}

	public function users()
	{
		return $this->belongsToMany('App\Models\User');
	}

	public function contact()
	{
		return DB::table('users')->where('email', $this->contact_email)->first();
	}

	public function coach() {
		$uc = DB::table('company_user')->where('company_id', $this->id)->first();
		if($uc){
			$user = DB::table('users')->where('id', $uc->user_id)->first();
			return User::hydrate([$user])->first();
		}else{
			return null;
		}
    }

}