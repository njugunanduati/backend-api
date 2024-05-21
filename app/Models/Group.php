<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $table = 'groups';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'group_img',
        'owner',
        'price',
        'status',
        'payment_frequency',
        'template',
        'intro_image',
        'intro_video',
        'template_video',
        'active',
        'meets_on',
        'meeting_time',
        'time_zone',
        'meeting_url'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    //protected $hidden = [''];


    public function __toString()
    {
        return $this->title;
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
     * Set the slug. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the group's description. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the group's meeting url. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setMeetingUrlAttribute($value)
    {
        $this->attributes['meeting_url'] = trimSpecial(strip_tags($value));
    }
}
