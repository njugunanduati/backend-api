<?php

namespace App\Http\Controllers;


use App\Models\LeadGenLastActivity;
use App\Models\LeadGenOngoingActivity;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use LaracraftTech\LaravelDynamicModel\DynamicModel;
use Log;
use App\Models\User;
use App\Models\LeadGenSteps;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\LeadGenAdvisor;
use App\Models\LeadGenScripts;
use App\Models\LeadGenUserScripts;
use Validator;
use App\Http\Resources\Advisor as AdvisorResource;
use App\Http\Resources\LeadGenAnalysis;
use App\Http\Resources\LeadGenUser;
use App\Http\Resources\OnboardingUser;
use App\Http\Resources\TrainingMidAnalyticsResource;
use App\Models\LeadGenerationAnalysis;
use App\Models\LeadGenTrainingAnalytic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\FlashCoachingAnalysis as AnalysisResource;
use PDF;

/**
 * Summary of LeadGenerationController
 */
class LeadGenerationController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->getSteps($request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    public function store(Request $request)
    {
        //
    }


    public function show()
    {
        //
    }


    public function edit(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLeadGenerationRequest  $request
     * @param  \App\Models\LeadGeneration  $leadGeneration
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LeadGeneration  $leadGeneration
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        //
    }

    public function getOverviewVideos()
    {
        return $this->successResponse([], 200);
    }

    /**
     * Summary of getSteps
     * @param Request $request
     * @return
     */
    public function getSteps()
    {

        try {
            $steps = LeadGenSteps::all();
            return $this->successResponse($steps, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('No steps found ', 404);
        }
    }

    /**
     * Summary of getScripts
     * @param Request $request
     * @return
     */

    public function getUserScripts($id)
    {
        try {
            $scripts = LeadGenUserScripts::where('user_id', $id)->get();
            return $this->successResponse($scripts, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('No user scripts found ', 404);
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
            ];

            $messages = [
                'advisor.required' => 'Advisor is required',
                'advisor.exists' => 'That advisor cannot be found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $query = $request->input('query');
            $last_id = $request->input('last');
            $advisor = $request->advisor;
            
            
            $types = ['networking', 'joint-ventures', 'live-event-mastery'];

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where(function ($q) use ($advisor) {
                                $q->where('users.lead_gen_advisor', (int)$advisor);
                            
                        })
                        ->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });


                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->where(function ($q) use ($advisor) {
                            
                                $q->where('users.lead_gen_advisor', $advisor);
                            
                        })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                }
            } else {
                if (empty($last_id)) {

                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->where(function ($b) use ($advisor) {
                        
                            $b->where('users.lead_gen_advisor', $advisor);
                      
                    })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));


                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->where(function ($b) use ($advisor) {
                      
                            $b->where('users.lead_gen_advisor', $advisor);
                        
                    })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                $users = User::where('lead_gen_advisor', $advisor)->where('lead_gen', 1)->get();
            } else {
                // Get the users from the analysis
                
                    $users = User::where('lead_gen_advisor', $advisor)->where(function ($a) use ($query) {
                        $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                    })->where('lead_gen', 1)->get();
                
            }
            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the onboarding analysis for these users
            // $progress = $this->getusersanalytics($uids);

            $progress = $this->getAllUserProgress($uids);

            $usertransform = LeadGenUser::collection($users);

            return $this->successResponse(['users' => $usertransform, 'analysis' => $analytics, 'progress' => $progress], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }

    public function analysisAll(Request $request)
    {
        try {
            $query = $request->input('query');
            $last_id = $request->input('last');
            
            

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });


                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                }
            } else {
                if (empty($last_id)) {

                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));


                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                $users = User::where('lead_gen_advisor','!=', null)->where('lead_gen', 1)->get();
            } else {
                // Get the users from the analysis
                
                    $users = User::where('lead_gen_advisor','!=', null)->where(function ($a) use ($query) {
                        $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                    })->where('lead_gen', 1)->get();
                
            }
            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the lead generation analysis for these users


            
            $progress = $this->getAllUserProgress($uids);
            

            $usertransform = LeadGenUser::collection($users);

            return $this->successResponse(['users' => $usertransform, 'analysis' => $analytics, 'progress' => $progress], 200);
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
            
            
            $types = ['networking', 'joint-ventures', 'live-event-mastery'];

            if (empty($query)) {
                if (empty($last_id)) {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });


                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));

                }
            } else {
                if (empty($last_id)) {

                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));


                } else {
                    $analytics = DB::table('leadgen_video_analysis')
                        ->join('users', 'users.id', '=', 'leadgen_video_analysis.user_id')
                        ->select('leadgen_video_analysis.id', 'leadgen_video_analysis.user_id', 'leadgen_video_analysis.video_id', 'leadgen_video_analysis.video_time_watched','leadgen_video_analysis.video_length','leadgen_video_analysis.video_name','leadgen_video_analysis.updated_at')
                        ->where('leadgen_video_analysis.id', '>', $last_id)
                        ->where(function ($a) use ($query) {
                            $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                        })->orderBy('leadgen_video_analysis.id')->get()->chunk(1000, function ($analytics) {
                        return false;
                    });

                    $analytics = LeadGenTrainingAnalytic::hydrate(array_merge(...$analytics->toArray()));
                }
            }

            if (empty($query)) {
                // Get the users from the analysis
                $users = User::where('lead_gen_advisor','!=', null)->where('lead_gen', 1)->get();
            } else {
                // Get the users from the analysis
                
                    $users = User::where('lead_gen_advisor','!=', null)->where(function ($a) use ($query) {
                        $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                    })->where('lead_gen', 1)->get();
                
            }
            // Get the users from the analysis
            $uids = $users->pluck('id')->all();

            // Get the onboarding analysis for these users
            $progress = $this->getAllUserProgress($uids);

            $usertransform = LeadGenUser::collection($users);

            return $this->successResponse(['users' => $usertransform, 'analysis' => $analytics, 'progress' => $progress], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }


    public function saveUserScripts(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'script_name' => 'required',
                'content' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'script_name.required' => 'Script Name is required',
                'content.required' => 'Content is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $script = LeadGenUserScripts::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'script_name' => $request->script_name,
                ],
                [
                    'content' => $request->content,
                ]
            );

            return $this->getUserScripts($request->user_id);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Script not found', 400);
        }
    }

    public function getProgress(Request $request)
    {
        $tables = [
            'leadgen_networking_pre',
            'leadgen_networking_during',
            'leadgen_networking_post',
            'leadgen_live_event_pre',
            'leadgen_live_event_during',
            'leadgen_live_event_post',
            'leadgen_joint_ventures_pre',
            'leadgen_joint_ventures_during',
            'leadgen_joint_ventures_post'
        ];

        $analytics = [];
        try {
            foreach ($tables as $table_name) {
                $model = App::make(DynamicModel::class, ['table_name' => $table_name]);
                $progress = $model::where('user_id', $request->id)->get()->first();
                $analytics[$table_name] = $progress;
            }
            return $this->successResponse($analytics, 200);


        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Script not found', 400);
        }
    }

    protected function getAllUserProgress($uids)
    {
        $tables = [
            'leadgen_networking_pre',
            'leadgen_networking_during',
            'leadgen_networking_post',
            'leadgen_live_event_pre',
            'leadgen_live_event_during',
            'leadgen_live_event_post',
            'leadgen_joint_ventures_pre',
            'leadgen_joint_ventures_during',
            'leadgen_joint_ventures_post'
        ];

        $analytics = [];

        
        try {
            foreach($uids as $user_id){
                foreach ($tables as $table_name) {
                    $model = App::make(DynamicModel::class, ['table_name' => $table_name]);
                    $progress = $model::where('user_id', $user_id)->get()->first();
                    $progress_data[$table_name] = $progress;
                   
                }
                array_push($analytics,['user_id'=>$user_id ,'data' =>$progress_data?:[]]);
               
            }
            return $analytics;
            


        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Script not found', 400);
        }
    }
    public function saveOngoingActivity(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'step_id' => 'required|exists:leadgen_steps,id',
                'status' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User cannot be found',
                'step_id.required' => 'Step ID is required',
                'step_id.exists' => 'Step cannot be found',
                'status.required' => 'Status is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $activity = LeadGenLastActivity::updateOrCreate(
                [
                    'user_id' => $this->user->id, 
                ],
                [
                    'step_id' => $request->step,
                    'status' => $request->status,
                ]
            );
            return $this->successResponse($activity, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Script not found', 400);
        }
        
    }

    public function saveProgress(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'step' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User ID cannot be found',
                'step.required' => 'Step is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $table = "leadgen_" . $request->page . "_" . $request->tab;

            $model = App::make(DynamicModel::class, ['table_name' => $table]);

            // Save Step Row
            $progress = $model::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                ],
                [
                    $request->step => 1,
                    $request->step . "_date" => Carbon::now(),
                ]
            );

            // Save Latest Activity
            LeadGenLastActivity::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                ],
                [
                    'step' => $request->step,
                    'page' => $request->page,
                    'tab' => $request->tab,
                    'status' => 1,
                    'adate' => Carbon::now()
                ]
            );

            // Save Ongoing Activity

            LeadGenOngoingActivity::create(
                [
                    'user_id' => $request->user_id,
                    'step' => $request->step,
                    'page' => $request->page,
                    'tab' => $request->tab,
                    'adate' => Carbon::now(),
                    'status' => 1,
                ]
            );

            // Get Current Progress
            $tables = [
                'leadgen_networking_pre',
                'leadgen_networking_during',
                'leadgen_networking_post',
                'leadgen_live_event_pre',
                'leadgen_live_event_during',
                'leadgen_live_event_post',
                'leadgen_joint_ventures_pre',
                'leadgen_joint_ventures_during',
                'leadgen_joint_ventures_post'
            ];
    
            $analytics = [];
            
                foreach ($tables as $table_name) {
                    $model = App::make(DynamicModel::class, ['table_name' => $table_name]);
                    $progress = $model::where('user_id', $request->user_id)->get()->first();
                    $analytics[$table_name] = $progress;
                }

            // Mark the user as having accessed the Lead Gen Portal
            $user = User::findOrFail($request->user_id);

            if($user->lead_gen==0){
                $user->lead_gen=1;
                $user->save();
            }
            return $this->successResponse($analytics, 200);



        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tab Information not found', 400);
        }
    }


    public function saveProgressNotes(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'step' => 'required',
                'step_note' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User ID cannot be found',
                'step.required' => 'Step is required',
                'step_note.required' => 'Step Note is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $table = "leadgen_" . $request->page . "_" . $request->tab;

            $model = App::make(DynamicModel::class, ['table_name' => $table]);

            $activity = $model::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                ],
                [
                    $request->step => $request->status,
                    $request->step . "_date" => Carbon::now(),
                    $request->step . "_note" => $request->step_note,
                ]
            );
            return $this->successResponse($activity, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Tab Information not found', 400);
        }
    }

    /**
     * Undocumented function
     *
     *  @return \Illuminate\Http\Response
     */
    public function getAdvisors()
    {
        $advisors = LeadGenAdvisor::all();
        $advisorsResource = AdvisorResource::collection($advisors);
        return $this->successResponse($advisorsResource, 200);
    }

    public function getAdvisor($user_id)
    {
        $user = User::where('id',$user_id)->first();
        $advisor = LeadGenAdvisor::where('user_id', $user->lead_gen_advisor)->first();
        $advisorsResource = new AdvisorResource($advisor);
        return $this->successResponse($advisorsResource, 200);
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
                'leadgen_advisor_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user cannot be found',
                'leadgen_advisor_id.required' => 'Lead Generation Advisor ID is required',
                'leadgen_advisor_id.exists' => 'That lead generation advisor user cannot be found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $leadgen_advisor_id = $request->leadgen_advisor_id;

            $user = User::findOrFail($user_id);

            $user->lead_gen_advisor = $leadgen_advisor_id;

            $user->save();

            $user = $user->refresh();

            if ($user->lead_gen_advisor) {
                $advisor = LeadGenAdvisor::where('user_id', $user->lead_gen_advisor)->first();
                $transform = new AdvisorResource($advisor);
                return $this->showMessage($transform, 200);
            }

            return $this->showMessage(null, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Lead Generation Advisor not found', 400);
        }
    }

  

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function videoAnalysis($user_id)
    {
        try {
            
            $analysis = LeadGenerationAnalysis::where('user_id', $user_id)->get();

           
            $b = AnalysisResource::collection($analysis);

            return $this->successResponse(['analysis' => $b], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Lead Generation Analysis not found', 404);
        }
    }

    public function getAllVideoAnalysis($uids){
        $userVideoAnalysis = [];
        foreach($uids as $user_id){
            $analysis = LeadGenerationAnalysis::where('user_id', $user_id)->get();

           
            $b = AnalysisResource::collection($analysis);

            array_push($userVideoAnalysis,$b);
        }

        return $userVideoAnalysis;
    }

    public function saveVideoProgress(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'video_id' => 'required'
            ];

            $messages = [
                'user_id.required' => 'The user id is required',
                'video_id.required' => 'The user id is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            
             LeadGenerationAnalysis::updateOrCreate([
                'user_id'=>$request->user_id,
                'video_id'=> $request->video_id
            ],
            [
                'video_name'=>$request->video_name,
                'video_progress'=>$request->video_progress,
                'video_time_watched'=>$request->video_time_watched,
                'video_length'=>$request->video_length,
            ]);
           
            $analysis = LeadGenerationAnalysis::where('user_id', $request->user_id)->get();
            $b = AnalysisResource::collection($analysis);

            return $this->successResponse(['analysis' => $b], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Lead Generation Analysis not found', 404);
        }
    }

    public function lesson(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
                'lesson_id' => 'required',
            ];

            $messages = [
                'coach_id.required' => 'The coach id is required',
                'coach_id.exists' => 'That coach doe not exist',
                'lesson_id.required' => 'The lesson id is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id = $request->coach_id;
            $lesson_id = $request->lesson_id;

        

            return $this->successResponse([], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching progress not found', 400);
        }
    }

     /**
     * Send a PDF of coaching poral history via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function downloadScript(Request $request)
    {

        try {

            $rules = [
                'title' => 'required',
                'content' => 'required',
            ];

            $messages = [
                'title.required' => 'Title is required',
                'content.required' => 'Content is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $time = Carbon::now();

            $title = $request->title;
            $content = $request->content;
            $file_name = '';

            
                $file_name = strtolower('Script Download  - ' . $title. ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                
               

                $pdf = PDF::loadView('pdfs.script', compact('content', 'title'));
                return $pdf->download($file_name);
           
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to download PDF', 400);
        }
    }
  
}