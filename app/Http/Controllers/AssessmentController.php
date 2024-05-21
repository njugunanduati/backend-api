<?php

namespace App\Http\Controllers;

use Cache;
use Validator;
use App\Models\User;
use App\Helpers\Helper;
// use cypher
use App\Helpers\Cypher;
use App\Models\Module;
use App\Models\Company;
use App\Models\Assessment;
use App\Models\ImpSettings;
use App\Models\TrainingAccess;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\ModuleQuestion;
use App\Models\ModuleQuestionSplit;
use App\Http\Controllers\Controller;
use App\Models\ModuleQuestionComment;
use App\Models\ModuleQuestionResponse;
use App\Models\IncreasePricesExtra;
use App\Models\PersonalAccessToken;
use App\Models\AssessmentPercentage;
use App\Models\PriorityQuestionnaire;

use App\Http\Resources\Assessment as AssessmentResource;
use App\Http\Resources\AssessmentSimple as AssessmentSimpleResource;
use App\Http\Resources\AssessmentMiniAnalysis as AssessmentAnalysis;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\AssessmentAll as AssessmentAllResource;
use App\Http\Resources\PricesExtraResponse as ExtraResource;
use App\Models\CompanyUser;

class AssessmentController extends Controller
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
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function userAssessments($id)
    {
        try {
            $user = User::findOrFail($id);

            $transform = AssessmentResource::collection($user->assessments()->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function userSimpleAssessments($id)
    {
        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $user = User::findOrFail(intval($my_id));

            $transform = AssessmentSimpleResource::collection($user->assessments()->orderBy('id', 'DESC')->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }


    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function companyAssessments($id)
    {
        try {

            $company = Company::findOrFail($id);

            $transform = AssessmentSimpleResource::collection($company->assessments()->orderBy('id', 'DESC')->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That company does not exist', 404);
        }
    }



    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function assessmentSingleAnalysis($assessment_id, Request $request)
    {
        try {
            $cypher = new Cypher;
            $assessmentId = $cypher->decryptID(env('HASHING_SALT'), $assessment_id);
            $assessment = Assessment::findOrFail(intval($assessmentId, 10));
            $transform = new AssessmentAnalysis($assessment);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }
    }

    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function assessmentAnalysis(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'start' => 'required|string',
            'end' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $id = $request->input('user_id');
        $start = $request->input('start');
        $end = $request->input('end');

        try {
            $user = User::findOrFail($id);

            $assessments = $user->assessments()->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();

            $transform = AssessmentAnalysis::collection($assessments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $assessments = Assessment::all(); //Get all assessments

        $transform = AssessmentAllResource::collection($assessments);

        return $this->successResponse($transform, 200);
    }

    /**
     * Search for assessments by query and list 20 records
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        try {

            $query = $request->input('query');
            $last_id = $request->input('last');

            if (empty($query)) {
                if (empty($last_id)) {
                    $assessments = Assessment::orderBy('id')->limit(20)->get();
                } else {
                    $assessments = Assessment::where('id', '>', $last_id)->orderBy('id')->limit(20)->get();
                }
            } else {
                if (empty($last_id)) {
                    $assessments = Assessment::where('name', 'LIKE', '%' . $query . '%')->orderBy('id')->limit(20)->get();
                } else {
                    $assessments = Assessment::where('id', '>', $last_id)
                        ->where(function ($q) use ($query) {
                            $q->where('name', 'LIKE', '%' . $query . '%');
                        })->orderBy('id')->limit(20)->get();
                }
            }

            $transform = AssessmentResource::collection($assessments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Assessment not found', 400);
        }
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|max:50',
            'currency_id' => 'required|exists:currencies,id',
            'module_set_id' => 'required|exists:module_sets,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $module_set_id = (int)$request->input('module_set_id');
        
        $assessment = new Assessment;
        $assessment->company_id = $request->input('company_id');
        $assessment->name = $request->input('name');
        $assessment->currency_symbol = $request->input('currency_symbol');
        $assessment->currency_id = $request->input('currency_id');
        $assessment->module_set_id = $module_set_id;
        $assessment->back_count = 0;
        
        // Only Jumpstart assessment can allow adding %
        if(($module_set_id == 1) || ($module_set_id == 3) || ($module_set_id == 5) || ($module_set_id == 7)){
            $assessment->allow_percent = 1;
        }
        
        // If user/coach has access to quotum leap, allow them to proceed
        // to create a quotum experience
        if($this->user->trainingAccess->quotum_access == 1){
            // Add a priorities_questionnaire for this assessment
            // This is a feature for the quotum leap assessments
            PriorityQuestionnaire::firstOrCreate(['user_id' => (int)$request->input('user_id'), 'company_id' => (int)$request->input('company_id')]);
            $assessment->quotum_assessment = 1;
            
        }

        $assessment->save();

        $assessment->users()->attach($request->input('user_id'), [
            'view_rights' => 1,
            'edit_rights' => 1,
            'report_rights' => 1,
        ]);

        $transform = new AssessmentResource($assessment);

        return $this->showMessage($transform, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $assessment = Assessment::findOrfail($my_id); //Get by id

            $transform = new AssessmentResource($assessment);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'name' => 'required|max:50',
                'currency_id' => 'required|exists:currencies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment = Assessment::findOrFail($id);

            if ($assessment) {
                $assessment->company_id = $request->input('company_id');
                $assessment->name = trim($request->input('name'));
                $assessment->currency_symbol = $request->input('currency_symbol');
                $assessment->currency_id = $request->input('currency_id');
                $assessment->save();

                if ($assessment->isDirty()) {
                    $assessment->save();
                }

                // $transform = new AssessmentResource($assessment);
                $transform = AssessmentSimpleResource::collection($this->user->assessments()->orderBy('id', 'DESC')->get());
                return $this->successResponse($transform, 200);
            }

            return $this->errorResponse('This assessment does not exist', 404);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addPercent(Request $request)
    {
        try {

            $rules = [
                'assessment_id' => 'required',
                'percent' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'percent.required' => 'Percent is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);
            $percent = intval($request->percent);

            $assessment = Assessment::findOrFail(intval($assessment_id));

            // Only assessments that has this flag will be processed
            // Old assessments will not have this flag on
            if ($assessment && intval($assessment->allow_percent) == 1) {

                $has_financials = $assessment->confirmFinancialsExists();

                // Work with an assessment that has 
                // The financials module already filled
                if($has_financials){

                    // Check if there is a percentage record
                    $percentage = AssessmentPercentage::where('assessment_id', $assessment_id)->first();

                    if($percentage){
                        // This is a repeat run
                        $array = explode(",", $percentage->modules);
                        $count = 0;

                        foreach ($array as $module) {
                            if(strlen($module) > 0){
                                $module_array = explode("+", $module);
                                $module_name = $module_array[0];
                                $module_path = $module_array[1];
                                $assessment->addImpactManually($module_name, $percent, $count, $module_path);
                            }
                        }

                        return $this->successResponse(['status'=> 'Valid', 'count' => $count], 200);

                    }else{
                        // This is a first run

                        $module = new Module('m_advertising_questions');
                        $modules = $module->module_set($assessment->module_set_id);

                        $invalid_modules = array('financials', 'introduction', 'foundational', 'valuation');
                        $statuses = [];

                        $count = 0;
                        $list = '';

                        // Add/update impact to all the modules in the assessemennt
                        foreach ($modules as $module) {
                            if (!in_array($module->module_path, $invalid_modules)) {
                                $status = $assessment->confirmModuleHasImpact($module->module_name);
                                if($status == false){
                                    $list .= $module->module_name.'+'.$module->module_path.',';
                                    $assessment->addImpactManually($module->module_name, $percent, $count, $module->module_path);
                                }
                            }
                        }

                        $ap = new AssessmentPercentage;
                        $ap->modules = $list;
                        $ap->assessment_id = $assessment_id;
                        $ap->save();

                        $assessment->percent_added = 1;
                        $assessment->save();

                        return $this->successResponse(['status'=> 'Valid', 'count' => $count], 200);
                        
                    }

                }else{
                    return $this->successResponse(['status'=> 'No Financials'], 200);
                }
            }else{
                return $this->successResponse(['status'=> 'Not Allowed'], 200);
            }

            return $this->errorResponse('This assessment does not exist', 404);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This assessment does not exist', 404);
        }
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateOtherIndustry(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'otherindustry' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $value = ($request->input('otherindustry') == 'NULL') ? null : $request->input('otherindustry');

            $assessment = Assessment::find($id);
            $assessment->otherindustry = $value;
            $assessment->save();

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This assessment does not exist', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveCostOfCoaching(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'monthly_coaching_cost' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment = Assessment::find($id);
            $assessment->monthly_coaching_cost = (int)$request->input('monthly_coaching_cost');

            if ($request->input('initial_coaching_cost')) {
                $assessment->initial_coaching_cost = (int)$request->input('initial_coaching_cost');
            }

            $assessment->save();

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveImplementationDate(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'imp_date' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $assessment = Assessment::findOrFail($assessment_id);
            $assessment->implementation_start_date = $request->input('imp_date');
        
            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function togglePlanningMeetings(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'add_planning_meetings' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $status = (int)$request->input('add_planning_meetings');

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $assessment = Assessment::findOrFail($assessment_id);
            $assessment->add_planning_meetings = $status;

            // Set planning_meetings to ZERO
            if($status == 0){
                $assessment->planning_meetings = $status;
            }

            // If this is a quantum assessmennt add this variabble to the priorities_questionnaire table
            if(($assessment->quotum_assessment == 1) && ($status == 0)){
                $array = ['q4' => $status];
                $questionnaire = PriorityQuestionnaire::updateOrCreate(['company_id' => $assessment->company_id, 'user_id' => $this->user->id],$array);
            }

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleReviewMeetings(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'add_review_meetings' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $status = (int)$request->input('add_review_meetings');

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $assessment = Assessment::findOrFail($assessment_id);

            $assessment->add_review_meetings = $status;

            // If this is a quantum assessmennt add this variabble to the priorities_questionnaire table
            if($assessment->quotum_assessment == 1){
                $array = ['q5' => $status];
                $questionnaire = PriorityQuestionnaire::updateOrCreate(['company_id' => $assessment->company_id, 'user_id' => $this->user->id],$array);
            }

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function savePlanningMeetings(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'planning_meetings' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $number = (int)$request->input('planning_meetings');

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $id);

            $assessment = Assessment::findOrFail($assessment_id);

            $assessment->planning_meetings = $number;

            // If this is a quantum assessmennt add this variabble to the priorities_questionnaire table
            if($assessment->quotum_assessment == 1){
                $array = ['q4' => $number];
                $questionnaire = PriorityQuestionnaire::updateOrCreate(['company_id' => $assessment->company_id, 'user_id' => $this->user->id],$array);
            }

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveAgreements(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'agreements' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment = Assessment::find($id);
            $assessment->agreements = (int)$request->input('agreements');
            $assessment->save();

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function saveRevenueShare(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                'revenue_share' => 'required',
                'shared' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment = Assessment::find($id);
            $assessment->revenue_share = (int)$request->input('revenue_share');
            $assessment->shared = (float)$request->input('shared');

            $assessment->save();

            if ($assessment->isDirty()) {
                $assessment->save();
            }

            $transform = new AssessmentResource($assessment);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {

            return $this->errorResponse('This assessment does not exist', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        try {
            $cypher = new Cypher;
            $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);
            $personal_access_token = PersonalAccessToken::findToken($request->bearerToken());

            $assessment = Assessment::findOrFail(intval($my_id, 10));
            $company_user = CompanyUser::where('company_id', $assessment->company_id)->first();

            if ($personal_access_token->tokenable_id != $company_user->user_id) {
                return $this->errorResponse('This action is not permitted', 404);
            }

            if ($assessment->priorities) {
                $assessment->priorities()->delete(); //Delete priorities associated
            }

            if ($assessment->rpm) {
                $assessment->rpm()->delete(); // Delete all rpm_dial_responses associated
            }

            if ($assessment->sessions) {
                $assessment->sessions()->delete(); // Delete all sessions notes associated
            }

            if ($assessment->users) {
                $assessment->users()->detach(); // Delete all users associated
            }

            if ($assessment->trails) {
                $assessment->trails()->delete(); // Delete all trails associated
            }

            if ($assessment->impCoaching) {
                $assessment->impCoaching()->delete(); // Delete all imp coaching associated
            }

            if ($assessment->impSettings) {
                $assessment->impSettings()->delete(); // Delete all imp settings associated
            }

            $assessment->delete(); //Delete the assessment

            $transform = AssessmentSimpleResource::collection($this->user->assessments()->orderBy('id', 'DESC')->get());

            return response()->json(['data' => $transform, 'message' => 'Assessment and all associations were deleted!']);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }
    }

    public function getComment($id, $module, $assessment_id)
    {
        $comment_table = new ModuleQuestionComment;
        $comment_table->setTable(Helper::module_name_to_full_table_name($module, 'QuestionComment'));
        $comment_count = $comment_table->where('question_id', $id)->where('assessment_id', $assessment_id)->count();
        $comment = null;
        if ($comment_count) {
            $comment = $comment_table->where('question_id', $id)->where('assessment_id', $assessment_id)->first()->comment;
        }
        return $comment;
    }

    public function loadQuestion($assessment_id, Request $request)
    {

        try {
            $cypher = new Cypher;
            $assess_id = intval($cypher->decryptID(env('HASHING_SALT'), base64_decode($assessment_id)));
            $assessment = Assessment::findOrFail($assess_id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }

        $this->validate($request, [
            'module' => 'required|alpha_dash',
        ]);
        $module = $request->input('module');
        Cache::tags('assessment_' . $assessment->id)->flush();

        $responses = new ModuleQuestionResponse();
        $responses->setTable(Helper::module_name_to_full_table_name($module, 'QuestionResponse'));
        $responses = $responses->where('assessment_id', $assessment->id)->get();

        foreach ($responses as $key => $value) {
            $response_question = new ModuleQuestion;
            $response_question->setTable(Helper::module_name_to_full_table_name($module, 'Question'));
            $response_question = $response_question->where('id', $value->question_id)->first();
            $value->setAttribute('question_type', $response_question->question_type);
            $value->setAttribute('module_name', $module);
            $value->setAttribute('comment', $this->getComment($value->question_id, $module, $assess_id));
        }

        $transform = array('questions' => $responses);

        // Get extras for increase price module
        if ((strpos(strtolower($module), 'price') !== false)) {
            $extras = new IncreasePricesExtra();
            $extras = $extras->where('assessment_id', $assessment->id)->first();
            if ($extras) {
                $extras = new ExtraResource($extras);
                $transform = array('questions' => $responses, 'extras' => $extras);
            }
        }

        return $this->successResponse($transform, 200);
    }

    public function saveResponse($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'module' => 'required',
            'response' => 'required',
            'question_id' => 'required|integer',
        ]);

        $module = $request->input('module');
        $response = strip_tags($request->input('response'));

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $assessment = Assessment::findOrFail($id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }

        // Clear all caches for this assessment
        Cache::tags('assessment_' . $assessment->id)->flush();

        $questions = new ModuleQuestion();
        $questions->setTable(Helper::module_name_to_full_table_name($module, 'Question'));
        $current_question = $questions->where('id', $request->input('question_id'))->first();

        // Validate responses

        switch ($current_question->question_type) {
            case 'split_y_n':
                $input = request()->all();
                $input['response'] = strtolower($input['response']);
                $validator = Validator::make($input, [
                    'response' => 'required|in:y,n',
                ]);
                if ($validator->fails()) {
                    return $this->errorResponse('Expected Yes or No for this response.Please use Y or N', 422);
                }
                $current_question = $this->handleSplitQuestion($assessment->id, $request);
                $next_question_id = $current_question->split_result;

                break;
            case 'impact':
                if (!is_numeric($response)) {
                    return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                }
                $next_question_id = $current_question->next_question;
                break;
            case 'percentage':
                if (!is_numeric($response)) {
                    return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                }
                $next_question_id = $current_question->next_question;
                break;
            case 'decimal':
                if (!is_numeric($response)) {
                    return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                }
                $next_question_id = $current_question->next_question;
                break;
            default;
                $next_question_id = $current_question->next_question;
        }

        $response = new ModuleQuestionResponse;
        $response->setTable(Helper::module_name_to_full_table_name($module, 'QuestionResponse'));

        if ($response->where('question_id', $request->input('question_id'))->where('assessment_id', $id)->count()) {
            $response = $response->where('question_id', $request->input('question_id'))->where('assessment_id', $id)->first();

            $response->setTable(Helper::module_name_to_full_table_name($module, 'QuestionResponse'));
        }

        $response->question_id = $request->input('question_id');
        $response->assessment_id = $id;
        $response->response = $request->input('response');
        $response->save();

        $check_comment = new ModuleQuestionComment;
        $check_comment->setTable(Helper::module_name_to_full_table_name($module, 'QuestionComment'));

        if (!$check_comment->where('question_id', $request->input('question_id'))->where('assessment_id', $id)->count()) {
            $check_comment = '';
        } else {
            $check_comment = 1;
        }


        if ($next_question_id == 0) {
            return $this->successResponse(array('end' => true,), 200);
        }


        $next_question = $questions->where('id', $next_question_id)->first();

        $next_question->setTable(Helper::module_name_to_full_table_name($module, 'Question'));

        $transform[] = array(
            'next' => nl2br($next_question->question_text),
            'type' => $next_question->question_type == 'impact' ? 'percentage' : $next_question->question_type,
            'note' => $next_question->note()->count() ? $next_question->note()->first()->note_text : '',
            'comment' => $check_comment,
            'id' => $next_question->id,
            'last' => $next_question->lastQuestion(),
            'end' => false,
        );


        return $this->successResponse($transform, 200);
    }

    protected function handleSplitQuestion($id, $request)
    {
        $questions = new ModuleQuestionSplit();
        $questions->setTable(Helper::module_name_to_full_table_name($request['module'], 'QuestionSplit'));

        $split_question = $questions->where('question_id', $request['question_id'])->where('split_criteria_operator', ucwords($request['response']))->first();

        return $split_question;
    }



    public function saveComment($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'comment' => 'required',
            'question_id' => 'required|integer',
            'module' => 'required|alpha_dash',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }
        $cypher = new Cypher;
        $my_id = $cypher->decryptID(env('HASHING_SALT'), $id);

        try {
            $assessment = Assessment::findOrFail($my_id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }

        $module = $request->input('module');

        $comment = new ModuleQuestionComment;
        $comment->setTable(Helper::module_name_to_full_table_name($module, 'QuestionComment'));
        if ($comment->where('question_id', $request->input('question_id'))->where('assessment_id', $my_id)->count()) {
            $comment = $comment->where('question_id', $request->input('question_id'))->where('assessment_id', $my_id)->first();
            $comment->setTable(Helper::module_name_to_full_table_name($module, 'QuestionComment'));
        }
        $comment->question_id = $request->input('question_id');
        $comment->assessment_id = $assessment->id;
        $comment->comment = strip_tags($request->input('comment'));
        $comment->save();
        return $this->singleMessage('Comment added successfully!', 201);
    }



    public function saveSingleResponse($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'response' => 'required',
            'question_id' => 'required|integer',
            'module' => 'required|alpha_dash',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $assessment = Assessment::findOrFail($id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }

        // Clear all caches for this assessment
        Cache::tags('assessment_' . $assessment->id)->flush();

        $responded = $request->input('response');
        $question_id = $request->input('question_id');
        $module = $request->input('module');

        $responses = new ModuleQuestionResponse;
        $responses->setTable(Helper::module_name_to_full_table_name($module, 'QuestionResponse'));

        if ($responses->where('question_id', $question_id)->where('assessment_id', $id)->count()) {
            $responses = $responses->where('question_id', $question_id)->where('assessment_id', $id)->first();
            $responses->setTable(Helper::module_name_to_full_table_name($module, 'QuestionResponse'));
        }

        $responses->question_id = $question_id;
        $responses->assessment_id = $id;
        $responses->response = strip_tags($responded);
        $responses->save();

        return $this->singleMessage('Responses recorded successfully', 201);
    }


    public function saveBulkResponse($id, Request $request)
    {
        try {
            $cypher = new Cypher;
            $id = intval($cypher->decryptID(env('HASHING_SALT'), $id));
            $assessment = Assessment::findOrFail($id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }


        // Clear all caches for this assessment
        Cache::tags('assessment_' . $assessment->id)->flush();

        $bulk_response = $request->input('response');

        foreach ($bulk_response  as $key => $value) {


            $questions = new ModuleQuestion();
            $questions->setTable(Helper::module_name_to_full_table_name($value['module'], 'Question'));
            $current_question = $questions->where('id', $value['question_id'])->first();

            // Validate responses

            switch ($current_question->question_type) {
                case 'split_y_n':
                    $value['response'] = strtolower($value['response']);
                    $validator = Validator::make($value, [
                        'response' => 'required|in:y,n',
                    ]);
                    if ($validator->fails()) {
                        return $this->errorResponse('Expected Yes or No for this response.Please use Y or N', 422);
                    }
                    $current_question = $this->handleSplitQuestion($assessment->id, $value);

                    break;
                case 'impact':
                    if (!is_numeric($value['response'])) {
                        return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                    }
                    break;
                case 'percentage':
                    if (!is_numeric($value['response'])) {
                        return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                    }
                    break;
                case 'decimal':
                    if (!is_numeric($value['response'])) {
                        return $this->errorResponse('Expected a number without any letters or symbols for this response', 422);
                    }
                    break;
                case 'blank':
                    if ($value['response'] = '') {
                        $value['response'] = '';
                    }

                    break;
            }

            $response = new ModuleQuestionResponse;
            $response->setTable(Helper::module_name_to_full_table_name($value['module'], 'QuestionResponse'));


            if ($response->where('question_id', $value['question_id'])->where('assessment_id', $id)->count()) {
                $response = $response->where('question_id', $value['question_id'])->where('assessment_id', $id)->first();

                $response->setTable(Helper::module_name_to_full_table_name($value['module'], 'QuestionResponse'));
            }

            $response->question_id = $value['question_id'];
            $response->assessment_id = $id;
            $response->response = $value['response'];
            $response->save();

            $check_comment = new ModuleQuestionComment;
            $check_comment->setTable(Helper::module_name_to_full_table_name($value['module'], 'QuestionComment'));

            if (!$check_comment->where('question_id', $value['question_id'])->where('assessment_id', $id)->count()) {
                $check_comment = '';
            } else {
                $check_comment = 1;
            }
        }

        return $this->singleMessage('Responses recorded successfully', 201);
    }

    /**
     * Display a client portals assessments.
     *
     * @return \Illuminate\Http\Response
     */
    public function clientSimpleAssessments(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company ID Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);


            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request['company_id'];

            $assessments = Assessment::where('company_id', $company_id)->get();

            $transform = AssessmentSimpleResource::collection($assessments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Disable Assessment Reminders.
     *
     * @return \Illuminate\Http\Response
     */
    public function disableAssessmentReminders(Request $request)
    {
        try {
            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment ID Not Found',
            ];
            $data = $request->all();
            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id')));
            $data['assessment_id']=$assessment_id;
            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $impSettings = ImpSettings::where('assessment_id', '=', $assessment_id)->first();
            
            if($impSettings){
                $impSettings->delete();
                return $this->successResponse(["data" => "Asssessment meeting reminders where disabled successfully" ], 200);
            }else{
                return $this->errorResponse('That assessment does not exist', 404);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }
    }
}

