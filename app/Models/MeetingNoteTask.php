<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteTask extends Model
{

    protected $table = 'meeting_notes_coach_tasks';

    protected $fillable = [
        'meeting_note_id',
        'note',
    ];

    /**
     * Set the note remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setNoteAttribute($value)
    {
        $this->attributes['note'] = trimSpecial(strip_tags($value));
    }

    public function meetingnote()
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_note_id');
    }
}
