<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Advisor;
use App\Jobs\ProcessEmail;
use App\Models\TrainingAnalytic;
use App\Models\OnboardingSurvey;
use App\Models\OnboardingOngoingActivity;
use App\Models\OnboardingLastActivity;

use App\Models\LicenseeOnboardingGetStarted;
use App\Models\LicenseeOnboardingBuildTeam;
use App\Models\LicenseeOnboardingSupportTeam;

use App\Models\OnboardingRoleplayPrep;
use App\Models\OnboardingRoleplayTrain;
use App\Models\OnboardingRoleplayComp;
use App\Http\Resources\Advisor as AdvisorResource;
use App\Http\Resources\OnboardingUser;
use App\Http\Resources\OnboardingBusinessUser;

use App\Http\Resources\OnboardingGetStartedMini as GetStartedResourceMini;
use App\Http\Resources\OnboardingBusinessGetStartedMini as GetBusinessStartedResourceMini;
use App\Http\Resources\OnboardingRoleplayPrepMini as RoleplayPrepResourceMini;
use App\Http\Resources\OnboardingRoleplayTrainMini as RoleplayTrainResourceMini;
use App\Http\Resources\OnboardingRoleplayCompMini as RoleplayCompResourceMini;

use App\Http\Resources\LicenseeOnboardingGetStarted as GetStartedResource;
use App\Http\Resources\LicenseeOnboardingBuildTeam as BuildTeamResource;
use App\Http\Resources\LicenseeOnboardingSupportTeam as SupportTeamResource;



use App\Http\Resources\OnboardingBusinessGetStarted as GetBusinessStartedResource;
use App\Http\Resources\OnboardingRoleplayPrep as RoleplayPrepResource;
use App\Http\Resources\OnboardingRoleplayTrain as RoleplayTrainResource;
use App\Http\Resources\OnboardingRoleplayComp as RoleplayCompResource;

use App\Http\Resources\UserMini;

use App\Http\Resources\TrainingAnalyticResource as AnalyticsResource;
use App\Http\Resources\TrainingMidAnalyticsResource as MidAnalyticsResource;

