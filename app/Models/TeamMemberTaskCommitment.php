<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMemberTaskCommitment extends Model
{
    use SoftDeletes;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_member_task_commitment';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['team_member_id','task_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function task()
    {
        return $this->belongsTo(MeetingNoteReminder::class,'task_id');
	}

    public function member()
    {
        return $this->belongsTo(TeamMember::class,'team_member_id');
	}

}
