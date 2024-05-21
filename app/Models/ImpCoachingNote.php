<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoachingNote extends Model {

	protected $table = 'imp_coaching_notes';

    protected $fillable = [
        'coaching_id',
        'notes'
    ];

	public function coaching() {
        return $this->belongsTo(ImpCoaching::class,'coaching_id');
	}

}
