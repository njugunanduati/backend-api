<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUser extends Model {

	protected $table = 'company_user';
    public $timestamps = false;
    protected $fillable = [ 'company_id', 'user_id' ];
    
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
	}

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
	}

}
