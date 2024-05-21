<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;

use App\Models\User;
use App\Models\UserEvent;
use App\Models\UserModuleAccessMetaData;
use App\Http\Resources\UserEvent as EventResource;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class EventsController extends Controller
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
    public function analysis(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $access = UserEvent::where('user_id', $request->user_id)->get();

            $transform = EventResource::collection($access);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User event analysis not found', 400);
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
                'user_id' => 'required|exists:users,id',
                'event_id' => 'required',
                'event_name' => 'required',
                'status' => 'required',
            ];

            $messages = [
                'event_id.required' => 'The event id is required',
                'event_name.required' => 'The event name is required',
                'status.required' => 'The status is required',
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user doe not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $status = $request->status;
            $event_id = $request->event_id;
            $event_name = $request->event_name;
            
            // Get user
            $user = User::findOrfail($user_id);
            $user_event_data = UserEvent::where([
                'event_id' => $event_id,
                'user_id' => $user_id
            ])->first();
            $date = Carbon::now();
            if (!isset($user_event_data)) {
                // Enable event for the client
                UserEvent::updateOrCreate([
                    'user_id' => $user_id,
                    'event_id' => $event_id,
                ], ['access' => (int)$status]);
                $access = '';
                if ((int)$status === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }

                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => $event_name,
                    'description' => $event_name.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);

            }else{
                // update the logs
                if ((int)$status != $user_event_data->access) {
                    $access = '';
                    if ((int)$status === 1) {
                        $access = 'Granted';
                    }else{
                        $access = 'Revoked';
                    }
                    UserModuleAccessMetaData::create([
                        'user_id' => $user->id,
                        'module_name' => $event_name,
                        'description' => $event_name.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                        'changed_by' => $this->user->id,
                        'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                    ]);
                }
                // Enable event for the client
                UserEvent::updateOrCreate([
                    'user_id' => $user_id,
                    'event_id' => $event_id,
                ], ['access' => (int)$status]);
            }
            
            

            $access = UserEvent::where('user_id', $user_id)->get();
            // Revoke current user token
            $user->tokens()->delete();

            $transform = EventResource::collection($access);
            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Events not found', 400);
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
