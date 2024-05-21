<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Advisor;
use App\Jobs\ProcessEmail;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\UserMini;
use App\Models\OnboardingSurvey;
use App\Models\TrainingAnalytic;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\OnboardingGetStarted;
use App\Http\Resources\OnboardingUser;
use App\Models\OnboardingLastActivity;
use App\Models\OnboardingRoleplayComp;
use App\Models\OnboardingRoleplayPrep;

use App\Models\OnboardingRoleplayTrain;
use App\Models\OnboardingOngoingActivity;
use App\Models\LicenseeOnboardingBuildTeam;
use App\Models\LicenseeOnboardingGetStarted;
use App\Models\OnboardingBusinessGetStarted;

use App\Models\LicenseeOnboardingSupportTeam;
use App\Http\Resources\LicenseeOnboardingUser;
use App\Http\Resources\OnboardingBusinessUser;
use App\Http\Resources\Advisor as AdvisorResource;

use App\Http\Resources\LicenseeOnboardingGetStarted as LicenseeGetStartedResource;
use App\Http\Resources\LicenseeOnboardingBuildTeam as BuildTeamResource;
use App\Http\Resources\LicenseeOnboardingSupportTeam as SupportTeamResource;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Resources\OnboardingGetStarted as GetStartedResource;

use App\Http\Resources\OnboardingSurvey as OnboardingSurveyResource;
use App\Http\Resources\TrainingAnalyticResource as AnalyticsResource;

use App\Http\Resources\OnboardingRoleplayComp as RoleplayCompResource;

use App\Http\Resources\OnboardingRoleplayPrep as RoleplayPrepResource;
use App\Http\Resources\OnboardingRoleplayTrain as RoleplayTrainResource;
use App\Http\Resources\OnboardingGetStartedMini as GetStartedResourceMini;
use App\Http\Resources\TrainingMidAnalyticsResource as MidAnalyticsResource;
use App\Http\Resources\OnboardingRoleplayCompMini as RoleplayCompResourceMini;
use App\Http\Resources\OnboardingRoleplayPrepMini as RoleplayPrepResourceMini;
use App\Http\Resources\OnboardingRoleplayTrainMini as RoleplayTrainResourceMini;
use App\Http\Resources\OnboardingBusinessGetStarted as GetBusinessStartedResource;
use App\Http\Resources\OnboardingBusinessGetStartedMini as GetBusinessStartedResourceMini;

