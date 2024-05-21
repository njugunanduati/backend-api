<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNote extends Model
{

    protected $table = 'meeting_notes';

    protected $fillable = [
        'coaching',
        'user_id',
        'company_id',
        'notes',
        'closed',
        'meeting_time',
        'next_meeting_time',
        'meeting_url',
        'time_zone',

    ];

    /**
     * Set the notes remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = trimSpecial(strip_tags($value));
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function others()
    {
        return $this->hasMany(MeetingNoteOther::class, 'meeting_note_id');
    }

    public function metrics()
    {
        return $this->hasMany(MeetingNoteMetric::class, 'meeting_note_id');
    }

    public function tasks()
    {
        return $this->hasMany(MeetingNoteTask::class, 'meeting_note_id');
    }

    public function reminder()
    {
        return $this->hasMany(MeetingNoteReminder::class, 'meeting_note_id');
    }
}
