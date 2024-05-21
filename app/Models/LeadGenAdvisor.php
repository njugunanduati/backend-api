<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadGenAdvisor extends Model
{

    protected $table = 'leadgen_advisors';
    protected $fillable = ['user_id', 'email_address', 'category', 'time_zone', 'calendar_link', 'image_url'];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
