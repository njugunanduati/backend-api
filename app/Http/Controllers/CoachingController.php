<?php

namespace App\Http\Controllers;

use Validator;
use PDF;
use Carbon\Carbon;
// use Cypher
use App\Helpers\Cypher;
use App\Jobs\ProcessEmail;
use App\Models\User;
use App\Models\ImpStep;
use App\Models\QuotumLevelOne;
use App\Models\Assessment;
use App\Models\ImpSimplifiedStep;
use App\Models\MeetingNote;
use App\Models\Company;
use App\Models\CompanyFile;
use App\Models\Currency;
use App\Models\CompanyUser;
use App\Models\ImpCoaching;
use App\Models\Session;
use App\Models\TrainingAccess;
use App\Models\MeetingNoteOther;
use App\Models\MeetingNoteMetric;
use App\Models\MeetingNoteTask;
use App\Models\MeetingNoteSetting;
use App\Models\MeetingNoteReminder;
use App\Models\MeetingNoteReminderFile;
use App\Models\MeetingNoteImplementation;
use App\Models\FlashCoachingAccess;
use App\Models\MeetingNoteImplementationAction;
use App\Models\ClientMeetingNote;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\ImpStep as StepResource;
use App\Http\Resources\ImpSimplifiedStep as SimplifiedStepResource;
use App\Http\Resources\Session as SessionResource;
use App\Http\Resources\MeetingNote as NoteResource;
use App\Http\Resources\Appointment as AppointmentResource;
use App\Http\Resources\MeetingNoteTask as TaskResource;
use App\Http\Resources\MeetingNoteReminder as ReminderResource;
use App\Http\Resources\CompanyFile as CompanyFileResource;
use App\Http\Resources\TrainingAccess as TrainingAccessResource;
use App\Http\Resources\MeetingNoteSetting as SettingResource;
use App\Http\Resources\ImpCoachingResource as CoachingResource;
use App\Http\Resources\MeetingNoteFullMetric as MetricsFullResource;
use App\Http\Resources\MetricsClientResource as MetricsClientResource;
use App\Http\Resources\MeetingNoteImplementation as ImplementationResource;
use App\Http\Resources\CoachingActionSteps as CoachingActionStepsResource;
use App\Http\Resources\FlashCoachingAppointment as  FlashAppointmentResource;

use App\Http\Resources\Currency as CurrencyResource;
use App\Http\Resources\AssessmentSimple as AssessmentSimpleResource;
use App\Http\Resources\Company as CompanyResource;

