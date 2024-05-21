<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notifications';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'title', 'description'];

    public function analysis() {
        return $this->hasMany(NotificationAnalysis::class, 'notification_id');
    }

    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trimSpecial(strip_tags($value));
    }

}
