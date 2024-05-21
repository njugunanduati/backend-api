<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpSettings extends Model {

	protected $table = 'imp_settings';

    protected $fillable = [
        'assessment_id',
        'three_days',
        'one_day',
        'one_hour',
        'meeting_sequence',
        'zoom_url',
        'meeting_type',
        'phone_number',
        'meeting_address',
    ];

	public function assessment(){
        return $this->belongsTo(Assessment::class,'assessment_id');
    }

    public function meetings(){
	    return $this->hasMany(ImpSettingsMeeting::class, 'settings_id');
	}

}
