<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Helpers\Cypher;
use App\Models\User;
use App\Models\Role;
use App\Jobs\ProcessEmail;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\FlashCoaching;
use App\Models\FlashCoachingAccess;
use App\Models\FlashCoachingProgress;
use App\Models\FlashCoachingAppointment;
use App\Models\CoachingActionStep;
use App\Models\Assessment;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\TrainingAccess;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Notifications\NewFlashCoachingStudent;
use App\Notifications\AppointmentDelete;
use App\Notifications\AppointmentNew;
use App\Notifications\NewStudent;
use App\Models\FlashCoachingAnalysis;
use App\Http\Resources\Coach;
use App\Http\Resources\FlashCoaching as FlashResource;
use App\Http\Resources\FlashCoachingStudent as StudentResource;
use App\Http\Resources\FlashCoachingProgress as ProgressResource;
use App\Http\Resources\FlashCoachingAppointment as  AppointmentResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\FlashCoachingAnalysis as AnalysisResource;
use App\Http\Resources\CoachingActionSteps as CoachingActionStepsResource;



class FlashCoachingController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function progress(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required',
                'assessment_id' => 'required',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
                'assessment_id.required' => 'The assessment id is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $company_id = $cypher->decryptID(env('HASHING_SALT'), $request->company_id);
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);

            $assessment = Assessment::findOrfail($assessment_id); //Get by id
            $company = Company::findOrfail($company_id); //Get by id
            
            // Get student/company progress
            $progress = DB::table('flash_coaching_progress')
            ->join('users', 'users.id', '=', 'flash_coaching_progress.student_id')
            ->select(
                'flash_coaching_progress.id', 
                'flash_coaching_progress.coach_id', 
                'flash_coaching_progress.company_id', 
                'flash_coaching_progress.student_id', 
                'users.first_name as student_first_name', 
                'users.last_name as student_last_name', 
                'users.company as company_name', 
                'users.email as student_email',
                'flash_coaching_progress.assessment_id',
                'flash_coaching_progress.lesson_id',
                'flash_coaching_progress.path',
                'flash_coaching_progress.access',
                'flash_coaching_progress.progress',
                )
            ->where('flash_coaching_progress.company_id', $company_id)
            ->where('flash_coaching_progress.assessment_id' , $assessment_id)->get();

            $transform = ProgressResource::collection($progress);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching progress not found', 400);
        }
    }

    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function prospects(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
            ];

            $messages = [
                'coach_id.required' => 'The coach id is required',
                'coach_id.exists' => 'That coach dont exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id = trim($request->coach_id);

            $coach = User::findOrFail($coach_id);

            // Get current flash coaching students
            $students = DB::table('flash_coaching_access')
                    ->select('flash_coaching_access.student_id')
                    ->where('flash_coaching_access.coach_id', $coach_id)->where('flash_coaching_access.access', 1)->get();
            
            // Pluck  the students IDs
            $current = $students->pluck('student_id')->all(); 

            // Get all coaches contacts emails
            $all = $coach->companies->pluck('contact_email')->all(); 

            // Remove the coach's email from the current list
            $filtered = array_filter($all, function ($e) {
                return $e != $this->user->email;
            });

            // Fetch the flash coaching prospects
            $p = DB::table('users')
                    ->select('users.id')
                    ->whereIn('email', $filtered)->whereNotIn('id', $current)->get();

            // Pluck  the prospects IDs
             $prospects_ids = $p->pluck('id')->all();
            
            // Get flash coaching student prospects
            $prospects = DB::table('users')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                    ->whereIn('id', $prospects_ids)->get();

            $stds = StudentResource::collection($prospects);

            return $this->showMessage($stds, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching students not found', 400);
        }
    }

    
    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newexistingcontact(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
                'student_id' => 'required|exists:users,id',
            ];

            $messages = [
                'coach_id.required' => 'The coach id is required',
                'coach_id.exists' => 'That coach dont exist',
                'student_id.required' => 'The student id is required',
                'student_id.exists' => 'That student dont exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id = trim($request->coach_id);
            $student_id = trim($request->student_id);


            $coach = User::findOrFail($coach_id);
            $student = DB::table('users')->where('id', trim($student_id))->first();
            $student = User::hydrate([$student])->first();

            // Add studnet to flash coaching program
                    
            TrainingAccess::updateOrCreate(['user_id' => $student_id],['flash_coaching' => 1]);
            
            // Add access to flash-coaching program associated to the coach
            FlashCoachingAccess::updateOrCreate(['student_id' => $student_id],['coach_id' => $coach_id, 'access' => 1]);

            // Notify client/student about the new add
            $student->notify(new NewFlashCoachingStudent($student, $coach));

            $students = DB::table('flash_coaching_access')
            ->join('users', 'users.id', '=', 'flash_coaching_access.student_id')
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
            ->where('flash_coaching_access.coach_id', $coach_id)->where('flash_coaching_access.access', 1)->get();

            $students_list = StudentResource::collection($students);


            // Now get the current prospects

            // Get current flash coaching students
            $students = DB::table('flash_coaching_access')
                    ->select('flash_coaching_access.student_id')
                    ->where('flash_coaching_access.coach_id', $coach_id)->where('flash_coaching_access.access', 1)->get();
            
            // Pluck  the students IDs
            $current = $students->pluck('student_id')->all(); 

            // Get all coaches contacts emails
            $all = $coach->companies->pluck('contact_email')->all(); 

            // Remove the coach's email from the current list
            $filtered = array_filter($all, function ($e) {
                return $e != $this->user->email;
            });

            // Fetch the flash coaching prospects
            $p = DB::table('users')
                    ->select('users.id')
                    ->whereIn('email', $filtered)->whereNotIn('id', $current)->get();

            // Pluck  the prospects IDs
             $prospects_ids = $p->pluck('id')->all();
            
            // Get flash coaching student prospects
            $prospects = DB::table('users')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                    ->whereIn('id', $prospects_ids)->get();

            $prospects_list = StudentResource::collection($prospects);

            return $this->showMessage(['students' => $students_list, 'prospects' => $prospects_list], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching students not found', 400);
        }
    }

    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function progressUpdate(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'assessment_id' => 'required|exists:assessments,id',
                'user_id' => 'required|exists:users,id',
                'lesson_id' => 'required',
                'checked' => 'required',
                'path' => 'required',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company dont exist',
                'assessment_id.required' => 'The assessment id is required',
                'assessment_id.exists' => 'That assessment doe not exist',
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user dont exist',
                'lesson_id.required' => 'The lesson_id is required',
                'checked.required' => 'The checked is required',
                'path.required' => 'The path is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $assessment_id = $request->assessment_id;
            $student_id = $request->user_id;
            $lesson_id = $request->lesson_id;
            $checked = trim($request->checked);
            $path = $request->path;

            $cu = CompanyUser::where('company_id' , $company_id )->first(['user_id']);

            FlashCoachingProgress::updateOrCreate(
            [
                'student_id' => $student_id,
                'company_id' => $company_id,
                'assessment_id' => $assessment_id,
                'lesson_id' => $lesson_id,
                'coach_id' => $cu->user_id,
                'path' => $path,
            ], ['progress' => $checked]);

            // Get student/company progress
            $progress = $this->getProgress($company_id);

            $transform = ProgressResource::collection($progress);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching progress not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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

            // Get lesson progress
            $progress = DB::table('flash_coaching_progress')
            ->join('users', 'users.id', '=', 'flash_coaching_progress.student_id')
            ->select(
                'flash_coaching_progress.id', 
                'flash_coaching_progress.coach_id', 
                'flash_coaching_progress.company_id', 
                'flash_coaching_progress.student_id', 
                'users.first_name as student_first_name', 
                'users.last_name as student_last_name', 
                'users.company as company_name', 
                'users.email as student_email',
                'flash_coaching_progress.assessment_id',
                'flash_coaching_progress.lesson_id',
                'flash_coaching_progress.path',
                'flash_coaching_progress.access',
                'flash_coaching_progress.progress',
                )
            ->where('flash_coaching_progress.coach_id', $coach_id)
            ->where('flash_coaching_progress.lesson_id' , $lesson_id)
            ->where('flash_coaching_progress.access' , 1)->get();

            $transform = ProgressResource::collection($progress);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching progress not found', 400);
        }
    }



    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toggle(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'assessment_id' => 'required|exists:assessments,id',
                'path' => 'required',
                'access' => 'required',
                'student_email' => 'required',
                'lesson_ids' => 'required',
            ];

            $messages = [
                'path.required' => 'The path is required',
                'student_email.required' => 'The student email is required',
                'access.required' => 'The access is required',
                'lesson_ids.required' => 'The lesson ids are required',
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company doe not exist',
                'assessment_id.required' => 'The assessment id is required',
                'assessment_id.exists' => 'That assessment doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $assessment_id = $request->assessment_id;
            $student_email = $request->student_email;
            $lesson_ids = $request->lesson_ids;

            // Enable flash coaching for the client
            
            // Get user
            $user = DB::table('users')->where('email', $student_email)->first();
            
            if($user){
                $huser = User::hydrate([$user])->first();
                $array = ['flash_coaching' => 1];
                // The update the flash coaching status
                $huser->trainingAccess()->updateOrCreate(['user_id' => $huser->id], $array);

                foreach ($lesson_ids as $key => $value) {
                    FlashCoachingProgress::updateOrCreate(
                    [
                        'coach_id' => $this->user->id,
                        'student_id' => $huser->id,
                        'company_id' => $company_id,
                        'assessment_id' => $assessment_id,
                        'lesson_id' => $value,
                        'path' => $request->path,
                    ], ['access' => (int)$request->access]);
                }

            }

            $progress = FlashCoachingProgress::where('company_id', $company_id)->where('assessment_id', $assessment_id)->get();

            $transform = ProgressResource::collection($progress);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching progress not found', 400);
        }
    }

    private function getProgress($company_id){
        // Get student/company progress
        return DB::table('flash_coaching_progress')
        ->join('users', 'users.id', '=', 'flash_coaching_progress.student_id')
        ->select(
            'flash_coaching_progress.id', 
            'flash_coaching_progress.coach_id', 
            'flash_coaching_progress.company_id', 
            'flash_coaching_progress.student_id', 
            'users.first_name as student_first_name', 
            'users.last_name as student_last_name', 
            'users.company as company_name', 
            'users.email as student_email',
            'flash_coaching_progress.assessment_id',
            'flash_coaching_progress.lesson_id',
            'flash_coaching_progress.path',
            'flash_coaching_progress.access',
            'flash_coaching_progress.progress',
            )
        ->where('flash_coaching_progress.company_id', $company_id)->get();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clientanalysis(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $company = Company::findOrfail($company_id);
            $contact = $company->contact();
            $coach = $company->coach();

            // Get student/company progress
            $p = $this->getProgress($company_id);

            $n = FlashCoachingAnalysis::where('company_id', $company_id)->get();

            $progress = ProgressResource::collection($p);

            $a = $this->getClientUpcomingAppointments($contact->id);

            $appointments = AppointmentResource::collection($a);
            
            $analysis = AnalysisResource::collection($n);
            
            $co = ($coach)? new Coach($coach) : null;

            return $this->successResponse(['coach' => $co, 'progress' => $progress, 'appointments' => $appointments, 'analysis' => $analysis], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching analysis not found', 400);
        }
    }


    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function analysis(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $company_id = $cypher->decryptID(env('HASHING_SALT'), $request->company_id);

            $company = Company::findOrfail($company_id); //Get by id

            $access = FlashCoaching::where('company_id', $company_id)->get();
            $analysis = FlashCoachingAnalysis::where('company_id', $company_id)->get();
            $action_steps = CoachingActionStep::where('company_id', $company_id)->get();

            $a = FlashResource::collection($access);
            $b = AnalysisResource::collection($analysis);
            $c = CoachingActionStepsResource::collection($action_steps);

            return $this->successResponse(['access' => $a, 'analysis' => $b, 'action_steps' => $c], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching analysis not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function savenotes(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'user_id' => 'required|exists:users,id',
                'video_id' => 'required',
                'path' => 'required',
                'notes' => 'required',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company doe not exist',
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user doe not exist',
                'video_id.required' => 'The video id is required',
                'notes.required' => 'The notes are required',
                'path.required' => 'The path is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $video_id = $request->video_id;
            $company_id = $request->company_id;
            $path = $request->path;
            $notes = trimSpecial(strip_tags(trim($request->notes)));

            $analysis = FlashCoachingAnalysis::firstOrNew(['user_id' => $user_id, 'company_id' => $company_id, 'path' => $path, 'video_id' => $video_id ]);
            
            $html = Str::markdown($notes);
            $analysis->notes = $html;
            $analysis->save();

            $transform = new AnalysisResource($analysis);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching analysis not found', 400);
        }
    }

    
    
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function videoAnalysis(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $access = FlashCoaching::where('company_id', $request->company_id)->get();
            $analysis = FlashCoachingAnalysis::where('company_id', $request->company_id)->get();

            $a = FlashResource::collection($access);
            $b = AnalysisResource::collection($analysis);

            return $this->successResponse(['access' => $a, 'analysis' => $b], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching analysis not found', 400);
        }
    }
    


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
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

            foreach ($request->input('data')  as $key => $each) {

                if(isset($each['user_id'])){

                    $user_id = $each['user_id'];
                    $video_id = $each['video_id'];
                    $company_id = $each['company_id'];
                    $path = $each['path'];

                    $analytics = FlashCoachingAnalysis::firstOrNew(['user_id' => $user_id, 'company_id' => $company_id, 'path' => $path, 'video_id' => $video_id ]);

                    if(isset($each['video_name'])){
                        $analytics->video_name = $each['video_name'];
                    }

                    if(isset($each['video_progress'])){
                        
                        $found = (float)$analytics->video_progress;
                        $new = (float)$each['video_progress'];

                        if($new > $found){
                            $analytics->video_progress = $each['video_progress'];
                        }
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

                    if($analytics->isDirty()){
                        $analytics->save();
                    }
                }
                
            }

            $transform = AnalysisResource::collection($user->flashanalysis()->get()); 

            return $this->successResponse($transform, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        } 
    }

    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAppointmentUrl(Request $request)
    {
        try {

            $rules = [
                'appointment_id' => 'required|exists:flash_coaching_appointments,id',
                'coach_id' => 'required|exists:users,id',
                'meeting_url' => 'required',
                'share_url' => 'required',
                'type' => 'required',
            ];

            $messages = [
                'appointment_id.required' => 'Appointment ID is required',
                'appointment_id.exists' => 'Appointment Not Found',
                'coach_id.required' => 'User ID is required',
                'coach_id.exists' => 'User Not Found',
                'meeting_url.required' => 'Appointment URL is required',
                'share_url.required' => 'Share URL is required',
                'type.required' => 'URL type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->appointment_id;
            $coach_id = $request->coach_id;
            $url = trim($request->meeting_url);
            $type = trim($request->type);
            $appointment = FlashCoachingAppointment::findOrFail($id);
            
            $appointment->meeting_url = $url;
            $appointment->type = $type;

            $appointment->save();

            if ((bool)$request->share_url) {
                $coach = User::findOrFail($coach_id);
                $coach->meeting_url = $url;
                $coach->save();
            }

            $appointment = $appointment->refresh();

            $transform = new AppointmentResource($appointment);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Appointment not found', 400);
        }
    }




    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAppointment(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
                'appointment_id' => 'required|exists:flash_coaching_appointments,id',
                'meeting_time' => 'required',
                'updated_by' => 'required',
            ];

            $messages = [
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'Coach Not Found',
                'appointment_id.required' => 'Appointment ID is required',
                'appointment_id.exists' => 'Appointment Not Found',
                'meeting_time.required' => 'Meeting time is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id = $request->coach_id;
            $appointment_id = $request->appointment_id;
            $meeting_time = trim($request->meeting_time);

            $appointment = FlashCoachingAppointment::findOrFail($appointment_id);
            $appointment->meeting_time = $meeting_time;

            $appointment->save();

            if($request->has('updated_by') && $request->updated_by == 'student'){
                $appointments = $this->getClientUpcomingAppointments($appointment->student_id);
            }else{
                $appointments = $this->getCoachUpcomingAppointments($coach_id);
            }

            $transform = AppointmentResource::collection($appointments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Uset not found', 400);
        }
    }



    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteAppointment(Request $request)
    {
        try {

            $rules = [
                'appointment_id' => 'required|exists:flash_coaching_appointments,id',
            ];

            $messages = [
                'appointment_id.required' => 'Appointment ID is required',
                'appointment_id.exists' => 'Appointment Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $appointment_id = $request->appointment_id;

            $appointment = FlashCoachingAppointment::findOrFail($appointment_id);
            
            $coach_id = $appointment->coach_id;
            $student_id = $appointment->student_id;
            
            $coach = User::findOrFail($coach_id);
            $student = User::findOrFail($student_id);

            // Notify coach about the deletion
            $coach->notify(new AppointmentDelete($student, $appointment));

            $appointment->delete();

            $appointments = $this->getClientUpcomingAppointments($student_id);

            $transform = AppointmentResource::collection($appointments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Uset not found', 400);
        }
    }

    /**
     * Create resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newAppointment(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
                'student_id' => 'required|exists:users,id',
                'meeting_time' => 'required',
            ];

            $messages = [
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'Coach Not Found',
                'student_id.required' => 'Student ID is required',
                'student_id.exists' => 'Student Not Found',
                'meeting_time.required' => 'Meeting time is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id = $request->coach_id;
            $student_id = $request->student_id;
            $company_id = $request->company_id;
            $meeting_time = trim($request->meeting_time);
            
            $appointment = new FlashCoachingAppointment;
            $appointment->student_id = $student_id;
            $appointment->coach_id = $coach_id;
            $appointment->company_id = $company_id;
            $appointment->meeting_time = $meeting_time;

            if ($request->has('meeting_url') && !empty($request->meeting_url)) {
                $meeting_url = trim($request->meeting_url);
                $appointment->meeting_url = $meeting_url;
            }

            if ($request->has('type') && !empty($request->type)) {
                $type = trim($request->type);
                $appointment->type = $type;
            }

            $appointment->save();
            
            $coach = User::findOrFail($coach_id);
            $student = User::findOrFail($student_id);

            // Notify coach about the new appointment
            $coach->notify(new AppointmentNew($student, $appointment));

            $appointments = $this->getClientUpcomingAppointments($student_id);
            
            $transform = AppointmentResource::collection($appointments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Uset not found', 400);
        }
    }


    private function getCoachUpcomingAppointments($coach_id){
        
        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Get up-coming appointments
        $appointments = DB::table('flash_coaching_appointments')
        ->join('users', 'users.id', '=', 'flash_coaching_appointments.student_id')
        ->select(
            'flash_coaching_appointments.id', 
            'flash_coaching_appointments.coach_id', 
            'flash_coaching_appointments.company_id', 
            'users.id as student_id', 
            'users.first_name as student_first_name', 
            'users.last_name as student_last_name', 
            'users.company as company_name', 
            'users.email as student_email',
            'flash_coaching_appointments.meeting_time',
            'flash_coaching_appointments.meeting_url',
            'flash_coaching_appointments.time_zone',
            'flash_coaching_appointments.type',
            )
        ->where('flash_coaching_appointments.coach_id', $coach_id)
        ->where('flash_coaching_appointments.meeting_time' , '>=' , $today)->orderBy('flash_coaching_appointments.meeting_time', 'ASC')->get();

        return $appointments;
    }



    private function getClientUpcomingAppointments($student_id){
        
        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Get up-coming appointments
        $appointments = DB::table('flash_coaching_appointments')
        ->join('users', 'users.id', '=', 'flash_coaching_appointments.student_id')
        ->select(
            'flash_coaching_appointments.id', 
            'flash_coaching_appointments.coach_id', 
            'flash_coaching_appointments.company_id', 
            'users.id as student_id', 
            'users.first_name as student_first_name', 
            'users.last_name as student_last_name', 
            'users.company as company_name', 
            'users.email as student_email',
            'flash_coaching_appointments.meeting_time',
            'flash_coaching_appointments.meeting_url',
            'flash_coaching_appointments.time_zone',
            'flash_coaching_appointments.type',
            )
        ->where('flash_coaching_appointments.student_id', $student_id)
        ->where('flash_coaching_appointments.meeting_time' , '>=' , $today)->orderBy('flash_coaching_appointments.meeting_time', 'ASC')->get();

        return $appointments;
    }



    /**
     * Retrive some details about flash coaching.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function details(Request $request)
    {
        try {

            $rules = [
                'coach_id' => 'required|exists:users,id',
            ];

            $messages = [
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'That coach doesnt exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach_id  = $request->coach_id;
            
            // Get students
            $students = DB::table('flash_coaching_access')
                    ->join('users', 'users.id', '=', 'flash_coaching_access.student_id')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                    ->where('flash_coaching_access.coach_id', $coach_id)->where('flash_coaching_access.access', 1)->get();

            $stds = StudentResource::collection($students);

            return $this->showMessage(['students' => $stds], 200);
            
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    private function findAvailableMeetingURL($list){
        $result = '';
        foreach (get_object_vars($list) as $key => $value) {
            if($value != null){
                $result = $key;
                break;
            }
        }
        return $result;
    }

    private function getCoachMeetingURLs($coach){

        $urls = [];

        if($coach->calendarurls){
            $urls = (object) [
                "fifteen_url" => $coach->calendarurls->fifteen_url,
                "thirty_url" => $coach->calendarurls->thirty_url,
                "forty_five_url" => $coach->calendarurls->forty_five_url,
                "sixty_url" => $coach->calendarurls->sixty_url,
            ];
        }

        return $urls;
    }


    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function appointmentReminder(Request $request)
    {
        try {

            $rules = [
                'appointment_id' => 'required|exists:flash_coaching_appointments,id',
            ];

            $messages = [
                'appointment_id.required' => 'Appointment ID is required',
                'appointment_id.exists' => 'Appointment Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $appointment_id = $request->appointment_id;
            $appointment = FlashCoachingAppointment::findOrFail($appointment_id);
            $coach_id = $appointment->coach_id;
            $student_id = $appointment->student_id;
            
            // $coach = User::findOrFail($coach_id);
            $student = User::findOrFail($student_id);

            // $urls = $this->getCoachMeetingURLs($coach);

            $date = Carbon::createFromDate($appointment->meeting_time);
            $mtime = $date->format('h:i A');
            $mdate = $date->isoFormat('dddd Do MMMM Y');

            // $url = '';

            // if(is_object($urls)){
            //     $url = (empty($urls->{$appointment->meeting_url}))? $urls->{$this->findAvailableMeetingURL($urls)} : $urls->{$appointment->meeting_url} ;
            // }

            $messages = [];

            if (strlen($appointment->meeting_url) > 0) {
                $messages[] = 'Just a friendly reminder that our Flash Coaching meeting session is on, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b> (' . $appointment->time_zone . '). <br/><br/> <a href=' . $appointment->meeting_url . ' target="_blank">Meeting Link</a> </br><br/> I’m looking forward to our session as we explore further ways to grow your business.';
            } else {
                $messages[] = 'Just a friendly reminder that our Flash Coaching meeting session is on, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b> (' . $appointment->time_zone . '). I’m looking forward to our session as we explore further ways to grow your business.';
            }

            $notice = [
                'client_name' => $student->first_name,
                'messages' => $messages,
                'user' => $this->user,
                'to' => $student->email,
                'copy' => [$this->user->email],
                'bcopy' => [],
                'subject' => 'Flash Coaching Appointment Reminder (' . $mdate . ')',
            ];

            ProcessEmail::dispatch($notice);

            return $this->showMessage('Reminder sent successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }



    /**
     * Remove resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function removeclient(Request $request)
    {
        try {

            $rules = [
                'student_id' => 'required|exists:users,id',
            ];

            $messages = [
                'student_id.required' => 'Student ID is required',
                'student_id.exists' => 'That student doesnt exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $student = User::findOrFail($request->student_id);
            
            if($student){

                TrainingAccess::updateOrCreate(['user_id' => $student->id],['flash_coaching' => 0]);
                    
                // Remove flash-coaching program access associated to this student
                FlashCoachingAccess::where(['student_id' => $student->id, 'coach_id' => $this->user->id ])->delete();
                
                // Remove flash-coaching video progress
                FlashCoachingProgress::where('student_id', $student->id)->where('coach_id', $this->user->id)->delete();
                
                // Remove flash-coaching appointments
                FlashCoachingAppointment::where('student_id', $student->id)->where('coach_id', $this->user->id)->delete();
                
                // Remove flash-coaching video analysis
                FlashCoachingAnalysis::where('user_id', $student->id)->delete();
    
                // Notify client/student about the removal
                $this->notifyFlashCoachingDeactivation($student);

                $students = DB::table('flash_coaching_access')
                ->join('users', 'users.id', '=', 'flash_coaching_access.student_id')
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                ->where('flash_coaching_access.coach_id', $this->user->id)->where('flash_coaching_access.access', 1)->get();

                $transform = StudentResource::collection($students);

                return $this->showMessage($transform, 200);   
                
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }


    private function notifyFlashCoachingDeactivation($student){

        $messages = [];

        $messages[] = 'You have been deactivated from Flash Coaching program by '.$this->user->first_name .' '.$this->user->last_name.'. Please reach out via email ('.$this->user->email.') for further details.';

        $notice = [
            'client_name' => $student->first_name,
            'messages' => $messages,
            'user' => $this->user,
            'to' => $student->email,
            'copy' => [],
            'bcopy' => [],
            'subject' => 'Flash Coaching Program - Deactivated',
        ];

        ProcessEmail::dispatch($notice);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newclient(Request $request)
    {
        try {

            $rules = [
                'company_name' => 'required|max:100',
                'first_name' => 'required|max:100',
                'last_name' => 'required|max:100',
                'email_address' => 'required|email',
                'coach_id' => 'required|exists:users,id',
            ];

            $messages = [
                'company_name.required' => 'Company name is required',
                'first_name.required' => 'First name is required',
                'last_name.required' => 'Last name is required',
                'email_address.required' => 'Email address is required',
                'email_address.email' => 'That is not a valid email address',
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'That coach doesnt exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $coach = User::findOrFail($request->coach_id);

            $user = DB::table('users')->where('email', trim($request->email_address))->first();
            
            if($user){

                $user = User::hydrate([$user])->first();

                // User already exists
                // Check  if the coach is already in a flash-coaching program to avoid overwriting the existing one
                // If user is in any flash-coaching  program
                    // => Notify coach and quit
                // Else 
                    // => Add the user to the flash-coaching program
                    // => Notify student about the new add

                if($user->id == $this->user->id){
                   return $this->showMessage("You are the coach of this flash-coaching program, you dont need to add yourself as a student", 200);  
                }

                if(isset($user->flashaccess)){
                    // This user is already in another flash-coaching program
                    return $this->showMessage("That user is already in another flash-coaching program", 200); 
                }else{
                    // Add flash coaching to their  profile

                    // If the student doesnt have a company create it and own it
                    if(!$user->company_id){
                        $comp = new Company;
                        $comp->contact_first_name = trim($user->first_name);
                        $comp->contact_last_name = trim($user->last_name);
                        $comp->contact_email = trim($user->email);
                        $comp->company_name = $user->first_name. ' - Company';

                        $comp->save();

                        $comp = $comp->refresh();
                        
                        CompanyUser::create([ 'company_id' => $comp->id, 'user_id' => $coach->id ]);
                        
                        $user->company_id = $comp->id;
                        $user->company = $comp->company_name;
                        $user->save();
                    }
                    
                    TrainingAccess::updateOrCreate(['user_id' => $user->id],['flash_coaching' => 1]);
                    
                    // Add access to flash-coaching program associated to the coach
                    FlashCoachingAccess::updateOrCreate(['student_id' => $user->id],['coach_id' => $coach->id, 'access' => 1]);

                    // Notify client/student about the new add
                    $user->notify(new NewFlashCoachingStudent($user, $coach));

                    $students = DB::table('flash_coaching_access')
                    ->join('users', 'users.id', '=', 'flash_coaching_access.student_id')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                    ->where('flash_coaching_access.coach_id', $coach->id)->where('flash_coaching_access.access', 1)->get();

                    $transform = StudentResource::collection($students);

                    return $this->showMessage($transform, 201);
                }    
                
            }else{
                // User doesnt exists
                // Create a user and company 
                // Send login credentials to the new user 
                // Add the user to the flash-coaching program
                // Notify student about the new add

                $company = new Company;
                $company->contact_first_name = trim($request->first_name);
                $company->contact_last_name = trim($request->last_name);
                $company->contact_email = trim($request->email_address);
                $company->company_name = trim($request->company_name);

                $company->save();

                $company = $company->refresh();
                
                CompanyUser::create([ 'company_id' => $company->id, 'user_id' => $coach->id ]);

                $status = $this->allocateStudent($company, $coach);

                if($status){
                    $students = DB::table('flash_coaching_access')
                    ->join('users', 'users.id', '=', 'flash_coaching_access.student_id')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.company_id', 'users.company', 'users.email')
                    ->where('flash_coaching_access.coach_id', $coach->id)->where('flash_coaching_access.access', 1)->get();

                    $transform = StudentResource::collection($students);

                    return $this->showMessage($transform, 201);
                }else{
                    return $this->showMessage("Unable to add student", 201);
                }
                
            }

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    public function allocateStudent($company, $coach){
        
        $password = (string)rand(1000000, 9999999);
        $nuser = new User;
        $nuser->email = $company->contact_email;
        $nuser->first_name = $company->contact_first_name;
        $nuser->last_name = $company->contact_last_name;
        $nuser->company = $company->company_name;
        $nuser->password = $password;
        $nuser->role_id = 10; // Student or Client Role	
        $nuser->company_id = $company->id;
        $nuser->created_by_id = $coach->id;

        $nuser->save();
        $role = Role::findOrFail(10);
        $nuser->assignRole($role);

        $nuser = $nuser->refresh();

        // Dont send a notification to student/client at this time
        $nuser->notify(new NewStudent($nuser, $password));

        // Add flash coaching to their  profile
        TrainingAccess::firstOrCreate(
        [
            'user_id' => $nuser->id,
            'training_software' => 0,
            'training_100k' => 0,
            'training_lead_gen' => 0,
            'group_coaching' => 0,
            'prep_roleplay' => 0,
            'training_jumpstart' => 0,
            'flash_coaching' => 1,
            'coaching_action_steps' => 0,
        ]);
        
        // Add access to flash-coaching program associated to the coach
        FlashCoachingAccess::firstOrCreate(
        [
            'coach_id' => $coach->id,
            'student_id' => $nuser->id,
            'access' => 1,
        ]);

        // Notify client/student about the new add
        $nuser->notify(new NewFlashCoachingStudent($nuser, $coach));

        return true;
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
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
    }

    
}
