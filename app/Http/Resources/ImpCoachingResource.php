<?php

namespace App\Http\Resources;

use App\Models\ImpCoaching;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ImpCoachingActions as ActionsResource;
use App\Http\Resources\ImpCoachingActionsMustComplete as MustResource;
use App\Http\Resources\ImpCoachingActionsNeedComplete as NeedResource;
use App\Http\Resources\ImpCoachingBiggestChallenges as ChallengesResource;
use App\Http\Resources\ImpCoachingBiggestWins as WinsResource;
use App\Http\Resources\ImpCoachingCoachesHelp as HelpResource;
use App\Http\Resources\ImpCoachingNote as NoteResource;
use App\Http\Resources\ImpCoachingWeeklyRevenue as RevenueResource;
use App\Http\Resources\ImpCoachingWeeklyLead as LeadResource;
use App\Http\Resources\ImpCoachingWeeklyAppointment as AppointmentResource;

class ImpCoachingResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? null,
            'assessment_id'  => $this->assessment_id ?? null, 
            'nid'  => $this->nid ?? null, 
            'path'  => $this->path ?? null, 
            'revenue'  => isset($this->coachingWeeklyRevenue)? RevenueResource::collection($this->coachingWeeklyRevenue) : [],
            'leads'  => isset($this->coachingWeeklyLeads)? LeadResource::collection($this->coachingWeeklyLeads) : [],
            'appointments'  => isset($this->coachingWeeklyAppointments)? AppointmentResource::collection($this->coachingWeeklyAppointments) : [],
            'notes'  => isset($this->coachingNotes)? NoteResource::collection($this->coachingNotes) : [],
            'actions'  => isset($this->coachingActions)? ActionsResource::collection($this->coachingActions) : [],
            'must'  => isset($this->coachingActionsMustComplete)? MustResource::collection($this->coachingActionsMustComplete) : [],
            'need'  => isset($this->coachingActionsNeedComplete)? NeedResource::collection($this->coachingActionsNeedComplete) : [],
            'challenges'  => isset($this->coachingBiggestChallenges)? ChallengesResource::collection($this->coachingBiggestChallenges) : [],
            'wins'  => isset($this->coachingBiggestWins)? WinsResource::collection($this->coachingBiggestWins) : [],
            'help'  => isset($this->coachingCoachesHelp)? HelpResource::collection($this->coachingCoachesHelp) : [],
            'created_at'  => isset($this->created_at)? formatDate($this->created_at) : null,
            'updated_at'  => isset($this->updated_at)? formatDate($this->updated_at) : null,
            'previous'  => isset($this->previous)? $this->previous : null,
            ];
    }
}
