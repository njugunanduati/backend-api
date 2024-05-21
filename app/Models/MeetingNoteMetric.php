<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteMetric extends Model
{

    protected $table = 'meeting_notes_metrics';

    protected $fillable = [
        'meeting_note_id',
        'setting_id',
        'entry_date',
        'value',
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
