<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteImplementationAction extends Model
{

    protected $table = 'meeting_notes_implementation_actions';

    protected $fillable = [
        'implementation_id',
        'aid',
        'complete',
        'deadline'
    ];

    public function implementation()
    {
        return $this->belongsTo(MeetingNoteImplementation::class, 'implementation_id');
    }

    public function teammember()
    {
        return $this->hasOne(TeamMemberTaskImplementation::class, 'task_id');
	}
}
