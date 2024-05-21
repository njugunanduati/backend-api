<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\Integration;
use App\Models\StripeIntegration;
use App\Models\AweberIntegration;
use App\Models\GetResponseIntegration;
use App\Models\IntegrationGroupList;
use App\Models\ActiveCampaignIntegration;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\Integration as IntegrationResource;
use App\Http\Resources\IntegrationGroupList as IntegrationGroupListResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class IntegrationsController extends Controller
{
    use ApiResponses;

    protected $user;

    public function __construct(Request $request)
    {
        $this->user = $request->user();
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {

        $integrations = Integration::all();//Get all integration

        $transform = IntegrationResource::collection($integrations);

        return $this->successResponse($transform,200);
    }

    /**
    * Display a listing of the resource by user id.
    *
    * @return \Illuminate\Http\Response
    */
    public function userIntegrations(Request $request){

        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist',  
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::findOrFail($request->user_id);

            $integration = getIntegration($user);

            return $this->successResponse($integration,200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That integration does not exist', 404);
        }

    }


    /**
    * Display a listing of the resource by integration id.
    *
    * @return \Illuminate\Http\Response
    */
    public function getAttachments(Request $request){

        try {

            $rules = [
                'integration_id' => 'required|exists:integrations,id',
            ];

            $info = [
                'integration_id.required' => 'Integrations ID is required',   
                'integration_id.exists' => 'That integrations does not exist',  
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $integration_id = $request->integration_id;

            $attachments = IntegrationGroupList::where('integration_id', $integration_id)->get();
    
            $transform = IntegrationGroupListResource::collection($attachments);

            return $this->successResponse($transform,200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That integration does not exist', 404);
        }

    }

    /**
    * Get all the students by their group id.
    *
    * @return \Illuminate\Http\Response
    */
    public function getGroupMembers($group_id){

        $students = DB::table('member_group_lesson')
                    ->join('users', 'users.id', '=', 'member_group_lesson.user_id')
                    ->select('users.id','users.first_name','users.last_name','users.email','users.role_id')
                    ->where('member_group_lesson.user_id', '!=' , DB::raw('member_group_lesson.invited_by')) // drop off the coach
                    ->where('member_group_lesson.group_id', $group_id)
                    ->where('member_group_lesson.invited_by', $this->user->id)
                    ->groupBy('member_group_lesson.user_id')->orderBy('member_group_lesson.user_id')->get();
        return $students;
    }

    
    /**
    * Add initial subscribers to the list.
    *
    * @return \Illuminate\Http\Response
    */
    public function addInitialSubscribers($request){
        $responses = [];
        if($request->type === 'aweber'){

            // Get all the users/students in this group
            $students = $this->getGroupMembers($request->group_id);
            
            if(count($students) > 0){
                // Loop through all the studenst and add trhem to Aweber list
                foreach($students as $key => $std){
                    $responses[] = addSingleAweberSubscriber($this->user, $request->group_id, $std->email, $std->first_name.' '.$std->last_name);
                }
            }
        }
        if($request->type === 'acampaign'){

            // Get all the users/students in this group
            $students = $this->getGroupMembers($request->group_id);

            if(count($students) > 0){
                // Loop through all the studenst and add trhem to ActiveCampaign list
                foreach($students as $key => $std){
                    $responses[] = addSingleACampaignSubscriber($this->user, $request->group_id, $std->email, $std->first_name, $std->last_name);
                }
            }
        }
        if($request->type === 'getresponse'){

            // Get all the users/students in this group
            $students = $this->getGroupMembers($request->group_id);

            if(count($students) > 0){
                // Loop through all the studenst and add trhem to ActiveCampaign list
                foreach($students as $key => $std){
                    $responses[] = addSingleGetResponseSubscriber($this->user, $request->group_id, $std->email, $std->first_name, $std->last_name);
                }
            }
        }
        return $responses;
    }
    

    /**
    * Store records and display a listing of the resource by integration id.
    *
    * @return \Illuminate\Http\Response
    */
    public function saveAttachments(Request $request){

        try {

            $rules = [
                'integration_id' => 'required|exists:integrations,id',
                'group_id' => 'required|exists:user_groups,id',
                'list_id' => 'required',
                'type' => 'required',
            ];

            $info = [
                'integration_id.required' => 'Integrations ID is required',   
                'integration_id.exists' => 'That integrations does not exist',  
                'group_id.required' => 'User group ID is required',   
                'group_id.exists' => 'That user group does not exist',  
                'list_id.required' => 'List ID is required',   
                'type.required' => 'Attachment type is required',   
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $integration_id = $request->integration_id;
            $group_id = $request->group_id;
            $list_id = $request->list_id;

            $integration_list = new IntegrationGroupList;

            $integration_list->integration_id = $integration_id;
            $integration_list->group_id = $group_id;
            $integration_list->list_id = $list_id;
            $integration_list->save();

            $attachments = IntegrationGroupList::where('integration_id', $integration_id)->get();
            
            // Add current group members/students to the list
            $responses = $this->addInitialSubscribers($request);
            $attachments[0]->responses = $responses;
            $transform = IntegrationGroupListResource::collection($attachments);
            
            return $this->successResponse($transform,200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That integration does not exist', 404);
        }

    }

    
    /**
    * Remove an integration.
    *
    * @return \Illuminate\Http\Response
    */
    public function removeIntegrations(Request $request){

        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'type' => 'required',
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
                'type.required' => 'Type of integration required',    
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->user_id;
            $type = $request->type;

            // Remove a stripe integrations
            if($type == 'stripe'){

                $stripe_id = $request->stripe_id;
                
                StripeIntegration::where('user_id', $user_id)->where('stripe_id', $stripe_id)->delete();

                Integration::updateOrCreate(
                    [
                        'user_id' => $user_id,
                    ], 
                    [
                        'stripe' => 0,
                    ]
                );

                $integration = getIntegration($this->user);

                return $this->successResponse($integration,200);
            }

            // Remove Aweber integration and any group-list attached
            if($type == 'aweber'){
                
                AweberIntegration::where('user_id', $request->user_id)->delete();

                Integration::updateOrCreate(
                    [
                        'user_id' => $request->user_id,
                    ], 
                    [
                        'aweber' => 0,
                    ]
                );

                $integration = getIntegration($this->user);

                IntegrationGroupList::where('integration_id', $integration->id)->delete();

                return $this->successResponse($integration,200);
            }

            // Remove ActiveCampaign integration and any group-list attached
            if($type == 'activecampaign'){
                
                ActiveCampaignIntegration::where('user_id', $request->user_id)->delete();

                Integration::updateOrCreate(
                    [
                        'user_id' => $request->user_id,
                    ], 
                    [
                        'active_campaign' => 0,
                    ]
                );

                $integration = getIntegration($this->user);

                IntegrationGroupList::where('integration_id', $integration->id)->delete();

                return $this->successResponse($integration,200);
            }

            // Remove GetResponse integration and any group-list attached
            if($type == 'getresponse'){
                
                GetResponseIntegration::where('user_id', $request->user_id)->delete();

                $integration = Integration::updateOrCreate(
                    [
                        'user_id' => $request->user_id,
                    ], 
                    [
                        'getresponse' => 0,
                    ]
                );

                $integration = getIntegration($this->user);

                IntegrationGroupList::where('integration_id', $integration->id)->delete();

                return $this->successResponse($integration,200);
            }
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That integration does not exist', 404);
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
    public function store(Request $request) {

    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {

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
    public function destroy($id) {

    }
}
