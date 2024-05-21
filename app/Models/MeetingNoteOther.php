<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteOther extends Model
{

    protected $table = 'meeting_notes_others';

    protected $fillable = [
        'meeting_note_id',
        'setting_id',
        'note',
    ];

    public function meetingnote()
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_note_id');
    }

    public function settings()
    {
        return $this->belongsTo(MeetingNoteSetting::class, 'setting_id');
    }
}
