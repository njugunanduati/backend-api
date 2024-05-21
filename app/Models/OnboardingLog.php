<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingLog extends Model
{

    protected $table = 'onboarding_email_log';
    protected $fillable = ['type', 'status', 'sent_to', 'manager', 'advisor'];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
