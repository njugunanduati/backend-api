<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberGroupLesson extends Model
{

    protected $table = 'member_group_lesson';
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
        'invited_by',
        'lesson_paused',
        'lesson_length',
        'lesson_order',
        'lesson_access',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function group()
    {
        return $this->belongsTo(UserGroup::class);
    }
}
