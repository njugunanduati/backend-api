<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashCoachingAccess extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'flash_coaching_access';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['coach_id', 'student_id', 'access'];

    public function coach() {
		  return $this->belongsTo(User::class, 'coach_id');
    }

    public function student() {
		  return $this->belongsTo(User::class, 'student_id');
    }

}
