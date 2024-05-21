<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashCoachingAppointment extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'flash_coaching_appointments';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['coach_id', 'student_id', 'company_id', 'type', 'meeting_time', 'meeting_url', 'time_zone'];


    public function company() {
		  return $this->belongsTo(Company::class, 'company_id');
    }


    public function coach() {
		  return $this->belongsTo(User::class, 'coach_id');
    }

    public function student() {
		  return $this->belongsTo(User::class, 'student_id');
    }

    public function calendarurls() {
		  return $this->belongsTo(UserCalendarURL::class, 'user_id');
    }

}
