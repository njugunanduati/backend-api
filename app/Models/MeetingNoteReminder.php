<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteReminder extends Model
{

    protected $table = 'meeting_notes_reminder_tasks';

    protected $fillable = [
        'meeting_note_id',
        'type',
        'status',
        'note',
        'reminder_date',
        'reminder_time',
        'send_reminder',
        'time_zone',
    ];

    public function meetingnote()
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_note_id');
    }

    public function teammember()
    {
        return $this->hasOne(TeamMemberTaskCommitment::class, 'task_id');
	}

}
