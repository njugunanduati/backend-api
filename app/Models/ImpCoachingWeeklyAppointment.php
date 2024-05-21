<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingWeeklyAppointment extends Model {

	protected $table = 'imp_coaching_weekly_appointments';

    protected $fillable = [
        'coaching_id',
        'weekly_appointments'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
