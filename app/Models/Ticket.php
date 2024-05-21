<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tickets';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email', 'priority', 'type', 'subject', 'description'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function __toString() {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function __trimSpecial($value)
    {
        $firststr = substr($value, 0, 1);
        $regex = preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $firststr);

        if ($regex) {
            $str1 = substr($value, 1);
            return $str1;
        }
        return $value;
    }

    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the last_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the subject. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setSubjectAttribute($value)
    {
        $this->attributes['subject'] = trimSpecial(strip_tags($value));
    }

     /**
     * Set the description. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trimSpecial(strip_tags($value));
    }
}
