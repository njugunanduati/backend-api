<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingRoleplayPrep extends Model
{

    protected $table = 'onboarding_roleplay_preparation';
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
        'step_5_note',
        'step_6',
        'step_6_date',
        'step_6_note',
        'step_7',
        'step_7_date',
        'step_7_note'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

  