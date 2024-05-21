<?php

namespace App\Http\Controllers;

use Validator;

use App\Models\Notification;
use App\Models\NotificationAnalysis;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\NotificationAnalysis as AnalysisResource;
use App\Http\Resources\Notification as NotificationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationController extends Controller
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
        $notifications = Notification::all()->sortByDesc('id');

        $transform = NotificationResource::collection($notifications);

        return $this->successResponse($transform, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    public function analysis(Request $request) {
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

            $user_id = $request->user_id;

            $total = Notification::all()->count();
            
            $read = NotificationAnalysis::where('user_id', $user_id)->get();

            $ids = ($read->count() > 0)?$read->pluck('notification_id')->all() : [];

            $unread_count = ($total - $read->count());

            $unread = [];

            if($unread_count > 0){
                $unread = Notification::whereNotIn('id', $ids)->get();
            }

            return $this->successResponse(['total' => $total, 'read' => $read->count(), 'unread_count' => $unread_count, 'read_ids' => $ids, 'unread' => $unread],200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Notification analysis not found', 400);
        }

    }


    public function toggleAnalysis(Request $request) {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'notification_id' => 'required|exists:notifications,id',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
                'notification_id.required' => 'Notification ID is required',
                'notification_id.exists' => 'Notification not found in the database',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $notification_id = $request->notification_id;

            $found = NotificationAnalysis::where('user_id', $user_id)->where('notification_id', $notification_id)->first();

            // If the anaysis was found, delete it to have the notification as unread
            if($found){
              $found->delete();  
            }else{
                NotificationAnalysis::firstOrCreate($request->all());
            }

            $total = Notification::all()->count();
            
            $read = NotificationAnalysis::where('user_id', $user_id)->get();

            $ids = ($read->count() > 0)?$read->pluck('notification_id')->all() : [];

            $unread_count = ($total - $read->count());

            $unread = [];
            
            if($unread_count > 0){
                $unread = Notification::whereNotIn('id', $ids)->get();
            }

            return $this->successResponse(['total' => $total, 'read' => $read->count(), 'unread_count' => $unread_count, 'read_ids' => $ids, 'unread' => $unread],200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Notification analysis not found', 400);
        }

    }


    public function toggleAll(Request $request) {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id'
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User not found in the database',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;

            $ar = NotificationAnalysis::where('user_id', $user_id)->get();
            $a = Notification::all();

            if($ar->count() > 0){

                $b = $a->pluck('id')->all();
                $c = $ar->pluck('notification_id')->all();
                $result = array_diff($b, $c);
                
                foreach ($result as $key => $value) {
                    NotificationAnalysis::firstOrCreate(['user_id' => $user_id, 'notification_id' => $value]);
                }
            }else{
                $i = $a->pluck('id')->all();
                foreach ($i as $key => $value) {
                    NotificationAnalysis::firstOrCreate(['user_id' => $user_id, 'notification_id' => $value]);
                }
            }

            $total = Notification::all()->count();
            
            $read = NotificationAnalysis::where('user_id', $user_id)->get();

            $ids = ($read->count() > 0)?$read->pluck('notification_id')->all() : [];

            $unread_count = ($total - $read->count());

            $unread = [];
            
            if($unread_count > 0){
                $unread = Notification::whereNotIn('id', $ids)->get();
            }

            return $this->successResponse(['total' => $total, 'read' => $read->count(), 'unread_count' => $unread_count, 'read_ids' => $ids, 'unread' => $unread],200);


        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Notification analysis not found', 400);
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
                'type' => 'required',
                'title' => 'required',
                'description' => 'required',
            ];

            $messages = [
                'type.required' => 'Type is required',
                'title.required' => 'Title is required',
                'description.required' => 'Description is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $type = trim($request->type);
            $title = trim($request->title);
            $description = trim($request->description);

            if ($request->has('id')) { // Edit the notification
                $notification = Notification::findOrfail($request->id); //Get by id
                $notification->type = $type;
                $notification->title = $title;
                $notification->description = $description;
                $notification->save();
            }else{
                $notification = new Notification;
                $notification->type = $type;
                $notification->title = $title;
                $notification->description = $description;
                $notification->save();
            }

            $notifications = Notification::all()->sortByDesc('id');

            $transform = NotificationResource::collection($notifications);

            return $this->successResponse($transform, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Notification analysis not found', 400);
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
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {

        try {

            $rules = [
                'notification_id' => 'required|exists:notifications,id',
            ];

            $messages = [
                'notification_id.required' => 'Notifications ID is required',
                'notification_id.exists' => 'Notifications not found in the database',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->notification_id;

            $notification = Notification::findOrFail($id);
            
            $notification->delete(); //Delete the notification
            
            $notifications = Notification::all()->sortByDesc('id');

            $transform = NotificationResource::collection($notifications);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This notification does not exist', 404);
        }
    }

}

