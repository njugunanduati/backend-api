<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingGetStarted extends Model
{

    protected $table = 'onboarding_getting_started';
    protected $fillable = [
        'user_id',
        'step_1',
        'step_1_date',
        'step_1_note',
        'step_2',
        'step_2_date',
        'step_2_note',
        'step_3',
        'step_3_date',
        'step_3_note',
        'step_4',
        'step_4_date',
        'step_4_note',
        'step_5',
        'step_5_date',
        'step_5_note'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

  