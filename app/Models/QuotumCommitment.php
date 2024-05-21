<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotumCommitment extends Model
{

    protected $table = 'quotum_commitments';

    protected $fillable = [
        'commitment_id',
        'note_id',
        'assessment_id',
        'coach_id',
        'company_id',
        'content_id',
        'type',
    ];

    public function meetingnote()
    {
        return $this->belongsTo(MeetingNote::class, 'note_id');
    }

    public function commitment()
    {
        return $this->belongsTo(MeetingNoteReminder::class, 'commitment_id');
	}

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
	}

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
	}

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
	}

}
