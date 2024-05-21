<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingActions extends Model {

	protected $table = 'imp_coaching_actions';

    protected $fillable = [
        'coaching_id',
        'aid',
        'complete',
        'notes'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
