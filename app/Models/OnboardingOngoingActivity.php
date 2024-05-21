<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingOngoingActivity extends Model
{

    protected $table = 'onboarding_ongoing_activity';
    protected $fillable = [
        'user_id',
        'type',
        'step',
        'category',
        'adate',
        'status'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

  