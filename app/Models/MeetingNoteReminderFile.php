<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteReminderFile extends Model
{

    protected $table = 'meeting_notes_reminder_files';

    protected $fillable = [
        'meeting_note_reminder_id',
        'name',
        'url',
        'key',
        'type',
    ];

    /**
     * Set the notes remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trimSpecial(strip_tags($value));
    }

    public function reminder()
    {
        return $this->belongsTo(MeetingNoteReminder::class, 'meeting_note_reminder_id');
    }

}
