<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpSettingsMeeting extends Model {

	protected $table = 'imp_settings_meetings';

    protected $fillable = [
        'settings_id',
        'meeting_day',
        'meeting_time',
        'time_zone',
    ];

	public function settings() {
        return $this->belongsTo(ImpSettings::class,'settings_id');
    }

}