use App\Http\Resources\MeetingNoteImplementationClient as ImplementationResourceClient;
use App\Http\Resources\ClientMeetingNote as ClientMeetingNoteResource;
use App\Http\Resources\MeetingSchedule as ClientMeetingSchedule;
use App\Models\CoachingActionStep;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CoachingController extends Controller
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

    private function getUserMetrics($user_id)
    {

        $metrics = DB::table('meeting_notes_metrics')
            ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
            ->join('companies', 'companies.id', '=', 'meeting_notes.company_id')
            ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
            ->select(
                'meeting_notes_metrics.*',
                'companies.company_name',
                'meeting_notes_settings.type',
                'meeting_notes_settings.name',
                'meeting_notes_settings.label'
            )
            ->where(function ($a) use ($user_id) {
                $a->where('meeting_notes.user_id', $user_id);
            })->orderBy('meeting_notes_metrics.id', 'ASC')->get();

        return MetricsFullResource::collection($metrics);
    }


    /**
     * Get initial coach details (Companies/clients, assessments, currencies).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function details(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;

            $user = User::findOrFail($user_id);

            $companies = $user->companies()->get(); // Get coach companies

            $company_ids = $companies->pluck('id')->all();

            $assessments = $user->assessments()->orderBy('id', 'DESC')->get(); // Get coach assessments
            $currencies = Currency::all(); //Get all currencies
            $appointments = MeetingNote::whereIn('company_id', $company_ids)->where('next_meeting_time', '>', Carbon::today())->where('coaching', '1')->where('closed', '1')->orderBy('next_meeting_time', 'ASC')->get();


            $a = CompanyResource::collection($companies);
            $b = AssessmentSimpleResource::collection($assessments);
            $c = CurrencyResource::collection($currencies);
            $d = AppointmentResource::collection($appointments);
            $e = $this->getUserMetrics($user_id);

            return $this->successResponse(['companies' => $a, 'assessments' => $b, 'currencies' => $c, 'appointments' => $d, 'metrics' => $e], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }



    /** 
     * Get initial client details (notes, notes-settings, files).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function notesdetails(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
            ];

            $cypher = new Cypher;
            $company_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->company_id));


            $validator = Validator::make(['company_id' => $company_id], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();
            $settings = MeetingNoteSetting::where('company_id', $company_id)->get();
            $steps = ImpStep::all(); //Get all Implementation steps
            $simplified_steps = ImpSimplifiedStep::all(); //Get all Implementation simplified steps

            // If no settings found
            // Create the default
            if (count($settings) === 0) {
                createMeetingNotesSettings($company_id);
                $settings = MeetingNoteSetting::where('company_id', $company_id)->get();
            }

            $files = CompanyFile::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

            $a = NoteResource::collection($notes);
            $b = SettingResource::collection($settings);
            $c = CompanyFileResource::collection($files);
            $d = StepResource::collection($steps);
            $e = SimplifiedStepResource::collection($simplified_steps);

            return $this->successResponse(['notes' => $a, 'settings' => $b, 'files' => $c, 'steps' => $d, 'simplifiedsteps' => $e], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Company not found', 400);
        }
    }




    /** 
     * Toggle client subscriptions and return the current subscriptions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscriptions(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $company = Company::findOrFail($request->company_id);
            
            $contact = $company->contact();
            
            $subscriptions = null;
            
            if($contact){

                $student = User::hydrate([$contact])->first();
                
                $subscriptions = TrainingAccess::where('user_id', $contact->id)->first();

                if ($request->input('subscriptions')) {

                    $list = json_decode($request->input('subscriptions'));

                    if(count($list) == 0){
                        $subscriptions->flash_coaching = 0;
                        $subscriptions->group_coaching = 0;
                        $subscriptions->save();
                        $this->notifyGroupCoachingDeactivation($student);
                        $this->notifyFlashCoachingDeactivation($student);
                    }else if(count($list) == 1){
                        foreach ($list as $key => $item) {
                            if($item == 'flash_coaching'){
                                // Toggle flash coaching on and 
                                if($subscriptions->flash_coaching == 0){
                                    $subscriptions->flash_coaching = 1;
                                }
                                if($subscriptions->group_coaching == 1){
                                    $subscriptions->group_coaching = 0;
                                    $this->notifyGroupCoachingDeactivation($student);
                                }

                            }else if($item == 'group_coaching'){
                                // Toggle group coaching
                                if($subscriptions->group_coaching == 0){
                                    $subscriptions->group_coaching = 1;
                                }
                                if($subscriptions->flash_coaching == 1){
                                    $subscriptions->flash_coaching = 0;
                                    $this->notifyFlashCoachingDeactivation($student);
                                }
                            }
                        }
                        $subscriptions->save();
                    }else{
                        foreach ($list as $key => $item) {
                            if($item == 'flash_coaching'){
                                // Toggle flash coaching on and 
                                if($subscriptions->flash_coaching == 0){
                                    $subscriptions->flash_coaching = 1;
                                }

                            }else if($item == 'group_coaching'){
                                // Toggle group coaching
                                if($subscriptions->group_coaching == 0){
                                    $subscriptions->group_coaching = 1;
                                }
                            }
                        }
                        $subscriptions->save();
                    }
                }
            }

            $a = ($subscriptions)? new TrainingAccessResource($subscriptions) : null;
        
            return $this->successResponse($a, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Company not found', 400);
        }
    }


    private function notifyFlashCoachingDeactivation($student){

        FlashCoachingAccess::updateOrCreate(
        [
            'coach_id' => $this->user->id,
            'student_id' => $student->id,
        ],['access' => 0]);

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


    private function notifyGroupCoachingDeactivation($student){

        $messages = [];

        $messages[] = 'You have been deactivated from Group Coaching program by '.$this->user->first_name .' '.$this->user->last_name.'. Please reach out via email ('.$this->user->email.') for further details.';

        $notice = [
            'client_name' => $student->first_name,
            'messages' => $messages,
            'user' => $this->user,
            'to' => $student->email,
            'copy' => [],
            'bcopy' => [],
            'subject' => 'Group Coaching Program - Deactivated',
        ];

        ProcessEmail::dispatch($notice);
    }



    /** 
     * Get coach commitments
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function coachCommitments()
    {
        try {

            $user_id = $this->user->id;

            $notes = MeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();

            if (count($notes) > 0) {
                $ids = $notes->pluck('id')->all();

                $commitments = MeetingNoteReminder::whereIn('meeting_note_id', $ids)->orderBy('id', 'DESC')->get();

                $transform = ReminderResource::collection($commitments);

                return $this->successResponse($transform, 200);
            } else {
                return $this->successResponse([], 200);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeing notes not found', 400);
        }
    }




    /** 
     * Get All initial client and coach details (notes, notes-settings, files, companies/clients, assessments, currencies).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function alldetails(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
            ];

            $cypher = new Cypher;
            $company_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->company_id));
            $user_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->user_id));

            $validator = Validator::make([
                'company_id' => $company_id,
                'user_id' => $user_id,
            ], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company = Company::findOrFail($company_id);
            $user = User::findOrFail($user_id);
            $contact = $company->contact();
            
            $subscriptions = null;
            
            if($contact){
                $subscriptions = TrainingAccess::where('user_id', $contact->id)->first();
            }
        
            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();
            $settings = MeetingNoteSetting::where('company_id', $company_id)->get();

            // If no settings found
            // Create the default
            if (count($settings) === 0) {
                createMeetingNotesSettings($company_id);
                $settings = MeetingNoteSetting::where('company_id', $company_id)->get();
            }

            $files = CompanyFile::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

            $companies = $user->companies()->get(); // Get coach companies
            $assessments = $user->assessments()->orderBy('id', 'DESC')->get(); // Get coach assessments
            $currencies = Currency::all(); //Get all currencies
            $steps = ImpStep::all(); //Get all Implementation steps
            $steps = ImpStep::all(); //Get all Implementation steps
            $simplified_steps = ImpSimplifiedStep::all(); //Get all Implementation simplified steps

            $a = NoteResource::collection($notes);
            $b = SettingResource::collection($settings);
            $c = CompanyFileResource::collection($files);
            $d = CompanyResource::collection($companies);
            $e = AssessmentSimpleResource::collection($assessments);
            $f = CurrencyResource::collection($currencies);
            $g = StepResource::collection($steps);
            $h = ($subscriptions)? new TrainingAccessResource($subscriptions) : null;
            $i = SimplifiedStepResource::collection($simplified_steps);

            return $this->successResponse(['notes' => $a, 'settings' => $b, 'files' => $c, 'companies' => $d, 'assessments' => $e, 'currencies' => $f, 'steps' => $g, 'simplifiedsteps' => $i, 'subscriptions' => $h], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Company or User not found', 400);
        }
    }



    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function oldMeetingNotes(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $sessions = $this->getOldMeetingNotes($company_id);

            if (count($sessions) > 0) {

                $transform = SessionResource::collection($sessions);

                return $this->successResponse($transform, 200);
            } else {
                return $this->successResponse([], 200);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }

    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function oldImpCoachingNotes(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $notes = $this->getOldImpCoachingNotes($company_id);

            if (count($notes) > 0) {

                $transform = CoachingResource::collection($notes);

                return $this->successResponse($transform, 200);
            } else {
                return $this->successResponse([], 200);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }


    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function oldTasks(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

            if (count($notes) > 0) {
                $ids = $notes->pluck('id')->all();

                $tasks = MeetingNoteTask::whereIn('meeting_note_id', $ids)->orderBy('id', 'DESC')->get();

                $transform = TaskResource::collection($tasks);

                return $this->successResponse($transform, 200);
            } else {
                return $this->successResponse([], 200);
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }



    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $custom_labels = [];
            $custom_placeholders = [];

            foreach ($request->all() as $key => $value) {
                if (substr($key, 0, 12) === "custom_label") {
                    $a = explode("_", $key);
                    $custom_labels[] = (object)['id' => $a[2], 'label' => $value];
                }
            }

            foreach ($request->all() as $key => $value) {
                if (substr($key, 0, 18) === "custom_placeholder") {
                    $a = explode("_", $key);
                    $custom_placeholders[] = (object)['id' => $a[2], 'placeholder' => $value];
                }
            }

            if (count($custom_labels) > 0) {
                foreach ($custom_labels as $key => $value) {

                    $id = $value->id;
                    $label = trim($value->label);

                    if (strlen($label) > 0) {
                        $setting = MeetingNoteSetting::findOrFail($id);
                        $setting->label = $label;
                        $setting->save();
                    }
                }
            }

            if (count($custom_placeholders) > 0) {
                foreach ($custom_placeholders as $key => $value) {

                    $id = $value->id;
                    $placeholder = trim($value->placeholder);

                    if (strlen($placeholder) > 0) {
                        $setting = MeetingNoteSetting::findOrFail($id);
                        $setting->placeholder = $placeholder;
                        $setting->save();
                    }
                }
            }

            $settings = MeetingNoteSetting::where('company_id', $company_id)->get();

            $transform = SettingResource::collection($settings);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
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
                'user_id' => 'required|exists:users,id',
                'appointment_id' => 'required|exists:meeting_notes,id',
                'next_meeting_time' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
                'appointment_id.required' => 'Appointment ID is required',
                'appointment_id.exists' => 'Appointment Not Found',
                'next_meeting_time.required' => 'Next meeting time is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $appointment_id = $request->appointment_id;
            $next_meeting_time = trim($request->next_meeting_time);

            $appointment = MeetingNote::findOrFail($appointment_id);
            $appointment->next_meeting_time = $next_meeting_time;

            $appointment->save();

            $user = User::findOrFail($user_id);

            $companies = $user->companies()->get(); // Get coach companies
            $company_ids = $companies->pluck('id')->all();
            $appointments = MeetingNote::whereIn('company_id', $company_ids)->where('next_meeting_time', '>', Carbon::today())->orderBy('next_meeting_time', 'ASC')->get();

            $transform = AppointmentResource::collection($appointments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
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
                'appointment_id' => 'required|exists:meeting_notes,id',
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
            $appointment = MeetingNote::findOrFail($appointment_id);
            $user_id = $appointment->user_id;

            $user = User::findOrFail($user_id);

            $date = Carbon::createFromDate($appointment->next_meeting_time);
            $mtime = $date->format('h:i A');
            $mdate = $date->isoFormat('dddd Do MMMM Y');

            $url = null;

            if ($appointment->meeting_url) {
                $url = $appointment->meeting_url;
            } elseif ($user->meeting_url) {
                $url = $user->meeting_url;
            }

            $messages = [];

            if ($url) {
                $messages[] = 'Just a friendly reminder that our meeting together is on, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b> (' . $appointment->time_zone . '). <br/><br/> <a href=' . $url . ' target="_blank">Meeting Link</a> </br><br/> I’m looking forward to our time as we explore further ways to grow your business.';
            } else {
                $messages[] = 'Just a friendly reminder that our meeting together is on, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b> (' . $appointment->time_zone . '). I’m looking forward to our time as we explore further ways to grow your business.';
            }

            $notice = [
                'client_name' => $user->first_name,
                'messages' => $messages,
                'user' => $this->user,
                'to' => $user->email,
                'copy' => [$this->user->email],
                'bcopy' => [],
                'subject' => 'Reminder of our appointment (' . $mdate . ')',
            ];

            ProcessEmail::dispatch($notice);

            return $this->showMessage('Reminder sent successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }



    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function appointmentDelete(Request $request)
    {
        try {

            $rules = [
                'appointment_id' => 'required|exists:meeting_notes,id',
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
            $appointment = MeetingNote::findOrFail($appointment_id);
            $user_id = $appointment->user_id;

            $user = User::findOrFail($user_id);

            $date = Carbon::createFromDate($appointment->next_meeting_time);
            $mtime = $date->format('h:i A');
            $mdate = $date->isoFormat('dddd Do MMMM Y');

            $messages = [];

            $messages[] = 'Just a notification that your appointment that was to be held on, <b>' . $mdate . '</b>, at <b>' . $mtime . '</b> (' . $appointment->time_zone . '). has been cancelled.';

            $notice = [
                'client_name' => $user->first_name,
                'messages' => $messages,
                'user' => $this->user,
                'to' => $user->email,
                'copy' => [$this->user->email],
                'bcopy' => [],
                'subject' => 'Appointment Cancelled (' . $mdate . ')',
            ];

            ProcessEmail::dispatch($notice);

            $companies = $this->user->companies()->get(); // Get coach companies
            $company_ids = $companies->pluck('id')->all();
            
            // Delete the appointmnet
            $appointment->delete();

            // Return the other appointments
            // coaching => This was a coaching session
            // closed => The coach had already held and saved the meeting notes for this meeting
            $appointments = MeetingNote::whereIn('company_id', $company_ids)->where('next_meeting_time', '>', Carbon::today())->where('coaching', '1')->where('closed', '1')->orderBy('next_meeting_time', 'ASC')->get();

            $transform = AppointmentResource::collection($appointments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Appointment or meeting note not found', 400);
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
                'id' => 'required|exists:meeting_notes,id',
                'url' => 'required',
            ];

            $messages = [
                'id.required' => 'Appointment ID is required',
                'id.exists' => 'Appointment Not Found',
                'url.required' => 'Appointment URL is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $url = trim($request->url);
            $appointment = MeetingNote::findOrFail($id);
            $appointment->meeting_url = $url;

            $appointment->save();

            $appointment = $appointment->refresh();

            $transform = new AppointmentResource($appointment);

            return $this->successResponse($transform, 200);

            return $this->showMessage('Appointment url updated successfully', 200);
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
    public function updateTimezone(Request $request)
    {
        try {

            $rules = [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'timezone' => 'required',
            ];

            $messages = [
                'meeting_note_id.required' => 'Note ID is required',
                'meeting_note_id.exists' => 'Meeting note Not Found',
                'timezone.required' => 'Timezone is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->meeting_note_id;
            $timezone = trim($request->timezone);
            $notes = MeetingNote::findOrFail($id);
            $notes->time_zone = $timezone;

            $notes->save();

            return $this->successResponse("Time zone updated", 200);

            return $this->showMessage('Appointment url updated successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Appointment not found', 400);
        }
    }




    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getImplementation(Request $request)
    {

        try {

            $rules = [
                'assessment_id' => 'required',
                'company_id' => 'required',
                'path' => 'required',
                'start_date' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'company_id.required' => 'Company ID is required',
                'path.required' => 'Path is required',
                'start_date.required' => 'Start date is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);
            $company_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->company_id);

            $assessment = Assessment::findOrfail($assessment_id); //Get by id
            $company = Company::findOrfail($company_id); //Get by id

            $path = $request->path;
            $start_date = $request->start_date;

            $impl = MeetingNoteImplementation::where('assessment_id', $assessment_id)->where('company_id', $company_id)->where('path', $path)->where('start_date', $start_date)->first();

            $transform = ($impl) ? new ImplementationResource($impl) : null;

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveImplementation(Request $request)
    {

        try {

            $rules = [
                'assessment_id' => 'required',
                'company_id' => 'required',
                'path' => 'required',
                'start_date' => 'required',
                'time' => 'required',
                'aid' => 'required',
                'complete' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'company_id.required' => 'Company ID is required',
                'aid.required' => 'Action ID is required',
                'path.required' => 'Path is required',
                'start_date.required' => 'Start date is required',
                'time.required' => 'Time is required',
                'complete.required' => 'Complete status is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $company_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->company_id);
            $assessment_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);
            $aid = $request->aid;
            $time = (int)$cypher->decryptID(env('HASHING_SALT'), $request->time);
            $complete = (int)$cypher->decryptID(env('HASHING_SALT'), $request->complete);

            $assessment = Assessment::findOrfail($assessment_id); //Get by id
            $company = Company::findOrfail($company_id); //Get by id

            $path = $request->path;
            $start_date = $request->start_date;
            
            $impl = MeetingNoteImplementation::updateOrCreate(
                ['assessment_id' => $assessment_id, 'path' => $path, 'start_date' => $start_date],
                ['company_id' => $company_id, 'time' => $time]
            );

            $impl->actions()->updateOrCreate(
                ['implementation_id' => $impl->id, 'aid' => $aid],
                ['complete' => $complete]
            );

            $transform = new ImplementationResource($impl);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function archiveImplementation(Request $request)
    {

        try {

            $rules = [
                'assessment_id' => 'required',
                'company_id' => 'required',
                'actions' => 'required',
                'path' => 'required',
                'start_date' => 'required',
                'time' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'company_id.required' => 'Company ID is required',
                'path.required' => 'Path is required',
                'start_date.required' => 'Start date is required',
                'time.required' => 'Time is required',
                'actions.required' => 'Actions are required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $company_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->company_id);
            $assessment_id = (int)$cypher->decryptID(env('HASHING_SALT'), $request->assessment_id);
            $time = (int)$cypher->decryptID(env('HASHING_SALT'), $request->time);

            $assessment = Assessment::findOrfail($assessment_id); //Get by id
            $company = Company::findOrfail($company_id); //Get by id

            $path = $request->path;
            $start_date = $request->start_date;
            
            $complete = 1;
            $archived = 1;

            $impl = MeetingNoteImplementation::updateOrCreate(
                ['assessment_id' => $assessment_id, 'path' => $path, 'start_date' => $start_date],
                ['company_id' => $company_id, 'archived' => $archived, 'time' => $time]
            );

            foreach ($request->actions as $key => $aid) {
                $impl->actions()->updateOrCreate(
                    ['implementation_id' => $impl->id, 'aid' => $aid],
                    ['complete' => $complete]
                );
            }

            $transform = new ImplementationResource($impl);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
        }
    }




    /**
     * Send an email of the coaching portal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shareResources(Request $request)
    {

        try {

            $rules = [
                'to' => 'required|email',
                'resources' => 'required',
            ];

            $messages = [
                'to.required' => 'Recipient is required',
                'to.email' => 'The recipient you entered is not a valid email',
                'resources.required' => 'Resources are required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $to = $request->to;
            $resources = $request->resources;

            $copy = [];
            $msg = [];
            $temp_resources = [];

            if ($request->cc) {
                $cleancc = str_replace(' ', '', $request->cc);
                $copy = explode(',', trim($cleancc));
            }

            if ($request->resources) {
                $clean = str_replace(' ', '', $request->resources);
                $temp_resources = explode(',', trim($clean));
            }

            $msg[] = 'Here are some resources I belive will be helpful in your business.';

            if (count($temp_resources) > 0) {
                foreach ($temp_resources as $key => $value) {
                    $msg[] = '<a target="_blank" href=' . $value . '>Resource # ' . ($key + 1) . '</a>';
                }
            }

            $details = [
                'user' => $this->user,
                'to' => trim($request->to),
                'messages' => $msg,
                'subject' => 'Resources from ' . $this->user->first_name,
                'copy' => $copy,
                'bcopy' => [],
            ];

            ProcessEmail::dispatch($details);

            return $this->showMessage('Resources shared successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
        }
    }




    /**
     * Send an email of the coaching portal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shareReport(Request $request)
    {
        try {


            // update the metrics
            if ($request->metrics) {
                $this->privateMetricsUpdate($request->metrics);
            }

            // update the notes
            if ($request->notes) {
                $this->privateNotesUpdate($request->notes);
            }

            if ($request->recipients) {
                // Send notes
                try {
                    $to = $request->recipients['to'];
                    $cc = $request->recipients['cc'];
                    $msg = '';
                    if(isset($request->recipients['message'])){
                       $msg = trimSpecial(strip_tags($request->recipients['message']));
                    }
                    $company_id = $request->recipients['company_id'];
                    $note_id = $request->recipients['meeting_note_id'];
                    $share_notes = $request->recipients['share_notes'];

                    $n = MeetingNote::findOrFail($note_id);

                    $note = new NoteResource($n);
                    $company = Company::findOrFail($company_id);

                    $messages = '';

                    if ($msg && strlen($msg) > 0) {
                        $messages = nl2br($msg);
                    }

                    if ($share_notes) {

                        if (strlen($messages) > 0) {
                            $messages = $messages . '<br/><br/>';
                        }

                        $messages = $messages . nl2br($note->notes);
                    }

                    if ($note->others && count($note->others) > 0) {

                        if ($share_notes) {
                            $messages = $messages . '<br/>';
                        }

                        foreach ($note->others as $key => $other) {
                            $messages = $messages . '<br/>';
                            $messages = $messages . '<b>' . $other->settings->label . '</b><br />';
                            $messages = $messages . nl2br($other->note);
                            $messages = $messages . '<br/>';
                        }
                    }

                    if ($note->metrics && count($note->metrics) > 0) {
                        $messages = $messages . '<br/>';
                        foreach ($note->metrics as $key => $metric) {
                            $messages = $messages . '<br/>';
                            $messages = $messages . '<b>' . $metric->settings->label . '</b> ';
                            $messages = $messages . $metric->value;
                            $messages = $messages . '<br/>';
                        }
                    }

                    if ($note->reminder && count($note->reminder) > 0) {
                        $messages = $messages . '<br/><br/> <b>Commitments:</b> <br/>';

                        foreach ($note->reminder as $key => $item) {
                            $messages = $messages . '<br/>';

                            $type = ($item->type == 'client') ? 'Client Commitment' : 'Coach Commitment';
                            $messages = $messages . '<b>Type:</b> ' . $type;
                            $messages = $messages . '<br/>';

                            $status = ($item->status == 0) ? 'Not Done' : ($item->status == 1) ? 'In Progress' : 'Complete';
                            $messages = $messages . '<b>Status:</b> ' . $status;
                            $messages = $messages . '<br/>';

                            $date = Carbon::createFromFormat('Y-m-d H:i:s', $item->reminder_date)->format('D, d M Y g:i A');
                            $messages = $messages . '<b>Due:</b> ' . $date;
                            $messages = $messages . '<br/>';

                            $messages = $messages . '<b>Note:</b> ' . nl2br($item->note);
                            $messages = $messages . '<br/>';
                        }
                    }

                    if ($note->next_meeting_time) {
                        $meet = Carbon::createFromFormat('Y-m-d H:i:s', $note->next_meeting_time)->format('D, d M Y g:i A');
                        $messages = $messages . '<br/><br/> <b>Next meeting time:</b> ' . $meet;
                    }

                    $summary = [];
                    $summary[] = 'Dedicated to your success!';

                    $details = (object)[
                        'subject' => $company->company_name . ' - Notes Report',
                        'to' => $to,
                        'cc' => $cc,
                        'summary' => $summary,
                        'message' => $messages
                    ];

                    $this->sendReportEmail($details);

                    return $this->singleMessage('Report sent successfully', 200);
                } catch (ModelNotFoundException $th) {
                    return $this->errorResponse('Note not found', 400);
                }
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Send a single email to student
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendReportEmail($request)
    {

        $to = $request->to;

        $copy = [];

        if ($request->cc) {
            $cleancc = str_replace(' ', '', $request->cc);
            $copy = explode(',', trim($cleancc));
        }

        $messages = [];
        $messages[] = $request->message;

        // Email without attachment
        $notice = [
            'user' => $this->user,
            'to' => trim($to),
            'messages' => $messages,
            'summary' => isset($request->summary) ? $request->summary : [],
            'subject' => $request->subject,
            'copy' => $copy,
        ];

        ProcessEmail::dispatch($notice, 'coachingportalreport');
    }



    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function commitmentStatus(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:meeting_notes_reminder_tasks,id',
                'status' => 'required',
                'meeting_note_id' => 'required|exists:meeting_notes,id',
            ];

            $messages = [
                'id.required' => 'Commitment ID is required',
                'id.exists' => 'Commitment not found in the database',
                'meeting_note_id.required' => 'Meeting note ID is required',
                'meeting_note_id.exists' => 'Meeting note not found in the database',
                'status.required' => 'Status is required',

            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $status = $request->status;
            $meeting_note_id = $request->meeting_note_id;

            $commitment = MeetingNoteReminder::findOrFail($id);
            $commitment->status = (int)$status;
            $commitment->save();

            $note = MeetingNote::findOrFail($meeting_note_id);

            $transform = new NoteResource($note);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }

    /**
     * Download the commitment task
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function commitmentDownload(Request $request)
    {

        try {

            $task_id = $request->task_id;

            $task = MeetingNoteReminder::findOrFail($task_id);

            if ($task) {

                $time = Carbon::now();
                $key = str_random(6);
                $file_name = strtolower('Commitment ' . $key . ' ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $date = Carbon::createFromFormat('Y-m-d H:i:s', $task->reminder_date)->format('D, d M Y g:i A');
                $due = $date . ' (' . $task->time_zone . ')';
                $status = ($task->status == '0') ? 'Not Started' : (($task->status == '1') ? 'In Progress' : 'Complete');
                $title = 'Commitment Task';

                $pdf = PDF::loadView('pdfs.commitment', compact('task', 'title', 'due', 'status'));
                return $pdf->download($file_name);
            } else {
                return null;
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to download PDF', 400);
        }
    }




    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newUpdateCommitment(Request $request)
    {
        try {

            $rules = [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'reminder_date' => 'required',
                'reminder_time' => 'required',
                'note' => 'required',
            ];

            $messages = [
                'meeting_note_id.required' => 'Meeting note ID is required',
                'meeting_note_id.exists' => 'Meeting note not found in the database',
                'reminder_date.required' => 'Reminder date is required',
                'reminder_time.required' => 'Reminder time is required',
                'note.required' => 'Reminder note is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $meeting_note_id = $request->input('meeting_note_id');

            if ($request->input('id')) {
                $commitment = MeetingNoteReminder::findOrFail($request->input('id'));
                $commitment->note = trim($request->input('note'));
                $commitment->reminder_date = $request->input('reminder_date');
                $commitment->reminder_time = $request->input('reminder_time');
            } else {
                $commitment = new MeetingNoteReminder;
                $commitment->meeting_note_id = $meeting_note_id;
                $commitment->note = trim($request->input('note'));
                $commitment->reminder_date = $request->input('reminder_date');
                $commitment->reminder_time = $request->input('reminder_time');
            }

            if ($request->input('time_zone')) {
                $commitment->time_zone = $request->input('time_zone');
            }

            if ($request->input('type')) {
                $commitment->type = $request->input('type');
            }

            if ($request->input('status')) {
                $commitment->status = $request->input('status');
            }

            if ($request->input('send_reminder')) {
                $commitment->send_reminder = $request->input('send_reminder');
            }

            $commitment->save();

            $note = MeetingNote::findOrFail($meeting_note_id);

            $transform = new NoteResource($note);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newUpdateTask(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'task' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $meeting_note_id = $request->meeting_note_id;
            $note = trim($request->task);

            if (empty($id)) {
                $task = new MeetingNoteTask;
                $task->meeting_note_id = $meeting_note_id;
                $task->note = $note;
            } else {
                $task = MeetingNoteTask::findOrFail($id);
                $task->note = $note;
            }

            $task->save();

            $tasks = MeetingNoteTask::where('meeting_note_id', $meeting_note_id)->orderBy('id')->get();

            $transform = TaskResource::collection($tasks);

            return $this->showMessage($transform, 200);
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Create a new resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newUpdateMetrics(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $meeting_note_id = $request->meeting_note_id;
            $company_id = $request->company_id;

            $custom = [];

            foreach ($request->all() as $key => $value) {
                if (substr($key, 0, 6) === "custom") {
                    $a = explode("_", $key);
                    $custom[] = (object)['id' => $a[1], 'note' => $value];
                }
            }

            if (count($custom) > 0) {

                $today = Carbon::now()->format('Y-m-d H:i:s');

                foreach ($custom as $key => $value) {

                    $n = trim($value->note);

                    if (strlen($n) == 0) {
                        $n = '0';
                    }

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note_id,
                            'setting_id' => $value->id,
                        ],
                        [
                            'value' => $n,
                            'entry_date' => $today
                        ]
                    );
                }
            }

            if ($request->from && ($request->from == 'history')) {
                $note = MeetingNote::findOrFail($meeting_note_id);
                $transform = new NoteResource($note);
                return $this->showMessage($transform, 200);
            } else {

                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();
                $transform = NoteResource::collection($notes);

                return $this->showMessage($transform, 200);
            }
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    public function privateMetricsUpdate($request)
    {
        try {
            $meeting_note_id = $request['meeting_note_id'];
            $company_id = $request['company_id'];

            $custom = [];

            foreach ($request as $key => $value) {
                if (substr($key, 0, 6) === "custom") {
                    $a = explode("_", $key);
                    $custom[] = (object)['id' => $a[1], 'note' => $value];
                }
            }

            if (count($custom) > 0) {

                $today = Carbon::now()->format('Y-m-d H:i:s');

                foreach ($custom as $key => $value) {

                    $n = trim($value->note);

                    if (strlen($n) == 0) {
                        $n = '0';
                    }

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note_id,
                            'setting_id' => $value->id,
                        ],
                        [
                            'value' => $n,
                            'entry_date' => $today
                        ]
                    );
                }
            }
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    public function privateNotesUpdate($request)
    {
        try {

            $company_id = $request['company_id'];
            $meeting_time = $request['meeting_time'];
            $notes = trim($request['notes']);
            $closed = (int)$request['closed'];
            $id = $request['id'];

            $custom = [];

            foreach ($request as $key => $value) {
                if (substr($key, 0, 6) === "custom") {
                    $a = explode("_", $key);
                    $custom[] = (object)['id' => $a[1], 'note' => $value];
                }
            }

            if (empty($id)) {
                $note = MeetingNote::updateOrCreate(
                    [
                        'company_id' => $company_id,
                        'meeting_time' => $meeting_time,
                        'user_id' => $this->user->id,
                        'closed' => $closed,
                    ],
                    [
                        'notes' => $notes,
                    ]
                );
            } else {
                $note = MeetingNote::findOrFail($id);
                $note->notes = $notes;
            }

            if (isset($request['coaching'])) {
                $note->coaching = (int)$request['coaching'];
                if (count($custom) > 0) {
                    foreach ($custom as $key => $value) {

                        $n = trim($value->note);

                        if (strlen($n) > 0) {
                            MeetingNoteOther::updateOrCreate(
                                [
                                    'meeting_note_id' => $note->id,
                                    'setting_id' => $value->id,
                                ],
                                [
                                    'note' => $n,
                                ]
                            );
                        }
                    }
                }
            }

            if (isset($request['closed'])) {
                $note->closed = (int)$request['closed'];
            }

            if (isset($request['next_meeting_time'])) {
                $note->next_meeting_time = $request['next_meeting_time'];
            }

            if (isset($request['time_zone'])) {
                $note->time_zone = $request['time_zone'];
            }

            if (isset($request['meeting_url']) && strlen($request['meeting_url']) > 0) {
                $note->meeting_url = trim($request['meeting_url']);
            }

            $note->save();
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting note not found', 400);
        }
    }



    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMetrics(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $metrics = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->join('companies', 'companies.id', '=', 'meeting_notes.company_id')
                ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
                ->select(
                    'meeting_notes_metrics.*',
                    'companies.company_name',
                    'meeting_notes_settings.type',
                    'meeting_notes_settings.name',
                    'meeting_notes_settings.label'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->orderBy('meeting_notes_metrics.id', 'ASC')->get();

            $transform = MetricsFullResource::collection($metrics);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Send a PDF of coaching poral metrics via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shareMetrics(Request $request)
    {

        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'attachment' => 'required',
                'to' => 'required|email',
                'cc' => 'nullable|string',
            ];

            $messages = [
                'attachment.required' => 'A base64 string attachment is required',
                'to.required' => 'Enter an email recipient',
                'to.email' => 'Please enter a valid recipient email address',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $company = Company::findOrFail($company_id);

            $time = Carbon::now();

            $file_name = strtolower('Coaching Metrics - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
            $file_name = preg_replace('/\s+/', '_', $file_name);

            Storage::disk('local')->put('pdfs/' . $file_name, base64_decode($request->attachment));

            $copy = [];

            if ($request->cc) {
                $cleancc = str_replace(' ', '', $request->cc);
                $copy = explode(',', trim($cleancc));
            }

            $bcopy = [];

            if ($request->bcc) {
                $cleanbcc = str_replace(' ', '', $request->bcc);
                $bcopy = explode(',', trim($cleanbcc));
            }

            $messages = [];

            $messages[] = 'Attached here is latest coaching metrics for ' . trim($company->company_name);
            $messages[] = 'Thank you';

            $notice = [
                'user' => $this->user,
                'to' => trim($request->to),
                'type' => 'local',
                'messages' => $messages,
                'file_name' => $file_name,
                'subject' => 'Coaching Metrics - ' . trim($company->company_name),
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice, 'attachment');

            return $this->showMessage('Your email was sent successfully', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to send email', 400);
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


    private function getContent(Request $request){
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

                        return $task;

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

                    return $task;

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

                return $task;

            }
            
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to download PDF', 400);
        }
    }



    /**
     * Send a PDF of coaching poral task via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shareTask(Request $request)
    {

        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'type' => 'required',
                'task_id' => 'required',
                'to' => 'required|email',
                'cc' => 'nullable|string',
            ];

            $messages = [
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'That company does not exist in the database',
                'type.required' => 'Type is required',
                'task_id.required' => 'Task ID is required',
                'to.required' => 'Enter an email recipient',
                'to.email' => 'Please enter a valid recipient email address',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $type = $request->type;
            $task_id = $request->task_id;

            $company = Company::findOrFail($company_id);
            $task = ($type == 'implementation') ? $this->getContent($request) : MeetingNoteReminder::findOrFail($task_id);
            $file_name = null;

            if ($type == 'implementation') {
                if ($task) {
                    $time = Carbon::now();
                    $key = str_random(6);
                    $file_name = strtolower('Task ' . $key . ' ' . $time->toDateString() . '.pdf');
                    $file_name = preg_replace('/\s+/', '_', $file_name);

                    $title = (isset($task->header))? trim($task->header) : 'Step '. $task->step . ' : ' .$task->description;

                    $pdf = PDF::loadView('pdfs.task', compact('task', 'title'));
                    $pdf->save(storage_path('app/pdfs/') . $file_name);
                }
            } else {
                if ($task) {
                    $time = Carbon::now();
                    $key = str_random(6);
                    $file_name = strtolower('Commitment ' . $key . ' ' . $time->toDateString() . '.pdf');
                    $file_name = preg_replace('/\s+/', '_', $file_name);
                    $date = Carbon::createFromFormat('Y-m-d H:i:s', $task->reminder_date)->format('D, d M Y g:i A');
                    $due = $date . ' (' . $task->time_zone . ')';
                    $status = ($task->status == '0') ? 'Not Started' : (($task->status == '1') ? 'In Progress' : 'Complete');
                    $title = 'Commitment Task';

                    $pdf = PDF::loadView('pdfs.commitment', compact('task', 'title', 'due', 'status'));
                    $pdf->save(storage_path('app/pdfs/') . $file_name);
                }
            }

            $copy = [];

            if ($request->cc) {
                $cleancc = str_replace(' ', '', $request->cc);
                $copy = explode(',', trim($cleancc));
            }

            $bcopy = [];

            if ($request->bcc) {
                $cleanbcc = str_replace(' ', '', $request->bcc);
                $bcopy = explode(',', trim($cleanbcc));
            }

            $messages = [];

            $messages[] = 'Attached here is a task from ' . trim($company->company_name);
            $messages[] = 'Thank you';

            $subject = ($type == 'implementation') ? 'Implementation  Task - ' . trim($company->company_name) : 'Commitment  Task - ' . trim($company->company_name);

            $notice = [
                'user' => $this->user,
                'to' => trim($request->to),
                'type' => 'local',
                'messages' => $messages,
                'file_name' => $file_name,
                'subject' => $subject,
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice, 'attachment');

            return $this->showMessage('Your email was sent successfully', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }



    public function getOldMeetingNotes($company_id)
    {

        $assessments = Company::findOrFail($company_id)->assessments()->get(['id']);

        if ($assessments->count() > 0) {

            $assessment_ids = array_column($assessments->toArray(), 'id');

            $sessions = Session::whereIn('assessment_id', $assessment_ids)->get()->sortByDesc('id');

            return $sessions;
        } else {
            return [];
        }
    }


    public function getOldImpCoachingNotes($company_id)
    {

        $assessments = Company::findOrFail($company_id)->assessments()->get(['id']);

        if ($assessments->count() > 0) {
            $assessment_ids = array_column($assessments->toArray(), 'id');

            $notes = ImpCoaching::whereIn('assessment_id', $assessment_ids)->get()->sortByDesc('id');

            return $notes;
        } else {
            return [];
        }
    }

    public function getOldTask($company_id)
    {

        $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

        if (count($notes) > 0) {
            $ids = $notes->pluck('id')->all();

            $tasks = MeetingNoteTask::whereIn('meeting_note_id', $ids)->orderBy('id', 'DESC')->get();

            return $tasks;
        } else {
            return [];
        }
    }

    /**
     * Send a PDF of coaching poral history via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shareHistory(Request $request)
    {

        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'to' => 'required|email',
                'cc' => 'nullable|string',
                'type' => 'required',
            ];

            $messages = [
                'to.required' => 'Enter an email recipient',
                'type.required' => 'Type is required',
                'to.email' => 'Please enter a valid recipient email address',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $company = Company::findOrFail($company_id);

            $time = Carbon::now();

            $messages = [];
            $title = '';
            $file_name = '';

            if ($request->type == 'history') {
                $messages[] = 'Attached here is the current notes history for ' . trim($company->company_name);
                $file_name = strtolower('Current Notes History - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $title = 'Current Notes History for - ' . trim($company->company_name);
                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

                $pdf = PDF::loadView('pdfs.history', compact('notes', 'title'));
                $pdf->save(storage_path('app/pdfs/') . $file_name);
            } else if ($request->type == 'meeting') {
                $messages[] = 'Attached here are the old meeting notes for ' . trim($company->company_name);
                $title = 'Old meeting notes for - ' . trim($company->company_name);
                $file_name = strtolower('Old meeting notes - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $meeting = $this->getOldMeetingNotes($company_id);

                $pdf = PDF::loadView('pdfs.meeting', compact('meeting', 'title'));
                $pdf->save(storage_path('app/pdfs/') . $file_name);
            } else if ($request->type == 'implementation') {
                $messages[] = 'Attached here are the old implementation notes for ' . trim($company->company_name);
                $title = 'Old implementation notes for - ' . trim($company->company_name);
                $file_name = strtolower('Old implementation notes - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $implementation = $this->getOldImpCoachingNotes($company_id);

                $pdf = PDF::loadView('pdfs.implementation', compact('implementation', 'title'));
                $pdf->save(storage_path('app/pdfs/') . $file_name);
            } else if ($request->type == 'tasks') {
                $messages[] = 'Attached here are the old tasks for ' . trim($company->company_name);
                $title = 'Old tasks for - ' . trim($company->company_name);
                $file_name = strtolower('Old tasks - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $tasks = $this->getOldTask($company_id);

                $pdf = PDF::loadView('pdfs.tasks', compact('tasks', 'title'));
                $pdf->save(storage_path('app/pdfs/') . $file_name);
            }

            $messages[] = 'Thank you';

            $summary = [];
            $summary[] = 'Dedicated to your success!';

            $copy = [];

            if ($request->cc) {
                $cleancc = str_replace(' ', '', $request->cc);
                $copy = explode(',', trim($cleancc));
            }

            $bcopy = [];

            if ($request->bcc) {
                $cleanbcc = str_replace(' ', '', $request->bcc);
                $bcopy = explode(',', trim($cleanbcc));
            }

            $notice = [
                'user' => $this->user,
                'to' => trim($request->to),
                'type' => 'local',
                'messages' => $messages,
                'file_name' => $file_name,
                'subject' => $title,
                'summary' => $summary,
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice, 'attachment');

            return $this->showMessage('Your email was sent successfully', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }


    /**
     * Send a PDF of coaching poral history via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function downloadHistory(Request $request)
    {

        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'type' => 'required',
            ];

            $messages = [
                'type.required' => 'Type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $company = Company::findOrFail($company_id);

            $time = Carbon::now();

            $title = '';
            $file_name = '';

            if ($request->type == 'history') {
                $file_name = strtolower('Current Notes History - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $title = 'Current Notes History for - ' . trim($company->company_name);
                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

                $pdf = PDF::loadView('pdfs.history', compact('notes', 'title'));
                return $pdf->download($file_name);
            } else if ($request->type == 'meeting') {
                $title = 'Old meeting notes for - ' . trim($company->company_name);
                $file_name = strtolower('Old meeting notes - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $meeting = $this->getOldMeetingNotes($company_id);

                $pdf = PDF::loadView('pdfs.meeting', compact('meeting', 'title'));
                return $pdf->download($file_name);
            } else if ($request->type == 'implementation') {
                $title = 'Old implementation notes for - ' . trim($company->company_name);
                $file_name = strtolower('Old implementation notes - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $implementation = $this->getOldImpCoachingNotes($company_id);

                $pdf = PDF::loadView('pdfs.implementation', compact('implementation', 'title'));
                return $pdf->download($file_name);
            } else if ($request->type == 'tasks') {
                $title = 'Old task for - ' . trim($company->company_name);
                $file_name = strtolower('Old tasks - ' . $company->company_name . ' - ' . $time->toDateString() . '.pdf');
                $file_name = preg_replace('/\s+/', '_', $file_name);
                $tasks = $this->getOldTask($company_id);

                $pdf = PDF::loadView('pdfs.tasks', compact('tasks', 'title'));
                return $pdf->download($file_name);
            }
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to download PDF', 400);
        }
    }





    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getNotes(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();

            $transform = NoteResource::collection($notes);

            return $this->showMessage($transform, 200);
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchNotes(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $last_id = $request->last;

            if (empty($last_id)) {
                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(20)->get();
            } else {
                $notes = MeetingNote::where('id', '<', $last_id)
                    ->where(function ($q) use ($company_id) {
                        $q->where('company_id', $company_id);
                    })->orderBy('id', 'DESC')->limit(20)->get();
            }

            $transform = NoteResource::collection($notes);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSettings(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $settings = MeetingNoteSetting::where('company_id', $company_id)->get();

            // If no settings found
            // Create the default
            if (count($settings) === 0) {
                createMeetingNotesSettings($company_id);
                $settings = MeetingNoteSetting::where('company_id', $company_id)->get();
            }

            $transform = SettingResource::collection($settings);

            return $this->showMessage($transform, 200);
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
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

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'meeting_time' => 'required',
                'notes' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $meeting_time = $request->meeting_time;
            $notes = trim($request->notes);
            $closed = (int)$request->closed;
            $time_zone = $request->time_zone || 'UTC';
            $id = $request->id;

            $custom = [];

            foreach ($request->all() as $key => $value) {
                if (substr($key, 0, 6) === "custom") {
                    $a = explode("_", $key);
                    $custom[] = (object)['id' => $a[1], 'note' => $value];
                }
            }

            if (empty($id)) {
                $note = new MeetingNote;
                $note->notes = $notes;
                $note->company_id = $company_id;
                $note->user_id = $this->user->id;
                $note->meeting_time = $meeting_time;
                $note->time_zone = $time_zone;
                $note->closed = $closed;
            } else {
                $note = MeetingNote::findOrFail($id);
                $note->notes = $notes;
                $note->time_zone = $time_zone;
            }

            if ($request->coaching) {
                $note->coaching = (int)$request->coaching;
                if (count($custom) > 0) {
                    foreach ($custom as $key => $value) {

                        $n = trim($value->note);

                        if (strlen($n) > 0) {
                            MeetingNoteOther::updateOrCreate(
                                [
                                    'meeting_note_id' => $note->id,
                                    'setting_id' => $value->id,
                                ],
                                [
                                    'note' => $n,
                                ]
                            );
                        }
                    }
                }
            }

            if ($request->closed) {
                $note->closed = (int)$request->closed;
            }

            if ($request->next_meeting_time) {
                $note->next_meeting_time = $request->next_meeting_time;
            }

            if ($request->time_zone) {
                $note->time_zone = $request->time_zone;
            }

            if ($request->meeting_url && strlen($request->meeting_url) > 0) {
                $note->meeting_url = trim($request->meeting_url);
            }

            $note->save();

            if ($request->global && (int)$request->global == 1) {

                $user = User::findOrFail($note->user_id);

                if ($request->meeting_url && strlen($request->meeting_url) > 0) {
                    $user->meeting_url = trim($request->meeting_url);
                }

                if ($user->isDirty()) {
                    $user->save();
                }
            }

            if ($request->from && ($request->from == 'history')) {

                $transform = new NoteResource($note);
                return $this->showMessage($transform, 200);
            } else {

                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();
                $transform = NoteResource::collection($notes);

                return $this->showMessage($transform, 200);
            }
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting note not found', 400);
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateNextMeetingTime(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:meeting_notes,id',
                'company_id' => 'required|exists:companies,id',
                'next_meeting_time' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $next_meeting_time = $request->next_meeting_time;
            $id = $request->id;

            $note = MeetingNote::findOrFail($id);

            $note->next_meeting_time = $request->next_meeting_time;

            if ($request->time_zone) {
                $note->time_zone = $request->time_zone;
            }

            if ($request->meeting_url && strlen($request->meeting_url) > 0) {
                $note->meeting_url = trim($request->meeting_url);
            }

            $note->save();

            if ($request->global && (int)$request->global == 1) {

                $user = User::findOrFail($note->user_id);

                if ($request->meeting_url && strlen($request->meeting_url) > 0) {
                    $user->meeting_url = trim($request->meeting_url);
                }

                if ($user->isDirty()) {
                    $user->save();
                }
            }

            if ($request->from && ($request->from == 'history')) {

                $transform = new NoteResource($note);
                return $this->showMessage($transform, 200);
            } else {

                $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();
                $transform = NoteResource::collection($notes);

                return $this->showMessage($transform, 200);
            }
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting note not found', 400);
        }
    }


    // update/ammend previous meeting date
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePreviousMeetingDate(Request $request)
    {
        try {
            $meeting_note = MeetingNote::findOrFail($request->id);
            $meeting_note->meeting_time = $request->meeting_date;
            $meeting_note->save();

            $transform = new NoteResource($meeting_note);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteNote(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
                'id' => 'required|exists:meeting_notes,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $company_id = $request->company_id;

            $note = MeetingNote::findOrFail($id);

            if ($note->others) {
                $note->others()->delete(); // Delete all other notes attached
            }

            if ($note->metrics) {
                $note->metrics()->delete(); // Delete all metrics attached
            }

            if ($note->tasks) {
                $note->tasks()->delete(); // Delete all tasks attached
            }

            if ($note->reminder) {
                $note->reminder()->delete(); // Delete all reminders attached
            }

            $note->delete(); //Delete the note

            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->limit(2)->get();

            $transform = NoteResource::collection($notes);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteCommitment(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'id' => 'required|exists:meeting_notes_reminder_tasks,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;

            $reminder = MeetingNoteReminder::findOrFail($id);

            if ($reminder->files) {
                // Delete all the files on S3 bucket
                $array = [];
                foreach ($reminder->files as $key => $file) {
                    $array[] = array('Key' => 'reminderfiles/' . $file->key);
                }

                $s3 = \AWS::createClient('s3');

                $s3->deleteObjects([
                    'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                    'Delete' => [
                        'Objects' => $array
                    ]
                ]);

                $reminder->files()->delete(); // Delete all other files attached in the DB
            }

            $reminder->delete(); //Delete the reminder

            return $this->singleMessage('Reminder task deleted successfully', 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyTask(Request $request)
    {

        try {

            $validator = Validator::make($request->all(), [
                'meeting_note_id' => 'required|exists:meeting_notes,id',
                'id' => 'required|exists:meeting_notes_coach_tasks,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $meeting_note_id = $request->meeting_note_id;

            $task = MeetingNoteTask::findOrFail($id);
            $task->delete(); //Delete the task

            $tasks = MeetingNoteTask::where('meeting_note_id', $meeting_note_id)->orderBy('id')->get();

            $transform = TaskResource::collection($tasks);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That task does not exist', 404);
        }
    }

    /**
     * Client meeting notes methods
     * */
    /**
     * Get a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getClientMeetingNotes(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;

            $notes = ClientMeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();
            $transform = ClientMeetingNoteResource::collection($notes);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Settings not found', 400);
        }
    }

    /**
     * Store the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function storeClientMeetingNote(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'notes' => 'required',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $note_data = $request->notes;

            ClientMeetingNote::create([
                'user_id' => $user_id,
                'notes' => $note_data
            ]);

            $notes = ClientMeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();

            $transform = ClientMeetingNoteResource::collection($notes);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('The note was not created', 404);
        }
    }

    /**
     * Update the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editClientMeetingNote(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:client_meeting_notes,id',
                'notes' => 'required',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $user_id = $request->user_id;
            $note_data = $request->notes;

            $note = ClientMeetingNote::findOrFail($id);
            $note->notes = $note_data;
            $note->save(); //Update the note


            $notes = ClientMeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();
            $transform = ClientMeetingNoteResource::collection($notes);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('The note was not updated', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteClientMeetingNote(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:client_meeting_notes,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $user_id = $request->user_id;

            $note = ClientMeetingNote::findOrFail($id);
            $note->delete(); //Delete the note

            $notes = ClientMeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();

            $transform = ClientMeetingNoteResource::collection($notes);
            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That note does not exist', 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getClientMeetingSchedule(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $meetings = DB::table('meeting_notes')
                ->join('users', 'users.id', '=', 'meeting_notes.user_id')
                ->select(
                    'meeting_notes.*',
                    'users.first_name',
                    'users.last_name',
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->where(function ($b) {
                    $b->where('meeting_notes.coaching', '1');
                })->where(function ($c) {
                    $c->where('meeting_notes.closed', '1');
                })->orderBy('meeting_notes.id', 'DESC')->get();

            $flash = $this->getFlashCoachingAppointments($company_id);
            // $flash_appointments = FlashAppointmentResource::collection($flash);
            $coaching_appointments = ClientMeetingSchedule::collection($meetings);

            return $this->showMessage(['coaching' => $coaching_appointments, 'flashcoaching' => $flash], 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting note not found', 400);
        }
    }

    private function getFlashCoachingAppointments($company_id){
        
        $today = Carbon::now()->format('Y-m-d H:i:s');

        // Get up-coming appointments
        $appointments = DB::table('flash_coaching_appointments')
        ->join('users', 'users.id', '=', 'flash_coaching_appointments.coach_id')
        ->select(
            'flash_coaching_appointments.id', 
            'flash_coaching_appointments.student_id', 
            'flash_coaching_appointments.company_id', 
            'users.id as coach_id', 
            'users.first_name as first_name', 
            'users.last_name as last_name', 
            'users.company as company_name', 
            'users.email as coach_email',
            'flash_coaching_appointments.meeting_time as next_meeting_time',
            'flash_coaching_appointments.meeting_url',
            'flash_coaching_appointments.time_zone',
            'flash_coaching_appointments.type',
            )
        ->where('flash_coaching_appointments.company_id', $company_id)
        ->where('flash_coaching_appointments.meeting_time' , '>=' , $today)->orderBy('flash_coaching_appointments.meeting_time', 'ASC')->get();

        return $appointments;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changeImplementationTaskStatus(Request $request)
    {
        try {
            $rules = [
                'id' => 'required|exists:meeting_notes_implementation_actions,id',
            ];

            $messages = [
                'id.required' => 'Task action id is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $id = $request->id;
            $status = $request->status;

            $task_action = MeetingNoteImplementationAction::findOrfail($id);
            $task_action->complete = $status;
            $task_action->save();

            return $this->successResponse('Meeting note implementation action has been updated successfully', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting note implementation action not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMetricsClientPortal(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'company_id' => 'required|exists:companies,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;

            $meeting_ids = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
                ->select(
                    'meeting_notes_metrics.meeting_note_id'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->orderBy('meeting_notes_metrics.id', 'ASC')->get();

            $foo = array();
            foreach ($meeting_ids as $key => $value) {
                $foo[$key] = $value->meeting_note_id;
            }
            $foo = array_unique($foo);

            $metrics = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
                ->select(
                    'meeting_notes_metrics.*',
                    'meeting_notes_settings.type',
                    'meeting_notes_settings.name',
                    'meeting_notes_settings.label'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->orderBy('meeting_notes_metrics.id', 'ASC')->get();
            $data = [];
            foreach ($foo as $k => $v) {
                $new_data = [
                    'id' => $k + 1,
                    'meeting_note_id' => null,
                    'revenue' => null,
                    'profits' => null,
                    'leads' => null,
                    'conversions' => null,
                    'date' => null
                ];
                foreach ($metrics as $key => $value) {
                    if ($v === $value->meeting_note_id && $value->name == 'revenue') {
                        $new_data['revenue'] = (float)$value->value;
                        $new_data['date'] = $value->entry_date;
                        $new_data['meeting_note_id'] = $value->meeting_note_id;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'profits') {
                        $new_data['profits'] = (float)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'leads') {
                        $new_data['leads'] = (int)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'conversions') {
                        $new_data['conversions'] = (int)$value->value;
                    }
                }
                array_push($data, $new_data);
            }

            $data = json_decode(json_encode($data), FALSE);

            $transform = MetricsClientResource::collection($data);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }

    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function saveClientMetrics(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'company_id' => 'required|exists:companies,id',
                'revenue' => 'required',
                'profits' => 'required',
                'leads' => 'required',
                'conversions' => 'required',
                'entry_date' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
                'revenue.required' => 'Revenue input is required',
                'profits.required' => 'Profit input is required',
                'leads.required' => 'Leads input is required',
                'conversions.required' => 'Conversion input is required',
                'entry_date' => 'Entry date is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $company_user = CompanyUser::where('company_id', '=', $company_id)->first();
            $date = strtotime($request->entry_date);
            $entry_date = (date('Y-m-d H:i:s', $date));

            $meeting_note = MeetingNote::firstOrCreate([
                'user_id' => $company_user->user_id,
                'company_id' => $company_id,
                'closed' => 1,
                'meeting_time' => $entry_date,
                'next_meeting_time' => $entry_date,
            ], ['notes' => 'Client entered custom metrics on this date']);

            $meeting_note_settings = MeetingNoteSetting::where('company_id', '=', $company_id)
                ->whereIn('name', ['revenue', 'leads', 'conversions', 'profits'])
                ->pluck('id', 'name');


            foreach ($meeting_note_settings as $key => $value) {

                if ($key === 'revenue') {

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->revenue,
                        ]
                    );
                } elseif ($key === 'profits') {

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->profits,
                        ]
                    );
                } elseif ($key === 'leads') {

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->leads,
                        ]
                    );
                } else {

                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->conversions,
                        ]
                    );
                }
            }

            $meeting_ids = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->select(
                    'meeting_notes_metrics.meeting_note_id'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->groupBy('meeting_notes_metrics.meeting_note_id')->orderBy('meeting_notes_metrics.id', 'ASC')->get();

            $foo = $meeting_ids->pluck('meeting_note_id')->all();


            $metrics = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
                ->select(
                    'meeting_notes_metrics.*',
                    'meeting_notes_settings.type',
                    'meeting_notes_settings.name',
                    'meeting_notes_settings.label'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->orderBy('meeting_notes_metrics.id', 'ASC')->get();

            $data = [];
            foreach ($foo as $k => $v) {

                $new_data = [
                    'id' => $k + 1,
                    'meeting_note_id' => null,
                    'revenue' => 0,
                    'profits' => 0,
                    'leads' => 0,
                    'conversions' => 0,
                    'date' => null
                ];
                foreach ($metrics as $key => $value) {
                    if ($v === $value->meeting_note_id && $value->name == 'revenue') {
                        $new_data['revenue'] = (float)$value->value;
                        $new_data['date'] = $value->entry_date;
                        $new_data['meeting_note_id'] = $value->meeting_note_id;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'profits') {
                        $new_data['profits'] = (float)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'leads') {
                        $new_data['leads'] = (int)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'conversions') {
                        $new_data['conversions'] = (int)$value->value;
                    }
                }
                array_push($data, $new_data);
            }

            $data = json_decode(json_encode($data), FALSE);

            $transform = MetricsClientResource::collection($data);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }

    /**
     * Update the client metrics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function updateClientMetrics(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:meeting_notes,id',
                'company_id' => 'required|exists:companies,id',
                'revenue' => 'required',
                'profits' => 'required',
                'leads' => 'required',
                'conversions' => 'required',
                'entry_date' => 'required',
            ];

            $messages = [
                'id.required' => 'ID is required',
                'id.exists' => 'ID Not Found',
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
                'revenue.required' => 'Revenue input is required',
                'profits.required' => 'Profit input is required',
                'leads.required' => 'Leads input is required',
                'conversions.required' => 'Conversion input is required',
                'entry_date' => 'Entry date is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $company_id = $request->company_id;
            $meeting_note_id = $request->id;
            $date = strtotime($request->entry_date);
            $entry_date = (date('Y-m-d H:i:s', $date));

            $meeting_note = MeetingNote::find($meeting_note_id);

            $meeting_note_settings = MeetingNoteSetting::where('company_id', '=', $company_id)
                ->whereIn('name', ['revenue', 'leads', 'conversions', 'profits'])
                ->pluck('id', 'name');

            foreach ($meeting_note_settings as $key => $value) {
                if ($key === 'revenue') {
                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->revenue,
                        ]
                    );
                } elseif ($key === 'profits') {
                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->profits,
                        ]
                    );
                } elseif ($key === 'leads') {
                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->leads,
                        ]
                    );
                } else {
                    MeetingNoteMetric::updateOrCreate(
                        [
                            'meeting_note_id' => $meeting_note->id,
                            'setting_id' => $value,
                            'entry_date' => $entry_date,
                        ],
                        [
                            'value' => $request->conversions,
                        ]
                    );
                }
            }

            $meeting_ids = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->select(
                    'meeting_notes_metrics.meeting_note_id'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->groupBy('meeting_notes_metrics.meeting_note_id')->orderBy('meeting_notes_metrics.id', 'ASC')->get();

            $foo = $meeting_ids->pluck('meeting_note_id')->all();


            $metrics = DB::table('meeting_notes_metrics')
                ->join('meeting_notes', 'meeting_notes.id', '=', 'meeting_notes_metrics.meeting_note_id')
                ->join('meeting_notes_settings', 'meeting_notes_settings.id', '=', 'meeting_notes_metrics.setting_id')
                ->select(
                    'meeting_notes_metrics.*',
                    'meeting_notes_settings.type',
                    'meeting_notes_settings.name',
                    'meeting_notes_settings.label'
                )
                ->where(function ($a) use ($company_id) {
                    $a->where('meeting_notes.company_id', $company_id);
                })->orderBy('meeting_notes_metrics.id', 'ASC')->get();
            $data = [];
            foreach ($foo as $k => $v) {
                $new_data = [
                    'id' => $k + 1,
                    'meeting_note_id' => null,
                    'revenue' => null,
                    'profits' => null,
                    'leads' => null,
                    'conversions' => null,
                    'date' => null
                ];
                foreach ($metrics as $key => $value) {
                    if ($v === $value->meeting_note_id && $value->name == 'revenue') {
                        $new_data['revenue'] = (float)$value->value;
                        $new_data['date'] = $value->entry_date;
                        $new_data['meeting_note_id'] = $value->meeting_note_id;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'profits') {
                        $new_data['profits'] = (float)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'leads') {
                        $new_data['leads'] = (int)$value->value;
                    }
                    if ($v === $value->meeting_note_id && $value->name == 'conversions') {
                        $new_data['conversions'] = (int)$value->value;
                    }
                }
                array_push($data, $new_data);
            }

            $data = json_decode(json_encode($data), FALSE);

            $transform = MetricsClientResource::collection($data);

            return $this->showMessage($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Note not found', 400);
        }
    }


    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sendClientMetrics(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'file' => 'required|mimetypes:application/pdf|max:10000',
                'file' => 'required',
            ];

            $messages = [
                'user_id.required' => 'The user ID is required',
                'user_id.exists' => 'The user does not exists Found',
                'file.required' => 'File is required'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            
            if ($request->hasFile('file')) {

                $file = $request->file('file');
                $s3 = \AWS::createClient('s3');
                $user_id = $request->input('id');
                $key = $user_id . '.' . $file->getClientOriginalExtension();
                $uploadfile = $s3->putObject([
                    'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                    'Key'        => 'profile_pictures/' . $key,
                    'ACL'          => 'public-read',
                    'ContentType' => $file->getMimeType(),
                    'SourceFile' => $file,
                ]);

                $url = $uploadfile->get('ObjectURL');
            }

            $transform = ["message" => "file uploaded", "data" => $url];

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That user was not found', 400);
        }
    }

    /**
     * add Client implementations and implementations actions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addClientImplementation(Request $request)
    {
        try {

            $data = $request->all();


            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'company_id' => 'required|exists:companies,id',
                'path' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'company_id.required' => 'Company ID is required',
                'company_id.exists' => 'Company Not Found',
                'path.required' => 'Path is required',
                'start_date.required' => 'Start date is required',
                'end_date.required' => 'End date is required',
            ];

            $validator = Validator::make([
                'assessment_id' => $data[0]['assessment_id'],
                'path' => $data[0]['path'],
                'company_id' => $data[0]['company_id'],
                'start_date' => $data[0]['start_date'],
                'end_date' => $data[0]['end_date']
            ], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment_id = (int)$data[0]['assessment_id'];
            $company_id = (int)$data[0]['company_id'];
            $path = $data[0]['path'];
            $start_date = $data[0]['start_date'];
            $end_date = $data[0]['end_date'];


            foreach ($data[1] as $key => $value) {
                $rules = [
                    'aid' => 'required',
                    'time' => 'required'
                ];

                $messages = [
                    'aid.required' => 'Action ID is required',
                    'time.required' => 'Time is required'
                ];

                $validator = Validator::make([
                    'aid' => $value['aid'],
                    'time' => $value['time']
                ], $rules, $messages);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }

                $time = (int)$value['time'];
                $aid = $value['aid'];
                $end_date = strtotime($end_date);
                $deadline = (date('Y-m-d h:i:s', $end_date));

                $impl = MeetingNoteImplementation::firstOrCreate(
                    [
                        'assessment_id' => $assessment_id,
                        'path' => $path,
                        'start_date' => $start_date,
                        'company_id' => $company_id,
                        'time' => $time
                    ]
                );

                $impl = $impl->refresh();

                MeetingNoteImplementationAction::firstOrCreate(
                    [
                        'implementation_id' => $impl->id,
                        'aid' => $aid
                    ]
                );
            }


            $impl = MeetingNoteImplementation::where('assessment_id', $assessment_id)
                ->where('company_id', $company_id)
                ->where('path', $path)
                ->where('start_date', $start_date)
                ->first();

            $transform = ($impl) ? new ImplementationResource($impl) : null;

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
        }
    }

    /**
     * toggleActionSteps.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toggleActionSteps(Request $request)
    {
        try {

            $rules = [
                'company_id' => 'required|exists:companies,id',
                'assessment_id' => 'required|exists:assessments,id',
                'path' => 'required',
                'status' => 'required',
            ];

            $messages = [
                'path.required' => 'The path is required',
                'status.required' => 'The status is required',
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

            // Enable action steps for the client

            // Get user
            $user = User::where('company_id', $company_id)->first();

            if ($user) {
                $array = [
                    'group_coaching' => 1,
                    'training_software' => 0,
                    'training_100k' => 0, 
                    'prep_roleplay' => 0,
                    'training_jumpstart' => 0, 
                    'training_lead_gen' => 0, 
                    'flash_coaching' => 0
                ];
                // The update the coaching_action_steps status
                $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);
            }

            CoachingActionStep::updateOrCreate(
                [
                    'company_id' => $company_id,
                    'assessment_id' => $assessment_id,
                    'path' => $request->path,
                ],
                ['status' => (int)$request->status]
            );

            $action_step = CoachingActionStep::where('company_id', $company_id)->get();

            $step = CoachingActionStepsResource::collection($action_step);

            return $this->successResponse($step, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Flash coaching not found', 400);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function actionSteps(Request $request)
    {
        try {

            $rules = [
                'company' => 'required|exists:companies,id',
                'assessment' => 'required|exists:assessments,id',
                'path' => 'required',
            ];

            $messages = [
                'path.required' => 'The path is required',
                'company_id.required' => 'The company id is required',
                'company_id.exists' => 'That company does not exist',
                'assessment_id.required' => 'The assessment id is required',
                'assessment_id.exists' => 'That assessment doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            $action_step = CoachingActionStep::where('company_id', $request->company)->where('assessment_id', $request->assessment)->where('path', $request->path)->get();

            if($action_step->isEmpty()){
                return $this->successResponse(['access'=> !$action_step->isEmpty() ], 200);
            }
           
            return $this->successResponse(['access'=> !!$action_step->first()->status ], 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Access not found', 400);
        }
    }
}
