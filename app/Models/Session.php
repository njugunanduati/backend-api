<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model {

    use SoftDeletes;

	protected $table = 'sessions';

    protected $fillable = [
        'module_name',
        'meeting_title',
        'assessment_id',
        'current_revenue',
        'meeting_notes',
        'client_action_steps',
        'coach_action_steps',
        'next_meeting_date',
        'next_meeting_location',
        'meeting_keywords',
    ];

	public $timestamps = true;

	public function assessment() {
		return $this->belongsTo('App\Models\Assessment');
	}

    /**
     * Set the meeting_title. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setMeetingTitleAttribute($value)
    {
        $this->attributes['meeting_title'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the meeting_notes. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setMeetingNotesAttribute($value)
    {
        $this->attributes['meeting_notes'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the next_meeting_location. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNextMeetingLocationAttribute($value)
    {
        $this->attributes['next_meeting_location'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the meeting_keywords. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNextMeetingKeywordsAttribute($value)
    {
        $this->attributes['meeting_keywords'] = trimSpecial(strip_tags($value));
    }

}
