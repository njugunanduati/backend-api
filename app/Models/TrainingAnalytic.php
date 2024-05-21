<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingAnalytic extends Model
{
    // use SoftDeletes;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_training_analysis';
    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'user_id', 'group_id', 'user_group_id', 'video_id', 'video_name', 'video_progress', 'video_time_watched', 'video_length', 'quiz_correct_answers', 'quiz_total_questions', 'quiz_score', 'quiz_answers', 'quiz_url'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
	}
}
