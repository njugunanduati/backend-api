<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupCoachingLessonMeetingSetting extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gc_lesson_meeting_settings';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lesson_meeting_id',
        'three_days_coach_reminder',
        'three_days_reminder',
        'one_day_reminder',
        'one_hour_reminder',
        'three_min_before_reminder',
        'ten_min_after_reminder',
        'one_day_after_reminder'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    //protected $hidden = [''];

    public function lessonmeeting()
    {
        return $this->belongsTo(GroupCoachingLessonMeeting::class, 'lesson_meeting_id');
    }
}