use App\Http\Resources\OnboardingSurvey as OnboardingSurveyResource;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class LicenseeOnboardingController extends Controller
{
    use ApiResponses;

    protected $user;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = $request->user();

            return $next($request);
        });
    }

    // Get the last 15 days including today
    private function _getDates(){
        $date = Carbon::now(); 
        $start = $date->copy()->subWeeks(2);
        $i = 1;
        $array = [];
        while($i <= 15){
            $array[] = $start->format('Y-m-d');
            $start->addDay(1);
            $i++;
        }
        
        return $array;
    }

    
    // Map onboarding activity to the last 15 days
    private function getActivity($activity, $dates){
        $results = [];
        foreach ($dates  as $key => $date) {
            $present = in_array($date, $activity);
            $results[$date] = (int) $present;
        }
        return $results;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function analytics(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
                'step' => 'required',
                'status' => 'required',
                'type' => 'required',
                'category' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'step.required' => 'Step is required',
                'status.required' => 'Status is required',
                'type.required' => 'Type is required',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $status = (int) $request->status;
            $type = $request->type;
            $userid = $request->userid;
            $step = $request->step;
            $category = $request->category;
            $date = Carbon::now()->format('Y-m-d');

                if($type == 'A'){

                    LicenseeOnboardingGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step.'_date' => $date
                        ]
                    );

                }else if($type == 'B'){

                    LicenseeOnboardingBuildTeam::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step.'_date' => $date
                        ]
                    );
                }else if($type == 'C'){

                    LicenseeOnboardingSupportTeam::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step.'_date' => $date
                        ]
                    );


                }
            
        
            OnboardingOngoingActivity::updateOrCreate(
                    [
                        'user_id' => $userid,
                        'type' => $type,
                        'step' => $step,
                        'category' => $category
                    ],
                    [
                        'status' => $status,
                        'adate' => $date
                    ]
                );

            OnboardingLastActivity::updateOrCreate(
                    [
                        'user_id' => $userid,
                        'category' => $category
                    ],
                    [
                        'type' => $type,
                        'step' => $step,
                        'status' => $status,
                        'adate' => $date
                    ]
                );

            if($category == 'licensee_onboarding'){
                $get = LicenseeOnboardingGetStarted::where('user_id', $userid)->first();
                $a = ($get)? new GetStartedResource($get) : null;

                $buildT = LicenseeOnboardingBuildTeam::where('user_id', $userid)->first();
                $b = ($buildT)? new BuildTeamResource($buildT) : null;

                $supportT = LicenseeOnboardingSupportTeam::where('user_id', $userid)->first();
                $c = ($supportT)? new SupportTeamResource($supportT) : null;

            }

            $dates = $this->_getDates(); // last 15 days (including today)
            $user = User::findOrFail($userid);

            $activity = $user->onboardingactivity()->where('category', $category)->get();
            
            $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

            $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

            return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'build_team' => $b, 'support_team' => $c, 'activity' => $results ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Licencee Onboarding model not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getanalytics(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
                'category' => 'required'
            ];

            $messages = [
                'userid.required' => 'User ID is required',
                'userid.exists' => 'That user cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $category = $request->category;


                $get = LicenseeOnboardingGetStarted::where('user_id', $request->userid)->first();
                $a = ($get)? new GetStartedResource($get) : null;

                $buildT = LicenseeOnboardingBuildTeam::where('user_id', $request->userid)->first();
                $b = ($buildT)? new BuildTeamResource($buildT) : null;

                $supportT = LicenseeOnboardingSupportTeam::where('user_id', $request->userid)->first();
                $c = ($supportT)? new SupportTeamResource($supportT) : null;

                return $this->showMessage(['getstarted' => $a, 'build_team' => $b, 'support_team' => $c ], 200);


        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Onboarding model not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function note(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
                'type' => 'required',
                'step' => 'required',
                'note' => 'required',
                'category' => 'required',
            ];

            $messages = [
                'userid.required' => 'User ID is required',
                'userid.exists' => 'That user cannot be found',
                'type.required' => 'Type is required',
                'step.required' => 'Step is required',
                'note.required' => 'Note is required',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $step = $request->step;
            $type = $request->type;
            $userid = $request->userid;
            $category = $request->category;
            $note = trim($request->note);

                
                if($type == 'A'){
                
                    $date = Carbon::now()->format('Y-m-d');

                    LicenseeOnboardingGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step.'_note' => $note,
                            $step.'_date' => $date
                        ]
                    );

                }else if($type == 'B'){ 
                    $date = Carbon::now()->format('Y-m-d');

                    LicenseeOnboardingBuildTeam::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step.'_note' => $note,
                            $step.'_date' => $date
                        ]
                    );
                }else if($type == 'C'){ 
                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingRoleplayTrain::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step.'_note' => $note,
                            $step.'_date' => $date
                        ]
                    );
                }
            

                $get = LicenseeOnboardingGetStarted::where('user_id', $request->userid)->first();
                $a = ($get)? new GetStartedResource($get) : null;

                $buildT = LicenseeOnboardingBuildTeam::where('user_id', $request->userid)->first();
                $b = ($buildT)? new BuildTeamResource($buildT) : null;

                $supportT = LicenseeOnboardingSupportTeam::where('user_id', $request->userid)->first();
                $c = ($supportT)? new SupportTeamResource($supportT) : null;

            
            $dates = $this->_getDates(); // last 15 days (including today)
            $user = User::findOrFail($userid);

            $activity = $user->onboardingactivity()->where('category', $category)->get();
            
            $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

            $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

            return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'build_team' => $b, 'support_team' => $c, 'activity' => $results ], 200);
            
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Coach not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function email(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
                'subject' => 'required',
                'message' => 'required',
            ];

            $message_rules = [
                'userid.required' => 'User ID is required',
                'userid.exists' => 'That user cannot be found',
                'subject.required' => 'Subject is required',
                'message.required' => 'Message is required',
            ];

            $validator = Validator::make($request->all(), $rules, $message_rules);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $userid = $request->userid;
            $note = strip_tags(trim($request->message));
            $subject = strip_tags(trim($request->subject));

            $user = User::findOrFail($userid);

            $details = [
                'user' => $this->user,
                'to' => ['tori@focused.com'],
                'client_name' => '',
                'messages' => $note,
                'subject' => $subject, 
                'copy' => ['wally@focused.com', 'adrian@focused.com'],
                'bcopy' => ['pasmailaudit@focused.com'],
            ];

            ProcessEmail::dispatch($details, 'onboarding-email');

            return $this->showMessage("Email sent successfully", 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    
}
