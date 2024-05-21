<?php

namespace App\Http\Controllers;

use Validator;
use PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Helpers\Cypher;
use App\Traits\ApiResponses;
use App\Models\Assessment;
use App\Models\ImpCoaching;
use App\Models\ImpSimplifiedStep;
use App\Models\QuotumLevelOne;
use App\Models\ImpStep;
use App\Http\Resources\QuotumLevelOneStepResource;
use App\Http\Resources\QuotumMiniLevelOneResource;
use App\Http\Resources\ImpCoachingResource as CoachingResource;
use App\Http\Resources\ImpCoachingSimple as CoachingSimple;
use App\Http\Resources\ImpSimplifiedStep as SimplifiedStepResource;
use App\Http\Resources\ImpStep as StepResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImpCoachingController extends Controller {

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
    public function index() {
        $coaching = ImpCoaching::all();//Get all Implementation coaching

        $transform = CoachingResource::collection($coaching);

        return $this->successResponse($transform,200);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function steps(Request $request) {

        if(isset($request->assessment_id)){

            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->assessment_id));

            $assessment = Assessment::findOrFail($assessment_id);

            $quotum = ($assessment->prioritiesQuestionnaire())? true : false;
            $quotum_recommendation = ($assessment->prioritiesQuestionnaire())? (bool)$assessment->prioritiesQuestionnaire()->recommendation : false;

            if($quotum){ // This is a quotum assessment

                if($quotum_recommendation){ // This is comprehensive steps

                    // Get the the level one
                    $responses = QuotumLevelOne::all();
            
                    if(count($responses) > 0){

                        $array = [];
                        
                        foreach ($responses as $key => $response) {

                            $array[] = (object)[
                                'id' => (string)$response->_id, 
                                'path' => $response->path,
                                'step' => $response->step,
                                'header' => 'Step '. $response->step .': '. $response->description,
                                'body' => $this->formatStepsBody($response),
                                'student_header' => '',
                                'student_body' => '',
                            ];

                        }// end of foreach
                        
                        return $this->successResponse(QuotumLevelOneStepResource::collection($array), 200);

                    }else{
                        return $this->successResponse([], 200);
                    }

                }else{ // This is simplified steps
                    // Get the new simplified steps
                    $simplified_steps = ImpSimplifiedStep::all(); //Get all Implementation simplified steps
                    $transform = SimplifiedStepResource::collection($simplified_steps);
                    return $this->successResponse($transform,200);
                }
            }else{
              $steps = ImpStep::all();//Get all OLD Implementation steps
              $transform = StepResource::collection($steps);
              return $this->successResponse($transform,200);  
            }

        }else{
            $steps = ImpStep::all();//Get all Implementation steps
            $transform = StepResource::collection($steps);
            return $this->successResponse($transform,200);
        }        
    }

    private function formatStepsBody($content){
        $list = '';
        $items = '';
        foreach ($content->children as $key => $each) {
            $list .= "<li>".$each->description."</li>";
            $items .= ($key+1).": ".$each->description."\n\n";
        }

        $body = "<ol>".$list."</ol>";

        return $body;
    }
    
    /**
     * Download the  implementation task
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function downloadTask(Request $request)
    {

        try {

            $rules = [
                'assessment_id' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);

            $assessment = Assessment::findOrFail($assessment_id);

            $quotum = ($assessment->prioritiesQuestionnaire())? true : false;
            $quotum_recommendation = ($assessment->prioritiesQuestionnaire())? (bool)$assessment->prioritiesQuestionnaire()->recommendation : false;

            if($quotum){ // This is a quotum assessment

                if($quotum_recommendation){ // This is comprehensive steps

                    $task = QuotumLevelOne::where(['_id' => $request->task_id])->first();

                    if($task){

                        $task->body = $this->formatStepsBody($task);

                        $time = Carbon::now();
                        $key = str_random(6);
                        $file_name = strtolower('Task ' . $key . ' ' . $time->toDateString() . '.pdf');
                        $file_name = preg_replace('/\s+/', '_', $file_name);
                        
                        $title = 'Step '. $task->step . ' : ' .$task->description;
                        
                        $pdf = PDF::loadView('pdfs.task', compact('task', 'title'));
                        return $pdf->download($file_name);

                    }else{
                        return null;
                    }

                }else{ // This is the new simplified steps

                    $rules = [
                        'task_id' => 'required|exists:imp_simplified_steps,id',
                    ];

                    $messages = [
                        'task_id.required' => 'Task is required',
                        'task_id.exists' => 'That task doesnt exist',
                    ];

                    $validator = Validator::make($request->all(), $rules, $messages);

                    if ($validator->fails()) {
                        return $this->errorResponse($validator->errors(), 400);
                    }

                    $task_id = $request->task_id;

                    $task = ImpSimplifiedStep::findOrFail($task_id);

                    if($task){

                        $time = Carbon::now();
                        $key = str_random(6);
                        $file_name = strtolower('Task ' . $key . ' ' . $time->toDateString() . '.pdf');
                        $file_name = preg_replace('/\s+/', '_', $file_name);
                        
                        $title = trim($task->header);
                        
                        $pdf = PDF::loadView('pdfs.task', compact('task', 'title'));
                        return $pdf->download($file_name);

                    }else{
                        return null;
                    }

                }

            }else{ // This is OLD imp steps

                $rules = [
                    'task_id' => 'required|exists:imp_steps,id',
                ];

                $messages = [
                    'task_id.required' => 'Task is required',
                    'task_id.exists' => 'That task doesnt exist',
                ];

                $validator = Validator::make($request->all(), $rules, $messages);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }

                $task_id = $request->task_id;

                $task = ImpStep::findOrFail($task_id);

                if($task){

                    $time = Carbon::now();
                    $key = str_random(6);
                    $file_name = strtolower('Task ' . $key . ' ' . $time->toDateString() . '.pdf');
                    $file_name = preg_replace('/\s+/', '_', $file_name);
                    
                    $title = trim($task->header);
                    
                    $pdf = PDF::loadView('pdfs.task', compact('task', 'title'));
                    return $pdf->download($file_name);

                }else{
                    return null;
                }

            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to download PDF', 400);
        }
    }



    public function getPrevious($path, $nid, $assessment_id){
        
        $previous = ImpCoaching::where('assessment_id', $assessment_id)
                        ->where(function($q) use ($path){
                            $q->where('path', $path);
                        })
                        ->where(function($q) use ($nid){
                            $q->where('nid', $nid);
                        })->first();

        if($previous){
            return $previous;
        }else{
            return null;
        }
    }


    /**
     * Search for implementation coaching by query 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'nid' => 'required',
                'path' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'nid.required' => 'Index ID is required',
                'path.required' => 'Path is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment_id = intval($request->input('assessment_id'));
            $nid = intval($request->input('nid'));
            $path = $request->input('path');

            if($assessment_id == 0){
                $coaching = [];
            }else{
                $coaching = ImpCoaching::where('assessment_id', $assessment_id)
                        ->where(function($q) use ($path){
                            $q->where('path', $path);
                        })
                        ->where(function($q) use ($nid){
                            $q->where('nid', $nid);
                        })->get();
            }
        
            if((strlen($request->input('previous_path')) == 0) || ($request->input('previous_path') == 'planning-meeting') || ($request->input('previous_path') == 'quarterly-review')){
               $previous = null;
            }else{
               $previous = $this->getPrevious($request->input('previous_path'), $request->input('previous_nid'), $assessment_id); 
            }

            if(count($coaching) > 0){
                
                if($previous){
                    $coaching[0]['previous'] = new CoachingResource($previous);
                }else{
                    $coaching[0]['previous'] = null;
                }
                
            }else{
                
                if($previous){
                    $coaching = collect(['previous' => new CoachingResource($previous)]);
                }else{
                    $coaching = collect(['previous' => null]);
                }
                
            }

            $transform = CoachingResource::collection($coaching);
            
            return $this->successResponse($transform, 200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
        }
    }

    /**
     * Search for implementation coaching by query 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function searchByAssessment(Request $request)
    {
        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
            ];
            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id')));

            $validator = Validator::make(['assessment_id' => $assessment_id], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            
            $coaching = ImpCoaching::where('assessment_id', $assessment_id)->get();

            $transform = CoachingSimple::collection($coaching);
            
            return $this->successResponse($transform, 200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
        }
    }





    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {


    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function actions(Request $request) {

        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'nid' => 'required',
                'aid' => 'required',
                'path' => 'required',
                'type' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'nid.required' => 'Index ID is required',
                'aid.required' => 'Action ID is required',
                'path.required' => 'Path is required',
                'type.required' => 'Type of action is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $type = $request->input('type');
            $assessment_id = intval($request->input('assessment_id'));
            $nid = intval($request->input('nid'));
            $path = $request->input('path');
            $aid = $request->input('aid');

            $coaching = ImpCoaching::firstOrCreate(
                ['assessment_id' => $assessment_id, 'path' => $path, 'nid' => $nid]
            );

            if($type == 'action-notes'){
                $coaching->coachingActions()->updateOrCreate(
                    ['coaching_id' => $coaching->id, 'aid' => $aid], 
                    ['notes' => $request->input('notes')]
                );
            }

            if($type == 'action-complete'){
                $coaching->coachingActions()->updateOrCreate(
                    ['coaching_id' => $coaching->id, 'aid' => $aid],
                    ['complete' => (int)$request->input('complete')]
                );
            }

            $transform = new CoachingResource($coaching);

            return $this->successResponse($transform, 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
        }

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'nid' => 'required',
                'imp_id' => 'required',
                'path' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'nid.required' => 'Index ID is required',
                'imp_id.required' => 'imp ID is required',
                'path.required' => 'Path is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $impid = intval($request->input('imp_id'));
            $assessment_id = intval($request->input('assessment_id'));
            $nid = intval($request->input('nid'));
            $path = $request->input('path');

            if($impid == 0){
                $coaching = new ImpCoaching;
                $coaching->assessment_id = $assessment_id;
                $coaching->path = $path;
                $coaching->nid = $nid;
                $coaching->save();
            }else{
                $coaching = ImpCoaching::findOrFail($impid);
            }

            if($request->input('imp-revenue')){
                foreach ($request->input('imp-revenue')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingWeeklyRevenue()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['weekly_revenue' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('imp-leads')){
                foreach ($request->input('imp-leads')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingWeeklyLeads()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['weekly_leads' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('imp-appointments')){
                foreach ($request->input('imp-appointments')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingWeeklyAppointments()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['weekly_appointments' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('imp-notes')){
                foreach ($request->input('imp-notes')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingNotes()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['notes' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('need-to-complete')){
                foreach ($request->input('need-to-complete')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingActionsNeedComplete()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['notes' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('biggest-wins')){
                foreach ($request->input('biggest-wins')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingBiggestWins()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['notes' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('biggest-challenges')){
                foreach ($request->input('biggest-challenges')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingBiggestChallenges()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['notes' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('client-must-complete')){
                foreach ($request->input('client-must-complete')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingActionsMustComplete()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['done' => $each['done'], 'notes' => $each['value']]
                        );
                    }
                }
            }

            if($request->input('coach-need-to-complete')){
                foreach ($request->input('coach-need-to-complete')  as $key => $each) {
                    if(isset($each['value']) && isset($each['coaching_id'])){
                        $coaching->coachingCoachesHelp()->updateOrCreate(
                            ['id' => $each['id'], 'coaching_id' => $each['coaching_id']],
                            ['done' => $each['done'], 'notes' => $each['value']]
                        );
                    }
                }
            }

            if((strlen($request->input('previous_path')) == 0) || ($request->input('previous_path') == 'planning-meeting') || ($request->input('previous_path') == 'quarterly-review')){
               $previous = null;
            }else{
               $previous = $this->getPrevious($request->input('previous_path'), $request->input('previous_nid'), $assessment_id); 
            }

            if($coaching){
                if($previous){
                    $coaching->previous = new CoachingResource($previous);
                }else{
                    $coaching->previous = null;
                }
            }

            $transform = new CoachingResource($coaching);

            return $this->successResponse($transform, 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
        }

    }


    /**
     * Remove resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function remove(Request $request) {

        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'coaching_id' => 'required|exists:imp_coaching,id',
                'id' => 'required',
                'type' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'coaching_id.required' => 'Coaching id is required',
                'coaching_id.exists' => 'Coaching session Not Found',
                'id.required' => 'Note id is required',
                'type.required' => 'Note type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $impid = intval($request->input('coaching_id'));
            $assessment_id = intval($request->input('assessment_id'));
            $id = intval($request->input('id'));
            $type = $request->input('type');

            $coaching = ImpCoaching::findOrFail($impid);

            if($type == 'imp-revenue'){
                $coaching->coachingWeeklyRevenue()->where('id', $id)->delete();
            }

            if($type == 'imp-leads'){
                $coaching->coachingWeeklyLeads()->where('id', $id)->delete();
            }

            if($type == 'imp-appointments'){
                $coaching->coachingWeeklyAppointments()->where('id', $id)->delete();
            }

            if($type == 'imp-notes'){
                $coaching->coachingNotes()->where('id', $id)->delete();
            }

            if($type == 'need-to-complete'){
                $coaching->coachingActionsNeedComplete()->where('id', $id)->delete();
            }

            if($type == 'biggest-wins'){
                $coaching->coachingBiggestWins()->where('id', $id)->delete();
            }

            if($type == 'biggest-challenges'){
                $coaching->coachingBiggestChallenges()->where('id', $id)->delete();
            }

            if($type == 'client-must-complete'){
                $coaching->coachingActionsMustComplete()->where('id', $id)->delete();
            }

            if($type == 'coach-need-to-complete'){
                $coaching->coachingCoachesHelp()->where('id', $id)->delete();
            }

            if((strlen($request->input('previous_path')) == 0) || ($request->input('previous_path') == 'planning-meeting') || ($request->input('previous_path') == 'quarterly-review')){
               $previous = null;
            }else{
               $previous = $this->getPrevious($request->input('previous_path'), $request->input('previous_nid'), $assessment_id); 
            }

            if($coaching){
                if($previous){
                    $coaching->previous = new CoachingResource($previous);
                }else{
                    $coaching->previous = null;
                }
            }

            $transform = new CoachingResource($coaching);

            return $this->successResponse($transform, 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        try {
            $response = ImpCoaching::findOrFail($id);

            $transform = new CoachingResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That Implementation Coaching does not exist', 200);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {

        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {

        

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        try{
            $coaching = ImpCoaching::findOrFail($id);


            if ($coaching->coachingWeeklyRevenue) {
                $coaching->coachingWeeklyRevenue()->delete();// Delete all weekly revenue associated
            }

            if ($coaching->coachingWeeklyLeads) {
                $coaching->coachingWeeklyLeads()->delete();// Delete all weekly leads associated
            }

            if ($coaching->coachingWeeklyAppointments) {
                $coaching->coachingWeeklyAppointments()->delete();// Delete all weekly appointments associated
            }

            if ($coaching->coachingNotes) {
                $coaching->coachingNotes()->delete();// Delete all notes associated
            }

            if ($coaching->coachingActions) {
                $coaching->coachingActions()->delete();// Delete all actions associated
            }

            if ($coaching->coachingActionsMustComplete) {
                $coaching->coachingActionsMustComplete()->delete();// Delete all actions-must-complete associated
            }

            if ($coaching->coachingActionsNeedComplete) {
                $coaching->coachingActionsNeedComplete()->delete();// Delete all actions-need-complete associated
            }

            if ($coaching->coachingBiggestChallenges) {
                $coaching->coachingBiggestChallenges()->delete();// Delete all biggest challenges associated
            }

            if ($coaching->coachingBiggestWins) {
                $coaching->coachingBiggestWins()->delete();// Delete all biggest wins associated
            }

            if ($coaching->coachingCoachesHelp) {
                $coaching->coachingCoachesHelp()->delete();// Delete all coaches help associated
            }

            $coaching->delete();

            return $this->singleMessage('Implementation coaching Deleted' ,201);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Implementation coaching record does not exist', 404);
        }

    }
}

