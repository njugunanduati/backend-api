<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospect extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'prospects';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'name', 'url', 'key', 'processed'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    

    public function user()
    {
        return $this->hasOne(User::class,'user_id');
	}
    /**
     * Set the name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trimSpecial(strip_tags($value));
    }

}
