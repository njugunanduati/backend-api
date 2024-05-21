<?php

namespace App\Http\Controllers;

use Validator;
use Notification;
use App\Models\TrainingAnalytic;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Notifications\TicketSent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TicketRequest;
use Illuminate\Notifications\Notifiable;
use App\Http\Resources\TrainingAnalyticResource as AnalyticsResource;
use App\Http\Resources\TrainingMiniAnalyticsResource as MiniAnalyticsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class TrainingAnalyticsController extends Controller
{
    use ApiResponses, Notifiable;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $analytics = TrainingAnalytic::orderBy('id', 'DESC')->get(); //Get all analytics by id

            $transform = AnalyticsResource::collection($analytics);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }

    /**
     * Display a listing 60 records every time of the resource by last ID
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        try {

            $query = $request->input('query');
            $last_id = $request->input('last');

            if(empty($query)){
                if(empty($last_id)){
                    $analytics = TrainingAnalytic::where('id', '>', 1)->orderBy('id')->limit(60)->get();
                }else{
                    $analytics = TrainingAnalytic::where('id', '>', $last_id)->orderBy('id')->limit(60)->get();
                } 
            }else{
                if(empty($last_id)){

                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.*')
                    ->where(function($q) use ($query){
                            $q->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                        })->orderBy('user_training_analysis.id')->get();
                        $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                }else{
                    $analytics = DB::table('user_training_analysis')
                    ->join('users', 'users.id', '=', 'user_training_analysis.user_id')
                    ->select('user_training_analysis.*')
                    ->where('user_training_analysis.id', '>', $last_id)
                    ->where(function($q) use ($query){
                            $q->where('users.first_name', 'LIKE', '%'.$query.'%')->orWhere('users.last_name', 'LIKE', '%'.$query.'%');
                        })->orderBy('user_training_analysis.id')->get();
                        $analytics = TrainingAnalytic::hydrate($analytics->toArray());
                }
            }

            $transform = AnalyticsResource::collection($analytics);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }


    /**
     * Display a listing of the resource by user id.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchByUser(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:users,id',
                'type' => 'required',
            ];

            $messages = [
                'id.required' => 'User ID is required',   
                'id.exists' => 'That user does not exist',
                'type.required' => 'Type of search is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->id;
            $type = $request->type;

            $user = User::findOrFail($user_id);

            $transform = ($type == 'simple')? MiniAnalyticsResource::collection($user->trainings()->get()) : AnalyticsResource::collection($user->trainings()->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $rules = [
                'data' => 'required',
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'data.required' => 'Data is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::findOrFail($request->user_id);

            $isOnboarding = false;
            $types = ['pas-roleplay-prep', 'jumpstart-12-training', '100k', 'pas-training', 'onboarding-business-academy'];

            foreach ($request->input('data')  as $key => $each) {

                if(isset($each['user_id'])){

                    $type = $each['type'];
                    $user_id = $each['user_id'];
                    $video_id = $each['video_id'];

                    $isOnboarding = in_array($type, $types);

                    $analytics = TrainingAnalytic::firstOrNew(['type' => $type, 'user_id' => $user_id, 'video_id' => $video_id ]);

                    if(isset($each['video_name'])){
                        $analytics->video_name = $each['video_name'];
                    }

                    if(isset($each['group_id'])){
                        $analytics->group_id = $each['group_id'];
                    }

                    if(isset($each['user_group_id'])){
                        $analytics->user_group_id = $each['user_group_id'];
                    }

                    if(isset($each['video_progress'])){
                        
                        $found = (float)$analytics->video_progress;
                        $new = (float)$each['video_progress'];

                        if($new > $found){
                            $analytics->video_progress = $each['video_progress'];
                        }
                    }

                    if(isset($each['quiz_score'])){
                        $analytics->quiz_score = $each['quiz_score'];
                    }

                    if(isset($each['quiz_answers'])){
                        $analytics->quiz_answers = $each['quiz_answers'];
                    }

                    if(isset($each['quiz_url'])){
                        $analytics->quiz_url = $each['quiz_url'];
                    }

                    if(isset($each['video_time_watched'])){
                        $found = (float)$analytics->video_time_watched;
                        $new = (float)$each['video_time_watched'];
                        if($new > $found){
                            $analytics->video_time_watched = $each['video_time_watched'];
                        }
                    }

                    if(isset($each['video_length'])){
                        $analytics->video_length = $each['video_length'];
                    }

                    if(isset($each['quiz_correct_answers'])){
                        $analytics->quiz_correct_answers = $each['quiz_correct_answers'];
                    }

                    if(isset($each['quiz_total_questions'])){
                        $analytics->quiz_total_questions = $each['quiz_total_questions'];
                    }

                    if($analytics->isDirty()){
                        $analytics->save();
                    }
                }
                
            }

            if($isOnboarding){ // Get just the onboarding analysis
                $transform = MiniAnalyticsResource::collection($user->trainings()->whereIn('type', $types)->get());
            }else{
                $transform = MiniAnalyticsResource::collection($user->trainings()->get());  
            }

            return $this->successResponse($transform, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ticket not found', 400);
        }

     
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\TrainingAnalytic  $training_analytic
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {
            $analytic = TrainingAnalytic::findOrFail($id);

            $transform = new AnalyticsResource($analytic);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Training Analytics not found', 400);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\TrainingAnalytic  $training_analytic
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
       
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\TrainingAnalytic  $training_analytic
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $analytic = TrainingAnalytic::findOrFail($id);
            $analytic->delete();

            return $this->singleMessage('Record Deleted', 202);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Record not found', 400);
        }
    }
}
