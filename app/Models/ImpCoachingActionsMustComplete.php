<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingActionsMustComplete extends Model {

	protected $table = 'imp_coaching_actions_must_complete';

    protected $fillable = [
        'coaching_id',
        'notes',
        'done'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
