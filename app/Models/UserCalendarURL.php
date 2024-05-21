<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCalendarURL extends Model
{

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_calendar_urls';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'fifteen_url', 'thirty_url', 'forty_five_url', 'sixty_url'];

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }
}
