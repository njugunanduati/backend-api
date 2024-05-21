<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupCoachingEmailNotification extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gc_email_notification';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lesson_id', 'three_days_before_coach', 'three_days_before_coach_sub', 'three_days_before', 'three_days_before_sub', 'one_day_before', 'one_day_before_sub', 'one_hour_before', 'one_hour_before_sub',
        'three_min_after', 'three_min_after_sub', 'ten_min_after', 'ten_min_after_sub', 'one_day_after', 'one_day_after_sub'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    //protected $hidden = [''];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Set the three_days_before_coach. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setThreeDaysBeforeCoachAttribute($value)
    {
        $this->attributes['three_days_before_coach'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the three_days_before_coach_sub. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setThreeDaysBeforeCoachSubAttribute($value)
    {
        $this->attributes['three_days_before_coach_sub'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the three_days_before. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setThreeDaysBeforeAttribute($value)
    {
        $this->attributes['three_days_before'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the three_days_before_sub. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setThreeDaysBeforeSubAttribute($value)
    {
        $this->attributes['three_days_before_sub'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the one_day_before. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneDayBeforeAttribute($value)
    {
        $this->attributes['one_day_before'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the one_day_before_sub. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneDayBeforeSubAttribute($value)
    {
        $this->attributes['one_day_before_sub'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the one_hour_before. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneHourBeforeAttribute($value)
    {
        $this->attributes['one_hour_before'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the one_hour_before_sub. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneHourBeforeSubAttribute($value)
    {
        $this->attributes['one_hour_before_sub'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the one_day_after. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneDayAfterAttribute($value)
    {
        $this->attributes['one_day_after'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the one_day_after_sub. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setOneDayAfterSubAttribute($value)
    {
        $this->attributes['one_day_after_sub'] = trimSpecial(strip_tags($value));
    }
}
