<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingWeeklyLead extends Model {

	protected $table = 'imp_coaching_weekly_leads';

    protected $fillable = [
        'coaching_id',
        'weekly_leads'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
