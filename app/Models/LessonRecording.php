<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonRecording extends Model {

	protected $table = 'lesson_recordings';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['owner_id', 'lesson_id', 'user_group_id', 'video_url' ];

	public function lesson() {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function usergroup() {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'owner_id');
    }

}
