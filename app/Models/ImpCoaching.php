<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpCoaching extends Model {

	protected $table = 'imp_coaching';

    protected $fillable = [
        'assessment_id',
        'nid',
        'path',
    ];

	public function assessment() {
        return $this->belongsTo(Assessment::class,'assessment_id');
    }
    
    public function coachingActions() {
        return $this->hasMany(ImpCoachingActions::class, 'coaching_id');
    }
    
    public function coachingActionsMustComplete() {
        return $this->hasMany(ImpCoachingActionsMustComplete::class, 'coaching_id');
    }

    public function coachingActionsNeedComplete() {
        return $this->hasMany(ImpCoachingActionsNeedComplete::class, 'coaching_id');
    }

    public function coachingBiggestChallenges() {
        return $this->hasMany(ImpCoachingBiggestChallenges::class, 'coaching_id');
    }

    public function coachingBiggestWins() {
        return $this->hasMany(ImpCoachingBiggestWins::class, 'coaching_id');
    }

    public function coachingCoachesHelp() {
        return $this->hasMany(ImpCoachingCoachesHelp::class, 'coaching_id');
    }

    public function coachingWeeklyRevenue() {
        return $this->hasMany(ImpCoachingWeeklyRevenue::class, 'coaching_id');
    }

    public function coachingWeeklyLeads() {
        return $this->hasMany(ImpCoachingWeeklyLead::class, 'coaching_id');
    }

    public function coachingWeeklyAppointments() {
        return $this->hasMany(ImpCoachingWeeklyAppointment::class, 'coaching_id');
    }

    public function coachingNotes() {
        return $this->hasMany(ImpCoachingNote::class, 'coaching_id');
    }

}
