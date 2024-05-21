<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advisor extends Model
{

    use SoftDeletes;
    protected $table = 'onboarding_advisors';
    protected $fillable = ['user_id', 'email_address', 'category', 'time_zone', 'calendar_link', 'image_url'];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
