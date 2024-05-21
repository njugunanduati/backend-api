<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupCoachingPausedSession extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gc_paused_sessions';
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
        'start_date',
        'end_date',
        'time_zone',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */


    public function coach()
    {
        return $this->hasOne(User::class, 'user_id');
    }
    public function group()
    {
        return $this->hasOne(UserGroup::class, 'group_id');
    }
    public function lesson()
    {
        return $this->hasOne(Lesson::class, 'lesson_id');
    }
}
