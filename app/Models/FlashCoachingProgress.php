<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashCoachingProgress extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'flash_coaching_progress';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['coach_id', 'student_id', 'company_id', 'assessment_id', 'lesson_id', 'path', 'access', 'progress'];


    public function company() {
		  return $this->belongsTo(Company::class, 'company_id');
    }


    public function coach() {
		  return $this->belongsTo(User::class, 'coach_id');
    }

    public function student() {
		  return $this->belongsTo(User::class, 'student_id');
    }

    public function assessment() {
		  return $this->belongsTo(Assessment::class, 'assessment_id');
    }

}
