<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingSurvey extends Model
{

    protected $table = 'onboarding_surveys';
    protected $fillable = [
        'type',
        'user_id',
        'survey_answers',
        'survey_url'
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

  