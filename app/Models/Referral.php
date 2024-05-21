<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model {

	protected $table = 'referrals';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 
        'last_name', 
        'email', 
        'phone_number', 
        'referred_to', 
        'referred_by'
    ];    
}