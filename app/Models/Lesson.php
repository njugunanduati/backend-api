<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{

    protected $table = 'lessons';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'slug', 'short_desc', 'full_desc', 'lesson_img', 'lesson_video', 'quiz_url', 'published',
        'free_lesson', 'price'
    ];

    public function memberGroupLesson()
    {
        return $this->hasMany(MemberGroupLesson::class);
    }

    public function gclessonNotifications()
    {
        return $this->hasMany(GroupCoachingEmailNotification::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function customGroupLessons()
    {
        return $this->hasMany(CustomGroupLesson::class);
    }
    public function lessonRecordings()
    {
        return $this->hasMany(LessonRecording::class);
    }

     /**
     * Set the title. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trimSpecial(strip_tags($value));
		
    }
     /**
     * Set the short_desc. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setShortDescAttribute($value)
    {
        $this->attributes['short_desc'] = trimSpecial(strip_tags($value));
		
    }
     /**
     * Set the full_desc. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setFullDescAttribute($value)
    {
        $this->attributes['full_desc'] = trimSpecial(strip_tags($value));
		
    }

    
}
