<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupCoachingLessonMeeting extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gc_lesson_meetings';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'group_id',
        'lesson_id',
        'lesson_order',
        'invited_by',
        'meeting_time',
        'time_zone',
        'meeting_url',
        'coach_notes',
        'coach_action_steps',
        'close_leson',
        'lesson_paused',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    //protected $hidden = [''];

    public function settings()
    {
        return $this->hasOne(GroupCoachingLessonMeetingSetting::class, 'lesson_meeting_id');
    }

     /**
     * Set the coach notes. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setCoachNotesAttribute($value)
    {
        $this->attributes['coach_notes'] = trimSpecial(strip_tags($value));
		
    }
     /**
     * Set the meeting url. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setMeetingUrlAttribute($value)
    {
        $this->attributes['meeting_url'] = trimSpecial(strip_tags($value));
		
    }

    public function mgl()
    {
        return DB::table('member_group_lesson')
            ->where('user_id', $this->user_id)
            ->where('group_id', $this->group_id)
            ->where('lesson_id', $this->lesson_id)
            ->first();
    }
}
