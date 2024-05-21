<?php

namespace App\Http\Controllers;

use Validator;
use Notification;
use App\Http\Requests;
use App\Models\Assessment;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Jobs\ProcessEmail;
use App\Http\Resources\Session as SessionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\Notifiable;


class SessionsController extends Controller
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
     * Display a listing of the resource by assessment_id.
     *
     * @return \Illuminate\Http\Response
     */
    public function assessment($id)
    {
        $sessions = Session::where('assessment_id', $id)->get()->sortByDesc('id');//Get all sessions by assessment_id

        $transform = SessionResource::collection($sessions);

        return $this->successResponse($transform,200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sessions = Session::all();//Get all meeting session notes

        $transform = SessionResource::collection($sessions);

        return $this->successResponse($transform,200);
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

        try
        {
            $validator = Validator::make($request->all(),[
                'assessment_id' => 'required|exists:assessments,id',
                'meeting_title' => 'required|string',
                'meeting_notes' => 'required|string',
                'coach_action_steps' => 'required|string',
                'current_revenue' => 'string',
                'client_action_steps' => 'required|string',
                'meeting_date' => 'required|string',
                'next_meeting_date' => 'required|string',
                'meeting_keywords' => 'required|string',
                ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $assessment = Assessment::findOrFail($request->assessment_id);

            $session = new Session;
            $session->assessment_id = $request->input('assessment_id');
            $session->meeting_title = trim($request->input('meeting_title'));
            $session->module_name = trim($request->input('meeting_title'));
            $session->current_revenue = trim($request->input('current_revenue'));
            $session->meeting_notes = trim($request->input('meeting_notes'));
            $session->coach_action_steps = trim($request->input('coach_action_steps'));
            $session->client_action_steps = trim($request->input('client_action_steps'));
            $session->next_meeting_date = $request->input('next_meeting_date');
            $session->meeting_date = $request->input('meeting_date');
            
            $session->meeting_keywords = trim($request->input('meeting_keywords'));

            $session->save();

            $transform = new SessionResource($session);

            return $this->showMessage($transform, 201);

        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Assessment not found', 400);
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

        try
        {
            $session = Session::findOrFail($id);

            $transform = new SessionResource($session);

            return $this->successResponse($transform,200);

        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Session record not found', 400);
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

        $validator = Validator::make($request->all(),[
            'assessment_id' => 'required|exists:assessments,id',
            'meeting_title' => 'required|string',
            'meeting_notes' => 'required|string',
            'current_revenue' => 'string',
            'coach_action_steps' => 'required|string',
            'client_action_steps' => 'required|string',
            'meeting_date' => 'required|string',
            'next_meeting_date' => 'required|string',
            'meeting_keywords' => 'required|string',
            ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try
        {
            $session = Session::findOrFail($id);
            $session->assessment_id = $request->input('assessment_id');
            $session->meeting_title = trim($request->input('meeting_title'));
            $session->module_name = trim($request->input('meeting_title'));
            $session->current_revenue = trim($request->input('current_revenue'));
            $session->meeting_notes = trim($request->input('meeting_notes'));
            $session->coach_action_steps = trim($request->input('coach_action_steps'));
            $session->client_action_steps = trim($request->input('client_action_steps'));
            $session->meeting_date = $request->input('meeting_date');
            $session->next_meeting_date = $request->input('next_meeting_date');
            $session->meeting_keywords = trim($request->input('meeting_keywords'));

            if($session->isDirty()){
                $session->save();
            }

            $transform = new SessionResource($session);
            return $this->showMessage($transform, 200);

        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Session record not found', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        try{
            $session = Session::findOrFail($id);
            $session->delete();
            return $this->singleMessage('Meeting Notes Deleted' ,202);
        }catch(ModelNotFoundException $e){
            return $this->errorResponse('Session record not found', 400);
        }

    }

    public function notesNotify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'meeting_title' => 'required|string',
                'meeting_notes' => 'required|string',
                'coach_action_steps' => 'required|string',
                'current_revenue' => 'string',
                'client_action_steps' => 'required|string',
                'meeting_date' => 'required|string',
                'next_meeting_date' => 'required|string',
                'meeting_keywords' => 'required|string',
                'to' => 'required|email',
                'from' => 'required|email',
                'cc' => 'nullable|string',

                ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $copy = [];
            $messages = [];

            if($request->cc){
               $cleancc = str_replace(' ', '', $request->cc);
               $copy = explode(',' ,trim($cleancc));
            }

            $messages[] = '<b>Meeting Held On:</b> ' . trim($request->meeting_date);
            $messages[] = '<b>Meeting Title:</b> ' . trim($request->meeting_title);
            $messages[] = '<b>Current Revenue:</b> ' . trim($request->current_revenue);
            $messages[] = '<b>Meeting Notes:</b> ' . trim($request->meeting_notes);
            $messages[] = '<b>Coach Action Steps:</b> ' . trim($request->coach_action_steps);
            $messages[] = '<b>Client Action Steps:</b> ' . trim($request->client_action_steps);
            $messages[] = '<b>Next Meeting Date:</b> ' . trim($request->next_meeting_date);
            $messages[] = '<b>Meeting Keywords:</b> ' . trim($request->meeting_keywords);

            $details = [
                'user' => $this->user,
                'to' => trim($request->to),
                'messages' => $messages,
                'subject' => 'Meeting Notes', 
                'copy' => $copy,
                'bcopy' => [],
            ];
            
            ProcessEmail::dispatch($details);

            return $this->showMessage('Your Email Was sent Successfully', 200);


        } catch(Exception $e){

            return $this->errorResponse('Error occured while trying to send email', 400);

        }
    }


}
