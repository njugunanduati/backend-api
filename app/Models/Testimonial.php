<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use SoftDeletes;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'testimonials';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['client', 'coach', 'rating', 'testimonial'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Set the last_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setTestimonialAttribute($value)
    {
        $this->attributes['testimonial'] = trimSpecial(strip_tags($value));
    }

}
