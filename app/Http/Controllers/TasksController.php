<?php

namespace App\Http\Controllers;

use Mail;
use Validator;

use PDF;
use Carbon\Carbon;
use App\Jobs\ProcessEmail;
use App\Models\ImpStep;
use App\Models\MeetingNote;
use App\Models\MeetingNoteReminder;
use App\Models\MeetingNoteImplementationAction;
use App\Models\TeamMember;
use App\Models\TeamMemberTaskImplementation;
use App\Models\TeamMemberTaskCommitment;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\MeetingNoteReminder as ReminderResource;
use App\Http\Resources\TeamMember as TeamMemberResource;
use App\Http\Resources\TeamMemberTaskImplementation as TeamMemberTaskImpResource;
use App\Http\Resources\TeamMemberTaskCommitment as TeamMemberTaskComResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class TasksController extends Controller
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

    /** 
     * Get all the coach tasks including the ones that belong to clients
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function coachTasks()
    {
        try {

            $user_id = $this->user->id;

            $notes = MeetingNote::where('user_id', $user_id)->orderBy('id', 'DESC')->get();

            if(count($notes) > 0){
                $ids = $notes->pluck('id')->all();

                $tasks = MeetingNoteReminder::whereIn('meeting_note_id', $ids)->orderBy('reminder_date')->get();

                $transform = ReminderResource::collection($tasks);

                return $this->successResponse($transform,200);
            }else{
                return $this->successResponse([],200);
            }
            
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting notes not found', 400);
        }
    }

    public function getClientCommitments(Request $request)
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

            if(count($notes) > 0){
                $ids = $notes->pluck('id')->all();

                $tasks = MeetingNoteReminder::whereIn('meeting_note_id', $ids)->where('type', 'client')->orderBy('id', 'DESC')->get();

                $transform = ReminderResource::collection($tasks);

                return $this->successResponse($transform,200);
            }else{
                return $this->successResponse([],200);
            }

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Meeting notes not found', 400);
        }
    }

    /**
     * Update the status of a task
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:meeting_notes_reminder_tasks,id',
                'status' => 'required',
            ];

            $messages = [
                'id.required' => 'Task ID is required',
                'id.exists' => 'Task not found in the database',
                'status.required' => 'Status is required',
                
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $status = $request->status;
            $meeting_note_id = $request->meeting_note_id;

            $task = MeetingNoteReminder::findOrFail($id);
            $task->status = (int)$status;
            $task->save();
            $task = $task->refresh();

            $transform = new ReminderResource($task);

            return $this->showMessage($transform, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Task not found', 400);
        }
    }


    /**
     * Update a task
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateTask(Request $request)
    {

        try{

            $rules = [
                'id' => 'required|exists:meeting_notes_reminder_tasks,id',
                'reminder_date' => 'required',
                'reminder_time' => 'required',
                'note' => 'required',
                'send_reminder' => 'required',
            ];

            $messages = [
                'id.required' => 'Task ID is required',
                'id.exists' => 'Task not found in the database',
                'note.required' => 'Note is required',
                'reminder_date.required' => 'Reminder date is required',
                'reminder_time.required' => 'Reminder time is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            
            $task = MeetingNoteReminder::findOrFail($id);

            $task->note = trim($request->note);
            $task->reminder_date = $request->reminder_date;
            $task->reminder_time = $request->reminder_time;
            $task->send_reminder = (int)$request->send_reminder;

            if ($task->isDirty()) {
                $task->save();
            }

            $task = $task->refresh();

            $transform = new ReminderResource($task);

            return $this->showMessage($transform, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Task not found', 400);
        }
    }

    public function updateClientCommitments(Request $request) {
        try {

            $rules = [
                'id' => 'required|exists:meeting_notes_reminder_tasks,id',
                'task' => 'required',
            ];

            $messages = [
                'id.required' => 'Task ID is required',
                'id.exists' => 'Task not found in the database',
                'task.required' => 'Note is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $text = preg_replace("/^<p.*?>/", "", $request->task);
            $task_note = preg_replace("|</p>$|", "", $text);

            $task = MeetingNoteReminder::findOrFail($id);
            $task->note = $task_note;
            $task->save();
            $task = $task->refresh();


            $company_id = $request->company_id;

            $notes = MeetingNote::where('company_id', $company_id)->orderBy('id', 'DESC')->get();

            if(count($notes) > 0){
                $ids = $notes->pluck('id')->all();

                $tasks = MeetingNoteReminder::whereIn('meeting_note_id', $ids)->orderBy('id', 'DESC')->get();

                $transform = ReminderResource::collection($tasks);

                return $this->successResponse($transform,200);
            }else{
                return $this->successResponse([],200);
            }

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation not found', 400);
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



    public function getTeamMembers(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $team_members = TeamMember::where('user_id', '=', trim($request->user_id))->get();

            $transform = TeamMemberResource::collection($team_members);

            return $this->successResponse($transform,200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Team members not found', 400);
        }
        
    }

    public function addTeamMember(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'first_name' => 'required', 
                'last_name' => 'required', 
                'email' => 'required|email',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
                'first_name.required' => 'First name is required', 
                'last_name.required' => 'Last name is required', 
                'email.required' => 'Email is required',
                'email.email' => 'Email is not a valid email',
                'email.exists' => 'Email already exists',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = trim($request->user_id);
            $email = trim($request->email);

            $team_members = TeamMember::whereUserId($user_id)
                                        ->whereEmail($email)->first();

            if(!is_null($team_members)) {
                return $this->errorResponse("Email ".$team_members->email." already exists", 400);
            }
            $team_member = new TeamMember;
            $team_member->user_id = $user_id;
            $team_member->first_name = $request->first_name;
            $team_member->last_name = $request->last_name;
            $team_member->email = $request->email;
            $team_member->save();

            $team_members = TeamMember::where('user_id', '=', $user_id)->get();

            $transform = TeamMemberResource::collection($team_members);

            return $this->successResponse($transform,200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Team members not found', 400);
        }
    }

    public function assignTeamMember(Request $request)
    {
        try {
            
            $rules = [
                'user_id' => 'required|exists:users,id',
                'team_member_id' => 'required|exists:team_members,id',
                'task_type' => 'required',
                'task_id' => 'required',
            ];

            $msgs = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
                'team_member_id.required' => 'Team member ID is required',
                'team_member_id.exists' => 'Team member not found in the database',
                'task_type.required' => 'Task type is required',
                'task_id.required' => 'Task ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $msgs);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $team_member_id = $request->team_member_id;
            $user_id = $request->user_id;
            $task_type = $request->task_type;
            $task_id = $request->task_id;
            
            $task = ($task_type == 'implementation')? ImpStep::findOrFail($task_id) : MeetingNoteReminder::findOrFail($task_id);

            $file_name = null;

            if($task_type == 'implementation'){
                 if($task){
                    $time = Carbon::now();
                    $key = str_random(6);
                    $file_name = strtolower('Task ' . $key . ' ' . $time->toDateString() . '.pdf');
                    $file_name = preg_replace('/\s+/', '_', $file_name);
                    
                    $title = trim($task->header);
                    
                    $pdf = PDF::loadView('pdfs.task', compact('task', 'title'));
                    $pdf->save(storage_path('app/pdfs/').$file_name);
                }
            }else{
                if($task){
                    $time = Carbon::now();
                    $key = str_random(6);
                    $file_name = strtolower('Commitment ' . $key . ' ' . $time->toDateString() . '.pdf');
                    $file_name = preg_replace('/\s+/', '_', $file_name);
                    $date = Carbon::createFromFormat('Y-m-d H:i:s', $task->reminder_date)->format('D, d M Y g:i A');
                    $due = $date . ' (' . $task->time_zone.')';
                    $status = ($task->status == '0')? 'Not Started' : (($task->status == '1')? 'In Progress' : 'Complete');
                    $title = 'Commitment Task';
                    
                    $pdf = PDF::loadView('pdfs.commitment', compact('task', 'title', 'due', 'status'));
                    $pdf->save(storage_path('app/pdfs/').$file_name);
                }
            }
            
            if ($task_type == "implementation"){
                $impla = MeetingNoteImplementationAction::findOrFail($request->id);

                $start = $impla->implementation->start_date;
                $period = $impla->implementation->time;

                $dat = Carbon::createFromDate($start);
                $dat->setTimezone('UTC');
                $dat->addWeeks((int)$period);
                $enddate = $dat->format('d M Y');
            }else{
                $mnr = MeetingNoteReminder::findOrFail($task_id);
                $start = $mnr->reminder_date;
                $dat = Carbon::createFromDate($start);
                $dat->setTimezone('UTC');
                $enddate = $dat->format('d M Y');
            }

            $team_member = TeamMember::whereId($team_member_id)->whereUserId($user_id)->first();

            if(is_null($team_member)) {
                return $this->errorResponse("Member is not in your team", 400);
            }

            $user = User::findOrFail($user_id);

            $messages = [];
            $messages[] = 'Hi, ' . trim($team_member->first_name). ' ' . trim($team_member->last_name);
            $messages[] = 'Attached here is a task assigned to you by <b>'. trim($user->first_name).'  '.trim($user->last_name).'</b> of <b>' . trim($user->company). '</b>';
            $messages[] = 'Please note the task should be completed before <b>' . $enddate . '</b>';
            $messages[] = 'Thank you';

            $subject = ($task_type == 'implementation')? 'Implementation Task assigned to you' : 'Commitment Task assigned to you';

            $notice = [
                'user' => $user,
                'to' => trim($team_member->email),
                'type' => 'local',
                'messages' => $messages,
                'file_name' => $file_name,
                'subject' => $subject,
                'copy' => [$user->email],
            ];

            if ($task_type == "implementation"){
                TeamMemberTaskImplementation::firstOrCreate(
                    [
                        'team_member_id' => $team_member_id,
                        'task_id' => $request->id,
                    ]);
            }else{
                TeamMemberTaskCommitment::firstOrCreate(
                    [
                        'team_member_id' => $team_member_id,
                        'task_id' => $task_id,
                    ]);
            }

            ProcessEmail::dispatch($notice, 'assignment');

            $transform = new TeamMemberResource($team_member);
            return $this->successResponse($transform, 201);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Failed to assign task to team member', 400);
        }
    }

    public function changeDeadline(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'task_id' => 'required|exists:meeting_notes_implementation_actions,id',
                'deadline' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
                'task_id.required' => 'Task ID is required',
                'task_id.exists' => 'Task not found in the database',
                'deadline' => 'Deadline is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            $assessment_id = $request["assessment_id"];
            $task_id = $request["task_id"];

            if ($request["task_type"] === "implementation") {
                $team_member_task_implementation = MeetingNoteImplementationAction::where('id', $task_id)->first();
                if(is_null($team_member_task_implementation)) {
                    return $this->errorResponse("No record of the Task found", 200);
                }
    
                $deadline = Carbon::createFromFormat('Y-m-d H:i:s', $request["deadline"]);
    
                $team_member_task_implementation->deadline = $deadline;
                $team_member_task_implementation->save();

                $transform = new TeamMemberTaskImpResource($team_member_task_implementation);
                return $this->successResponse($transform, 201);
            }else{
                $team_member_task_implementation = MeetingNoteReminder::where('id', $task_id)->first();
                if(is_null($team_member_task_implementation)) {
                    return $this->errorResponse("No record of the Task found", 200);
                }
    
                $deadline = Carbon::createFromFormat('Y-m-d H:i:s', $request["deadline"]);
    
                $team_member_task_implementation->reminder_date = $deadline;
                $team_member_task_implementation->save();
    
                $transform = new TeamMemberTaskComResource($team_member_task_implementation);
                return $this->successResponse($transform, 201);
            }
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Failed to assign task to team member', 400);
        }

    }


    public function getTaskDeadline(Request $request)
    {
        try {
            if ($request["type"] === 'implementation') {
                $rules = [
                    'user_id' => 'required|exists:users,id',
                    'task_id' => 'required|exists:meeting_notes_implementation_actions,id',
                ];
    
                $messages = [
                    'user_id.required' => 'User ID is required',
                    'user_id.exists' => 'User not found in the database',
                    'task_id.required' => 'Task ID is required',
                    'task_id.exists' => 'Task not found in the database',
                ];
    
                $validator = Validator::make($request->all(), $rules, $messages);
    
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }
    
                $task_id = $request["task_id"];

                $team_member_task_implementation_action = MeetingNoteImplementationAction::where('id', $task_id)->first();
                if(is_null($team_member_task_implementation_action)) {
                    return $this->successResponse("No record of the Task found", 200);
                }
    
                $team_member = TeamMember::find($team_member_task_implementation_action->team_member_id);
                $transform = [
                    'responsibility' => (!is_null($team_member)) ? $team_member->first_name.' '.$team_member->last_name: null,
                    'deadline' => $team_member_task_implementation_action->deadline,
                ];
                return $this->successResponse($transform, 200);
            }else{
                $rules = [
                    'user_id' => 'required|exists:users,id',
                    'task_id' => 'required|exists:meeting_notes_reminder_tasks,id',
                ];
    
                $messages = [
                    'user_id.required' => 'User ID is required',
                    'user_id.exists' => 'User not found in the database',
                    'task_id.required' => 'Task ID is required',
                    'task_id.exists' => 'Task not found in the database',
                ];
    
                $validator = Validator::make($request->all(), $rules, $messages);
    
                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }
    
                $task_id = $request["task_id"];

                $team_member_task_commitment = MeetingNoteReminder::where('id', $task_id)->first();
                if(is_null($team_member_task_commitment)) {
                    return $this->successResponse("No record of the Task found", 200);
                }

                $team_member = TeamMember::find($team_member_task_commitment->team_member_id);
                $transform = [
                    'responsibility' => (!is_null($team_member)) ? $team_member->first_name.' '.$team_member->last_name: null,
                    'deadline' => $team_member_task_commitment->reminder_date,
                ];
                return $this->successResponse($transform, 200);
            }
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Failed to assign task to team member', 400);
        }
    }

}

