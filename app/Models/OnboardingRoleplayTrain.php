<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingRoleplayTrain extends Model
{

    protected $table = 'onboarding_roleplay_training';
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
        'step_3_note'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

  