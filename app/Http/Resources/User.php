<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Role as RoleResource;
use App\Http\Resources\TrainingAccess as TrainingResource;
use App\Http\Resources\AssessmentMini as AssessmentResource;
use App\Http\Resources\UserCalendarURL as CalendarResource;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $assessments = $this->assessments()->get();
        $status = $this->status()->first();
        $createdBy = $this->createdBy()->first();

        return
            [
                'id' => $this->id,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'secondary_email' => $this->secondary_email,
                'company' => $this->company,
                'website' => $this->website,
                'role_id' => $this->role_id,
                'licensee_access' => $this->licensee_access,
                'onboarding' => $this->onboarding,
                'onboarding_status' => $this->onboarding_status,
                'business_onboarding_status' => $this->business_onboarding_status,
                'tos_check' => $this->tos_check,
                'show_tour' => $this->show_tour,
                'prospects_notify' => $this->prospects_notify,
                'trainings' => new TrainingResource($this->trainingAccess),
                'role' => new RoleResource($this->role),
                'assessments' => AssessmentResource::collection($assessments),
                'companies' => $this->companies()->get(),
                'modulesets' => $this->module_sets()->get()->sortBy('order')->toArray(),
                'title'=> ($this->title)? $this->title: '',
                'profile_pic'=> ($this->profile_pic)? $this->profile_pic: '',
                'location'=> ($this->location)? $this->location: '',
                'time_zone'=> ($this->time_zone)? $this->time_zone : '',
                'phone_number'=> ($this->phone_number)? $this->phone_number: '',
                'birthday'=> ($this->birthday)? $this->birthday: '',
                'facebook'=> ($this->facebook)? $this->facebook: '',
                'twitter'=> ($this->twitter)? $this->twitter: '',
                'linkedin'=> ($this->linkedin)? $this->twitter: '',
                'meeting_url'=> ($this->meeting_url)? $this->meeting_url: '',
                'meetingurls' => isset($this->meetingurls)? new MeetingsResource($this->meetingurls) : null,
                'status' => ($status)? $status : 1,
                'created_by' => ($createdBy) ? $createdBy: '',
                'created_at' => ($this->created_at)?$this->created_at:'',
                'calendarurls' => isset($this->calendarurls)? new CalendarResource($this->calendarurls) : null
            ];
    }
}