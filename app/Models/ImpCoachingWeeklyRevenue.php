<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingWeeklyRevenue extends Model {

	protected $table = 'imp_coaching_weekly_revenue';

    protected $fillable = [
        'coaching_id',
        'weekly_revenue'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
