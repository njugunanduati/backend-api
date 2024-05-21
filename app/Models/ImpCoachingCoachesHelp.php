<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingCoachesHelp extends Model {

	protected $table = 'imp_coaching_coaches_help';

    protected $fillable = [
        'coaching_id',
        'notes',
        'done'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