class OnboardingController extends Controller
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
    private function _getDates()
    {
        $date = Carbon::now();
        $start = $date->copy()->subWeeks(2);
        $i = 1;
        $array = [];
        while ($i <= 15) {
            $array[] = $start->format('Y-m-d');
            $start->addDay(1);
            $i++;
        }

        return $array;
    }


    // Map onboarding activity to the last 15 days
    private function getActivity($activity, $dates)
    {
        $results = [];
        foreach ($dates as $key => $date) {
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
    public function email(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
                'subject' => 'required',
                'note' => 'required',
            ];

            $message_rules = [
                'userid.required' => 'User ID is required',
                'userid.exists' => 'That user cannot be found',
                'subject.required' => 'Subject is required',
                'note.required' => 'Note is required',
            ];

            $validator = Validator::make($request->all(), $rules, $message_rules);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $userid = $request->userid;
            $note = strip_tags(trim($request->note));
            $subject = strip_tags(trim($request->subject));

            $user = User::findOrFail($userid);

            $messages = $note;

            $details = [
                'user' => $this->user,
                'to' => trim($user->email),
                'client_name' => '',
                'messages' => $messages,
                'subject' => $subject,
                'copy' => [$this->user->email],
                'bcopy' => ['pasmailaudit@focused.com'],
            ];

            ProcessEmail::dispatch($details, 'onboarding-email');

            return $this->showMessage("Email sent successfully", 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
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

            if ($category == 'pas') {

                if ($type == 'A') {

                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step . '_note' => $note,
                            $step . '_date' => $date
                        ]
                    );

                } else if ($type == 'B') {
                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingRoleplayPrep::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step . '_note' => $note,
                            $step . '_date' => $date
                        ]
                    );
                } else if ($type == 'C') {
                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingRoleplayTrain::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step . '_note' => $note,
                            $step . '_date' => $date
                        ]
                    );
                } else if ($type == 'D') {
                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingRoleplayComp::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step . '_note' => $note,
                            $step . '_date' => $date
                        ]
                    );
                }

            } else {
                if ($type == 'A') {

                    $date = Carbon::now()->format('Y-m-d');

                    OnboardingBusinessGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step . '_note' => $note,
                            $step . '_date' => $date
                        ]
                    );

                }
            }

            if ($category == 'pas') {
                $get = OnboardingGetStarted::where('user_id', $userid)->first();
                $a = ($get) ? new GetStartedResource($get) : null;

                $prep = OnboardingRoleplayPrep::where('user_id', $userid)->first();
                $b = ($prep) ? new RoleplayPrepResource($prep) : null;

                $train = OnboardingRoleplayTrain::where('user_id', $userid)->first();
                $c = ($train) ? new RoleplayTrainResource($train) : null;

                $comp = OnboardingRoleplayComp::where('user_id', $userid)->first();
                $d = ($comp) ? new RoleplayCompResource($comp) : null;

            } else {
                $get = OnboardingBusinessGetStarted::where('user_id', $userid)->first();
                $a = ($get) ? new GetBusinessStartedResource($get) : null;
            }

            $dates = $this->_getDates(); // last 15 days (including today)
            $user = User::findOrFail($userid);

            $activity = $user->onboardingactivity()->where('category', $category)->get();

            $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

            $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

            if ($category == 'pas') {
                return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'preparation' => $b, 'training' => $c, 'completion' => $d, 'activity' => $results], 200);
            } else {
                return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'activity' => $results], 200);
            }

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
    public function managerallocate(Request $request)
    {
        try {

            $rules = [
                'manager_id' => 'required|exists:users,id',
                'coach_id' => 'required|exists:users,id',
                'category' => 'required',
            ];

            $messages = [
                'manager_id.required' => 'Manager ID is required',
                'manager_id.exists' => 'That manager user cannot be found',
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'That coach user cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $category = $request->category;
            $coach_id = $request->coach_id;

            $coach = User::findOrFail($coach_id);
            $coach->manager = (int) $request->manager_id;
            $coach->save();
            $coach = $coach->refresh();

            if ($category == 'pas') {
                $transform = new OnboardingUser($coach);
            } else {
                $transform = new OnboardingBusinessUser($coach);
            }

            return $this->showMessage($transform, 200);
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
    public function managersearch(Request $request)
    {
        try {

            $rules = [
                'keyword' => 'required',
            ];

            $messages = [
                'keyword.required' => 'Keyword is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $query = trim($request->keyword);

            if (!empty($query)) {
                // Dont include student accounts
                $users = DB::table('users')
                    ->where(function ($a) use ($query) {
                        $a->where('first_name', 'LIKE', '%' . $query . '%')->orWhere('last_name', 'LIKE', '%' . $query . '%');
                    })->where(function ($b) {
                    $b->where('role_id', '<>', 10);
                })->orderBy('id')->limit(20)->get();

                $users = User::hydrate($users->toArray());

                $transform = UserMini::collection($users);
                return $this->showMessage($transform, 200);
            }

            return $this->showMessage(null, 200);
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
    public function advisors(Request $request)
    {
        try {

            $rules = [
                'category' => 'required',
            ];

            $messages = [
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $category = $request->category;

            $advisors = Advisor::where('category', $category)->get();
            $transform = AdvisorResource::collection($advisors);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Advisors not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function advisor(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'category' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $category = $request->category;

            $user = User::findOrFail($user_id);

            if ($category == 'pas') {
                $type = $user->advisor;
            }else if($category == 'licensee_onboarding'){
                $type = $user->licensee_onboarding_advisor;
            }else{
                $type = $user->business_advisor;
            }

            if ($type) {
                $advisor = Advisor::where('user_id', $type)->first();
                $transform = new AdvisorResource($advisor);
                return $this->showMessage($transform, 200);
            }

            return $this->showMessage(null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Advisors not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveadvisor(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'advisor_id' => 'required|exists:users,id',
                'category' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'advisor_id.required' => 'Advisor ID is required',
                'advisor_id.exists' => 'That advisor user cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $advisor_id = $request->advisor_id;
            $category = $request->category;

            $user = User::findOrFail($user_id);

            if ($category == 'pas') {
                $user->advisor = $advisor_id;
            }
            else if($category == 'licensee_onboarding'){
                $user->licensee_onboarding_advisor = $advisor_id;
            }else{
                $user->business_advisor = $advisor_id;
            }

            $user->save();

            $user = $user->refresh();

            if ($category == 'pas') {
                if ($user->advisor) {
                    $advisor = Advisor::where('user_id', $user->advisor)->first();
                    $transform = new AdvisorResource($advisor);
                    return $this->showMessage($transform, 200);
                }
            }
            else if($category == 'licensee_onboarding'){
                if($user->licensee_onboarding_advisor){
                    $licensee_onboarding_advisor = Advisor::where('user_id', $user->licensee_onboarding_advisor)->first();
                    $transform = new AdvisorResource($licensee_onboarding_advisor);
                    return $this->showMessage($transform, 200);
                }
            }else{
                if($user->business_advisor){
                    $advisor = Advisor::where('user_id', $user->business_advisor)->first();
                    $transform = new AdvisorResource($advisor);
                    return $this->showMessage($transform, 200);
                }
            }

            return $this->showMessage(null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Advisors not found', 400);
        }
    }


    /**
     * Send an email notification to the coach
     * To let them know they are almost done
     *
     * @param  $status, $user
     *
     */
    private function sendNotification($status, $user)
    {
        if ($status == 1) {
            // Allow deep dive 40

            $attached = $user->module_sets()->get()->pluck('module_set_id')->toArray();

            if (!in_array(6, $attached)) {
                $user->module_sets()->attach(6);
            }

            // Give access to Lead Gen training
            $array = ['training_lead_gen' => 1];
            $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);

            // Send a congratulations email to coach
            $subject = "One more step - You're almost finished!";

            $messages[] = 'Well Done! You have completed your Roleplay segment of your onboarding process. You are now ready to start watching the Lead Generation Training. It will be in your dashboard in the Training section.';
            $messages[] = "Your next Steps are to meet with your Onboarding Advisor for your final meeting. They will give you the information you need for Alan Ulsh's calendar link and a few other important details.";
            $messages[] = 'Also, please take the time to fill out the survey of your onboarding experience by clicking on the link in your onboarding portal. We value your opinion especially as this is the Beta Test phase of this new process.';
            $messages[] = "You're almost done!";

            $details = [
                'user' => $this->user,
                'to' => $user->email,
                'client_name' => $user->first_name,
                'messages' => $messages,
                'subject' => $subject,
                'copy' => [],
                'bcopy' => [],
            ];

            ProcessEmail::dispatch($details, 'onboarding');

        } else {
            // Disallow deep dive 40
            $user->module_sets()->detach(6);
        }
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

            if ($category == 'pas') {
                if ($type == 'A') {

                    OnboardingGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step . '_date' => $date
                        ]
                    );

                } else if ($type == 'B') {

                    OnboardingRoleplayPrep::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step . '_date' => $date
                        ]
                    );
                } else if ($type == 'C') {

                    OnboardingRoleplayTrain::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step . '_date' => $date
                        ]
                    );


                } else if ($type == 'D') {

                    OnboardingRoleplayComp::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step . '_date' => $date
                        ]
                    );

                    // if on step_3 mark the status on the user model
                    if ($step == 'step_3') {
                        $user = User::findOrFail($userid);
                        $user->onboarding_status = $status;
                        $user->save();

                        // Give access to DeedDive 40
                        $attached = $user->module_sets()->get()->pluck('module_set_id')->toArray();

                        if (!in_array(6, $attached)) {
                            $user->module_sets()->attach(6);
                        }

                        // Give access to Lead Gen training
                        $array = ['training_lead_gen' => 1,'lead_generation' => 1];
                        $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);
                    }

                }
            } else {
                if ($type == 'A') {

                    OnboardingBusinessGetStarted::updateOrCreate(
                        [
                            'user_id' => $userid,
                        ],
                        [
                            $step => $status,
                            $step . '_date' => $date
                        ]
                    );

                    $user = User::findOrFail($userid);

                    if ($user->business_advisor == null) {
                        $user->business_advisor = 567; // 567 is Chris Kling who is the only website advisor
                        $user->save();
                    }

                    // if on step_7 mark the status on the user model
                    if ($step == 'step_7') {
                        $user->business_onboarding_status = $status;
                        $user->save();
                    }

                }
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

            if ($category == 'pas') {
                $get = OnboardingGetStarted::where('user_id', $userid)->first();
                $a = ($get) ? new GetStartedResource($get) : null;

                $prep = OnboardingRoleplayPrep::where('user_id', $userid)->first();
                $b = ($prep) ? new RoleplayPrepResource($prep) : null;

                $train = OnboardingRoleplayTrain::where('user_id', $userid)->first();
                $c = ($train) ? new RoleplayTrainResource($train) : null;

                $comp = OnboardingRoleplayComp::where('user_id', $userid)->first();
                $d = ($comp) ? new RoleplayCompResource($comp) : null;
            } else {
                $get = OnboardingBusinessGetStarted::where('user_id', $userid)->first();
                $a = ($get) ? new GetBusinessStartedResource($get) : null;
            }

            $dates = $this->_getDates(); // last 15 days (including today)
            $user = User::findOrFail($userid);

            $activity = $user->onboardingactivity()->where('category', $category)->get();

            $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

            $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

            if ($category == 'pas') {
                return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'preparation' => $b, 'training' => $c, 'completion' => $d, 'activity' => $results], 200);
            } else {
                return $this->showMessage(['user_id' => $userid, 'getstarted' => $a, 'activity' => $results], 200);
            }

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

            if ($category == 'pas') {

                $get = OnboardingGetStarted::where('user_id', $request->userid)->first();
                $a = ($get) ? new GetStartedResourceMini($get) : null;

                $prep = OnboardingRoleplayPrep::where('user_id', $request->userid)->first();
                $b = ($prep) ? new RoleplayPrepResourceMini($prep) : null;

                $train = OnboardingRoleplayTrain::where('user_id', $request->userid)->first();
                $c = ($train) ? new RoleplayTrainResourceMini($train) : null;

                $comp = OnboardingRoleplayComp::where('user_id', $request->userid)->first();
                $d = ($comp) ? new RoleplayCompResourceMini($comp) : null;

                return $this->showMessage(['getstarted' => $a, 'preparation' => $b, 'training' => $c, 'completion' => $d], 200);

            } else {

                $get = OnboardingBusinessGetStarted::where('user_id', $request->userid)->first();
                $a = ($get) ? new GetBusinessStartedResourceMini($get) : null;

                return $this->showMessage(['getstarted' => $a], 200);
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Onboarding model not found', 400);
        }
    }


    /**
     * Display a listing 20 records every time of the resource by last ID
     *
     * @return \Illuminate\Http\Response
     */
    public function analysis(Request $request)
    {
        try {

            $rules = [
                'advisor' => 'required|exists:users,id',
                'category' => 'required',
            ];

            $messages = [
                'advisor.required' => 'Advisor is required',
                'advisor.exists' => 'That advisor cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $query = $request->input('query');
            $last_id = $request->input('last');
            $advisor = $request->advisor;
            $category = $request->category;

            $types = ['pas-roleplay-prep', 'jumpstart-12-training', '100k', 'pas-training', 'onboarding-business-academy'];

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->whereIn('user_training_analysis.type', $types)
                    ->where(function($q) use ($advisor, $category){
                        if($category == 'pas'){
                            $q->where('users.advisor', $advisor);
                        }
                        else if($category == 'licensee_onboarding'){
                            $q->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $q->where('users.business_advisor', $advisor);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->where('user_training_analysis.id', '>', $last_id)
                    ->whereIn('user_training_analysis.type', $types)
                    ->where(function($q) use ($advisor, $category){
                        if($category == 'pas'){
                            $q->where('users.advisor', $advisor);
                        }
                        else if($category == 'licensee_onboarding'){
                            $q->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $q->where('users.business_advisor', $advisor);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                }
            } else {
                if (empty($last_id)) {

                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.id', 'user_training_analysis.type', 'user_training_analysis.user_id', 'user_training_analysis.video_id', 'user_training_analysis.video_progress')
                        ->whereIn('user_training_analysis.type', $types)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->where(function ($b) use ($advisor, $category) {
                        if ($category == 'pas') {
                            $b->where('users.advisor', $advisor);
                        }
                        else if($category == 'licensee_onboarding'){
                            $b->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $b->where('users.business_advisor', $advisor);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));


                } else {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.id', 'user_training_analysis.type', 'user_training_analysis.user_id', 'user_training_analysis.video_id', 'user_training_analysis.video_progress')
                        ->whereIn('user_training_analysis.type', $types)
                        ->where('user_training_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->where(function ($b) use ($advisor, $category) {
                        if ($category == 'pas') {
                            $b->where('users.advisor', $advisor);
                        }
                        else if($category == 'licensee_onboarding'){
                            $b->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $b->where('users.business_advisor', $advisor);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                if($category == 'pas'){
                    $users = User::where('advisor', $advisor)->where('onboarding', 1)->get();
                }
                else if($category == 'licensee_onboarding'){
                    $users = User::where('licensee_onboarding_advisor', $advisor)->where('licensee_access', 1)->get();
                }else{
                    $users = User::where('business_advisor', $advisor)->where('onboarding', 1)->get();
                }

            }else{
                // Get the users from the analysis

                if($category == 'pas'){
                    $users = User::where('advisor','!=', null)->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where('onboarding', 1)->get();
                }
                else if($category == 'licensee_onboarding'){
                    $users = User::where('licensee_onboarding_advisor','!=', null)->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where('licensee_access', 1)->get();
                }else{
                    $users = User::where('business_advisor','!=', null)->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where('onboarding', 1)->get();
                }
            }
            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the onboarding analysis for these users
            $progress = $this->getusersanalytics($uids, $category);

            $transform = MidAnalyticsResource::collection($analytics);

            if ($category == 'pas') {
                $usertransform = OnboardingUser::collection($users);
            }
            else if($category == 'licensee_onboarding'){
                $usertransform = LicenseeOnboardingUser::collection($users);
            }else{
                $usertransform = OnboardingBusinessUser::collection($users);
            }

            return $this->successResponse(['users' => $usertransform, 'analysis' => $transform, 'progress' => $progress], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }


    /**
     * Display a listing 20 records every time of the resource by last ID
     *
     * @return \Illuminate\Http\Response
     */
    public function specialAnalysis(Request $request)
    {
        try {

            $query = $request->input('query');
            $last_id = $request->input('last');
            $category = $request->input('category');

            $types = ['pas-roleplay-prep', 'jumpstart-12-training', '100k', 'pas-training', 'onboarding-business-academy'];

            if (!empty($query)) {
                if (empty($last_id)) {

                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->whereIn('type', $types)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('user_training_analysis.id')->get();

                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());

                } else {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->whereIn('type', $types)
                        ->where('user_training_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('user_training_analysis.id')->get();

                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                }

                // Get the users from the analysis
                $users = DB::table('users')
                    ->where(function ($a) use ($query) {
                        $a->where('first_name', 'LIKE', '%' . $query . '%')->orWhere('last_name', 'LIKE', '%' . $query . '%');
                    })->where('onboarding', 1)->get();

                $users = User::hydrate($users->toArray());

                $uids = $users->pluck('id')->all();

                // Get the onboarding analysis for these users
                $progress = $this->getusersanalytics($uids, $category);

                $transform = AnalyticsResource::collection($analytics);

                if ($category == 'pas') {
                    $usertransform = OnboardingUser::collection($users);
                } else {
                    $usertransform = OnboardingBusinessUser::collection($users);
                }

                return $this->successResponse(['users' => $usertransform, 'analysis' => $transform, 'progress' => $progress], 200);
            } else {
                return $this->successResponse(['users' => [], 'analysis' => null, 'progress' => null], 200);
            }

        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getusersanalytics($users, $category)
    {
        try {

            $progress = [];

            foreach ($users as $key => $userid) {

                if ($category == 'pas') {

                    $get = OnboardingGetStarted::where('user_id', $userid)->first();
                    $a = ($get) ? new GetStartedResource($get) : null;

                    $prep = OnboardingRoleplayPrep::where('user_id', $userid)->first();
                    $b = ($prep) ? new RoleplayPrepResource($prep) : null;

                    $train = OnboardingRoleplayTrain::where('user_id', $userid)->first();
                    $c = ($train) ? new RoleplayTrainResource($train) : null;

                    $comp = OnboardingRoleplayComp::where('user_id', $userid)->first();
                    $d = ($comp) ? new RoleplayCompResource($comp) : null;

                    $dates = $this->_getDates(); // last 15 days (including today)
                    $user = User::findOrFail($userid);

                    $activity = $user->onboardingactivity()->where('category', $category)->get();

                    $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

                    $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

                    $progress[] = ['user_id' => $userid, 'getstarted' => $a, 'preparation' => $b, 'training' => $c, 'completion' => $d, 'activity' => $results];

                }
                if($category == 'licensee_onboarding'){

                    $get = LicenseeOnboardingGetStarted::where('user_id', $userid)->first();
                    $a = ($get)? new LicenseeGetStartedResource($get) : null;

                    $buildT = LicenseeOnboardingBuildTeam::where('user_id', $userid)->first();
                    $b = ($buildT)? new BuildTeamResource($buildT) : null;

                    $supportT = LicenseeOnboardingSupportTeam::where('user_id', $userid)->first();
                    $c = ($supportT)? new SupportTeamResource($supportT) : null;

                    $dates = $this->_getDates(); // last 15 days (including today)
                    $user = User::findOrFail($userid);

                    $activity = $user->onboardingactivity()->where('category', $category)->get();

                    $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

                    $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

                    $progress[] = ['user_id' => $userid, 'getstarted' => $a, 'build_team' => $b, 'support_team' => $c, 'activity' => $results ];

                } else {

                    $get = OnboardingBusinessGetStarted::where('user_id', $userid)->first();
                    $a = ($get) ? new GetBusinessStartedResource($get) : null;

                    $dates = $this->_getDates(); // last 15 days (including today)
                    $user = User::findOrFail($userid);

                    $activity = $user->onboardingactivity()->where('category', $category)->get();

                    $ongoing = array_unique($activity->pluck('adate')->toArray()); //get all unique days where there was activity

                    $results = $this->getActivity($ongoing, $dates); // map those activity days to the last 15 days

                    $progress[] = ['user_id' => $userid, 'getstarted' => $a, 'activity' => $results];
                }

            }

            return $progress;

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Onboarding model not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $surveys = OnboardingSurvey::all();
            $transform = OnboardingSurveyResource::collection($surveys);
            return $this->successResponse($transform, 200);
        }
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Surveys not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function survey(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'type' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'type.required' => 'Type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            OnboardingSurvey::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'type' => $request->type,
                ],
                [
                    'survey_answers' => $request->survey_answers,
                    'survey_url' => $request->survey_url,
                ]
            );

            return $this->showMessage('Survey saved successfully', 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Advisors not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function surveyResponses(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'type' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'type.required' => 'Type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $responses = OnboardingSurvey::where('user_id', $request->user_id)->where('type', $request->type)->first();

            $transform = new OnboardingSurveyResource($responses);


            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Advisors not found', 400);
        }
    }

    /**
     * Display a listing 20 records every time of the resource by last ID
     *
     * @return \Illuminate\Http\Response
     */
    public function analysisByManager(Request $request)
    {
        try {

            $rules = [
                'licensee' => 'required|exists:users,id',
                'category' => 'required',
            ];

            $messages = [
                'licensee.required' => 'Licensee is required',
                'licensee.exists' => 'That licensee cannot be found',
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $query = $request->input('query');
            $last_id = $request->input('last');
            $licensee = $request->licensee;
            $category = $request->category;

            $types = ['pas-roleplay-prep', 'jumpstart-12-training', '100k', 'pas-training', 'onboarding-business-academy'];

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->whereIn('user_training_analysis.type', $types)
                        ->where(function ($q) use ($licensee, $category) {
                            if ($category == 'pas') {
                                $q->where('users.manager', $licensee);
                            } else {
                                $q->where('users.manager', $licensee)->whereNotNull('users.business_advisor');
                            }
                        })->orderBy('user_training_analysis.id')->get();
                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                } else {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->where('user_training_analysis.id', '>', $last_id)
                        ->whereIn('user_training_analysis.type', $types)
                        ->where(function ($q) use ($licensee, $category) {
                            if ($category == 'pas') {
                                $q->where('users.manager', $licensee);
                            } else {
                                $q->where('users.manager', $licensee)->whereNotNull('users.business_advisor');
                            }
                        })->orderBy('user_training_analysis.id')->get();
                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                }
            } else {
                if (empty($last_id)) {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->whereIn('user_training_analysis.type', $types)
                        ->where(function ($a) use ($query) {
                            $a->where(DB::raw("(users.first_name LIKE '%" . $query . "%' OR users.last_name LIKE '%" . $query . "%')"));
                        })->where(function ($b) use ($licensee, $category) {
                        if ($category == 'pas') {
                            $b->where('users.manager', $licensee);
                        } else {
                            $b->where('users.manager', $licensee)->whereNotNull('users.business_advisor');
                        }
                    })->orderBy('user_training_analysis.id')->get();

                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());

                } else {
                    $analytics = DB::table('user_training_analysis')
                        ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                        ->select('user_training_analysis.*')
                        ->whereIn('user_training_analysis.type', $types)
                        ->where('user_training_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where(DB::raw("(users.first_name LIKE '%" . $query . "%' OR users.last_name LIKE '%" . $query . "%')"));
                        })->where(function ($b) use ($licensee, $category) {
                        if ($category == 'pas') {
                            $b->where('users.manager', $licensee);
                        } else {
                            $b->where('users.manager', $licensee)->whereNotNull('users.business_advisor');
                        }
                    })->orderBy('user_training_analysis.id')->get();

                    $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                $users = ($category == 'pas') ? User::where('manager', $licensee)->where('onboarding', 1)->get() : User::where('manager', $licensee)->whereNotNull('users.business_advisor')->where('onboarding', 1)->get();
            } else {
                // Get the users from the analysis
                if ($category == 'pas') {
                    $users = collect(DB::select("select * from users where manager=" . $licensee . " AND (users.first_name LIKE '%" . $query . "%' OR users.last_name LIKE '%" . $query . "%')"));
                } else {
                    $users = collect(DB::select("select * from users where manager=" . $licensee . " AND (users.first_name LIKE '%" . $query . "%' OR users.last_name LIKE '%" . $query . "%' and users.business_advisor IS NOT NULL)"));
                }
            }


            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the onboarding analysis for these users
            $progress = $this->getusersanalytics($uids, $category);

            $transform = AnalyticsResource::collection($analytics);

            if ($category == 'pas') {
                $usertransform = OnboardingUser::collection($users);
            } else {
                $usertransform = OnboardingBusinessUser::collection($users);
            }

            return $this->successResponse(['users' => $usertransform, 'analysis' => $transform, 'progress' => $progress], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }

    /**
     * Display a listing 20 records every time of the resource by last ID
     *
     * @return \Illuminate\Http\Response
     */
    public function analysisAll(Request $request)
    {
        try {

            $rules = [
                'category' => 'required',
            ];

            $messages = [
                'category.required' => 'Category is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $query = $request->input('query');
            $last_id = $request->input('last');
            // $advisor = $request->advisor;
            $category = $request->category;

            $types = ['pas-roleplay-prep', 'jumpstart-12-training', '100k', 'pas-training', 'onboarding-business-academy'];

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->whereIn('user_training_analysis.type', $types)
                    ->where(function($q) use ($category){
                        if($category == 'pas'){
                            $q->where('users.advisor','!=', null);
                        }
                        else if($category == 'licensee_onboarding'){
                            $q->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $q->where('users.business_advisor','!=', null);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });
                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->where('user_training_analysis.id', '>', $last_id)
                    ->whereIn('user_training_analysis.type', $types)
                    ->where(function($q) use ($category){
                        if($category == 'pas'){
                            $q->where('users.advisor','!=', null);
                        }
                        else if($category == 'licensee_onboarding'){
                            $q->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $q->where('users.business_advisor','!=', null);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });
                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            } else {
                if (empty($last_id)) {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->whereIn('user_training_analysis.type', $types)
                    ->where(function($a) use ($query){
                            $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where(function($b) use ($category){
                        if($category == 'pas'){
                            $b->where('users.advisor','!=', null);
                        }
                        else if($category == 'licensee_onboarding'){
                            $b->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $b->where('users.business_advisor','!=', null);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });
                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.id','user_training_analysis.type','user_training_analysis.user_id','user_training_analysis.video_id','user_training_analysis.video_progress')
                    ->whereIn('user_training_analysis.type', $types)
                    ->where('user_training_analysis.id', '>', $last_id)
                    ->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where(function($b) use ($category){
                        if($category == 'pas'){
                            $b->where('users.advisor','!=', null);
                        }
                        else if($category == 'licensee_onboarding'){
                            $b->where('users.licensee_onboarding_advisor','!=', null);
                        }else{
                            $b->where('users.business_advisor','!=', null);
                        }
                    })->orderBy('user_training_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });
                    $analytics = TrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                if($category == 'pas'){
                    $users = User::where('advisor','!=', null)->where('onboarding', 1)->get();
                }
                else if($category == 'licensee_onboarding'){
                    $users = User::where('licensee_onboarding_advisor', '!=', null)->where('licensee_access', 1)->get();
                }else{
                    $users = User::where('business_advisor', '!=', null)->where('onboarding', 1)->get();
                }
            }else{
                // Get the users from the analysis
                if ($category == 'pas') {
                    $users = User::where('advisor', '!=', null)->where(function ($a) use ($query) {
                        $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                    })->where('onboarding', 1)->get();
                }
                else if($category == 'licensee_onboarding'){
                    $users = User::where('licensee_onboarding_advisor','!=', null)->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where('licensee_access', 1)->get();
                }else{
                    $users = User::where('business_advisor','!=', null)->where(function($a) use ($query){
                        $a->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                    })->where('onboarding', 1)->get();
                }
            }

            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the onboarding analysis for these users
            $progress = $this->getusersanalytics($uids, $category);

            $transform = MidAnalyticsResource::collection($analytics);

            if ($category == 'pas') {
                $usertransform = OnboardingUser::collection($users);
            }
            else if($category == 'licensee_onboarding'){
                $usertransform = LicenseeOnboardingUser::collection($users);
            }else{
                $usertransform = OnboardingBusinessUser::collection($users);
            }

            return $this->successResponse(['users' => $usertransform, 'analysis' => $transform, 'progress' => $progress], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function notifyLicensee(Request $request)
    {
        try {

            $rules = [
                'userid' => 'required|exists:users,id',
            ];

            $message_rules = [
                'userid.required' => 'User ID is required',
                'userid.exists' => 'That user cannot be found',
            ];

            $validator = Validator::make($request->all(), $rules, $message_rules);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $userid = $request->userid;
            $user = User::findOrFail($userid);
            $coach = $user->getmanager;


            $note = "<p>Hello " . $coach->first_name . ",</p>
            <p>We wanted to let you know that your coach " . $user->first_name . " " . $user->last_name . " just completed their onboarding meeting #1 and is ready for PAS Roleplay training.</p>
            <p>Please feel free to check on their progress with the licensee visibility tool in your settings and encourage them throughout their onboarding process.</p>
            <p>If you have any questions regarding the licensee process or the visibility tool, please feel free to contact Wally at wally@focused.com</p>
            <p>Dedicated to your success!</p>
            <p>The PAS Onboarding Team</p>";
            $subject = "Licencee Update, " . $user->first_name . " " . $user->last_name;

            $messages[] = $note;

            $details = [
                'user' => $this->user,
                'to' => trim($user->email),
                'client_name' => '',
                'messages' => $messages,
                'subject' => $subject,
                'bcopy' => ['pasmailaudit@focused.com'],
            ];

            ProcessEmail::dispatch($details, 'onboarding-email');

            return $this->showMessage("A notification was sent to " . $user->first_name . "'s coach successfully", 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }
}
