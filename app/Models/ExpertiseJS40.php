<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertiseJS40 extends Model
{

    protected $table = 'js_40_expertise';
    protected $fillable = [
        'user_id',
        'strategy',
        'trust',
        'policies',
        'referral',
        'publicity',
        'mail',
        'advertising',
        'scripts',
        'initialclose',
        'followupclose',
        'formercustomers',
        'appointments',
        'purchase',
        'longevity'
    ];
    
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
