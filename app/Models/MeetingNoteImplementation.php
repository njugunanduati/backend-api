<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingNoteImplementation extends Model
{

    protected $table = 'meeting_notes_implementations';

    protected $fillable = [
        'assessment_id',
        'company_id',
        'path',
        'start_date',
        'time',
        'archived',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function actions() {
        return $this->hasMany(MeetingNoteImplementationAction::class, 'implementation_id');
    }
}
