<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;

use Exception;
use App\Models\AweberIntegration;
use App\Models\IntegrationGroupList;
use App\Models\Integration;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Notifications\Notifiable;
use App\Http\Resources\AweberIntegration as AweberIntegrationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class AweberController extends Controller
{
    use ApiResponses, Notifiable;

    protected $account_id;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $integrations = AweberIntegration::all(); //Get all aweber integrations

            $transform = AweberIntegrationResource::collection($integrations);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Aweber Integrations not found', 400);
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
                'user_id' => 'required|exists:users,id',
                'auth_code' => 'required|string',
                'state' => 'required|string|string',
                'access_token' => 'required|string',
                'refresh_token' => 'required|string',
                'expires_in' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $integration = AweberIntegration::create($request->all());

            //Also add to integrations table
            $record_integration = Array ('user_id'=> $request->user_id,'aweber'=>1 );

            //check if user has an active integrations record
            $record = Integration::where('user_id', $request->user_id)->first();

            if ($record) {
                $record->aweber = 1;
                $record->active_campaign = 0;
                if ($record->isDirty()) {
                    $record->save();
                }
            } else {
                $record = Integration::create($record_integration);
            }
            
            $transform = new AweberIntegrationResource($integration);

            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Something went wrong', 400);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AweberIntegration  $integration
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {
            $integration = AweberIntegration::findOrFail($id);

            $transform = new AweberIntegrationResource($integration);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Integration not found', 400);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AweberIntegration  $integration
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'account_id' => 'required',
                'auth_code' => 'required|string',
                'state' => 'required|string|string',
                'access_token' => 'required|string',
                'refresh_token' => 'required|string',
                'expires_in' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $integration = AweberIntegration::findOrFail($id);
            $integration->user_id = $request->user_id;
            $integration->account_id = $request->account_id;
            $integration->auth_code = $request->auth_code;
            $integration->state = $request->state;
            $integration->access_token = $request->access_token;
            $integration->refresh_token = $request->refresh_token;
            $integration->expires_in = $request->expires_in;

            if ($integration->isDirty()) {
                $integration->save();
            }

            $transform = new AweberIntegrationResource($integration);
            return $this->showMessage($transform, 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('integration not found', 400);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AweberIntegration $integration
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $integration = AweberIntegration::findOrFail($id);
            $integration->delete();

            return $this->singleMessage('integration Deleted', 202);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('integration not found', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AweberIntegration  $integration
     * @return \Illuminate\Http\Response
     */
    public function getByUser($id)
    {

        try {
            $integration = AweberIntegration::where('user_id', $id)->first();

            return $integration;
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Integration not found', 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getLists(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            // Get user API details
            $details = $this->getByUser($request->user_id);

            if($details){
                // Check current token validity

                if ($this->validateToken($details)) {

                    // refresh token
                    $this->refreshToken($details);
                    
                }

                $response = $this->makeListRequest($details);
                return $this->successResponse($response, 200);
            }
            
            return $this->successResponse(null, 200);
                
        } catch (Exception $e) {
            dd($e);
            return $this->errorResponse('integration not found', 400);
        }
    }

     /**
     * Update the specified resource in storage.
     *
     * @param $refresh_token
     * @return $token
     */

    public function refreshToken($details)
    {
        try {

        $end_point= 'oauth2/token';
        $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
        $verb='POST'; 
        $base_url='https://auth.aweber.com';
        $data = ['grant_type'=>'refresh_token','refresh_token'=>$details->refresh_token];
        $now = Carbon::now('UTC');
        $authentication_type='basic';

        $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type, $access_token=NULL, $data);
        
        if ($response) {
           //save new data 
           $details->refresh_token = $response->refresh_token;
           $details->access_token = $response->access_token;
           $details->expires_in = $now->addHours($response->expires_in/3600);
           $details->save();
        }
       
        // return $response;

    }

        catch (Exception $e) {
            return $this->errorResponse('Error communicating with aweber', 400);
        }

    }

    public function validateToken($details){
            $now = Carbon::now('UTC');
            $date = new Carbon($details->expires_in, 'UTC'); 
            return $now->greaterThanOrEqualTo($date);
    }

    public function getGroupList($group_id)
    {

        $integrationGroupList = IntegrationGroupList::where('group_id', $group_id)->first();

        return $integrationGroupList;;

        
}

    public function makeListRequest($data)
    {
        
        $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
        $verb='get'; 
        $base_url='https://api.aweber.com/1.0';
        if($data->account_id === NULL){
            $account = (object)$this->makeAccountRequest($data->access_token);
            $this->account_id = $account->entries[0]['id'];
            $data->account_id = $this->account_id;
            $data->save();
        }
        $end_point= 'accounts/'.$data->account_id.'/lists';

        $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type='token', $access_token=$data->access_token, $data);
        return $response;

    }

    public function makeAccountRequest($token)
    {

        $end_point= 'accounts';
        $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
        $verb='get'; 
        $base_url='https://api.aweber.com/1.0';
        $data = [];

        $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type='token', $access_token=$token, $data);
        return $response;

    }


    public function subscribeToListRequest(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'ad_tracking' => '',
                'custom_fields' => '',
                'email' => 'required|email',
                'ip_address' => '',
                'group_id' => 'required|exists:user_groups,id',//user_groups table
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

             // Get user API details
             $details = $this->getByUser($request->user_id);


              // Check current token validity

            if ($this->validateToken($details)) {

                // refresh token
                $this->refreshToken($details);
               
                
            }
            

            $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
            $verb='post'; 
            $base_url='https://api.aweber.com/1.0';
            $data = ['ws.op' => 'create','ad_tracking'=>$request->ad_tracking,'custom_fields'=>$request->custom_fields,'email'=>$request->email,'ip_address'=>$request->ip_address];

            // Get integration group list

            $integration_group_list = $this->getGroupList($request->group_id);
            if ($integration_group_list) {
                $list_id = $integration_group_list->list_id;

                // https://api.aweber.com/1.0/accounts/{accountId}/lists/{listId}/subscribers

            $end_point= 'accounts/'.$details->account_id.'/lists/'.$list_id.'/subscribers';
        

            $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type='token', $access_token=$details->access_token, $data);

            return $this->successResponse($response, 200);

            }

            return $this->errorResponse('Error occured. Check your List!', 400);

   
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            dd($e);
            return $this->errorResponse('integration not found', 400);
        }

        

    }
    public function createBroadcastRequest(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'body_html' => 'required',
                'body_text' => 'required',
                'click_tracking_enabled' => '',
                'subject' => 'required',
                'group_id' => 'required|exists:user_groups,id',//user_groups table
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

             // Get user API details
             $details = $this->getByUser($request->user_id);


              // Check current token validity

            if ($this->validateToken($details)) {

                // refresh token
                $this->refreshToken($details);
                
            }

            $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
            $verb='post'; 
            $base_url=env('AWEBER_BASE_URL', 'https://api.aweber.com/1.0');
            $data = ['body_html'=>$request->body_html,'body_text'=>$request->body_text,'subject'=>$request->subject,'click_tracking_enabled'=>$request->click_tracking_enabled];

            // Get integration group list

            $integration_group_list = $this->getGroupList($request->group_id);

            if ($integration_group_list) {
                $list_id = $integration_group_list->list_id;

                 // https://api.aweber.com/1.0/accounts/1793299/lists/6090248/broadcasts

                $end_point = 'accounts/'.$details->account_id.'/lists/'.$list_id.'/broadcasts';
        
                $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type='token', $access_token=$details->access_token, $data);

                //trigger schedule broadcast

                $schedule_broadcast = $this->scheduleBroadcastRequest($response->self_link, $details);
                if ($schedule_broadcast) {
                    return $this->successResponse($response, 200); 
                }
                return $this->errorResponse('Error occured. while sending message!', 400);
                

            }

            return $this->errorResponse('Error occured. Check your List!', 400);
                   
           
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            dd($e);
            return $this->errorResponse('integration not found', 400);
        } 

    }

    public function scheduleBroadcastRequest($self_link, $details){
        $now = Carbon::now('UTC');
        $scheduled_for = $now->addSeconds(15);

        $headers = ['Content-Type'=>'application/x-www-form-urlencoded', 'Accept'=> 'application/json'];
        $verb='post'; 
        $base_url=$self_link;
        $data = ['scheduled_for'=> $scheduled_for];

        $end_point= '';

        // https://api.aweber.com/1.0/accounts/1793299/lists/6090248/broadcasts/50282760/schedule

        $response = aweberApiRequest($verb, $base_url, $end_point, $headers, $authentication_type='token', $access_token=$details->access_token, $data);
        if ($response) {
            return true;
        }

        return false;

    }
}
