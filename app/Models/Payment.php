<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model {

	protected $table = 'payments';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'coach_id',
        'student_id',
        'group_id',
        'paid',
        'amount',
        'paid_on',
        'type',
        'log',
    ];


	public function student() {
        return $this->belongsTo(User::class,'student_id');
    }
    
	public function coach() {
        return $this->belongsTo(User::class,'coach_id');
    }
    
	public function group() {
        return $this->belongsTo(Group::class,'group_id');
    }

}
