<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingActionsNeedComplete extends Model {

	protected $table = 'imp_coaching_actions_need_to_complete';

    protected $fillable = [
        'coaching_id',
        'notes'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
