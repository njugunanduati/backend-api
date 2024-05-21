<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;

use Exception;
use App\Models\GetResponseIntegration;
use App\Models\IntegrationGroupList;
use App\Models\Integration;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Notifications\Notifiable;
use App\Http\Resources\GetResponseIntegration as GetResponseIntegrationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use phpDocumentor\Reflection\PseudoTypes\True_;

class GetResponseController extends Controller
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
            $integrations = GetResponseIntegration::all(); //Get all getresponse integrations

            $transform = GetResponseIntegrationResource::collection($integrations);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('GetResponse Integrations not found', 400);
        }
    }

    /**
     * Get a token from GetResponse
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getToken(Request $request){

        try {
        
            $rules = [
                'grant_type' => 'required',
                'code' => 'required'
            ];

            $info = [
                'grant_type.required' => 'Grant type is required',   
                'code.required' => 'Authorization code is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $form_data = [
                "grant_type" => $request->grant_type,
                "code" => $request->code
            ];

            $verb='POST'; 
            $base_url = env('GET_RESPONSE_ENDPOINT');
            $end_point = 'token';
            $access_token=null;
            $authentication_type='basic';
            $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $form_data, null);
            
            return $this->successResponse($response, 200);

        }catch (Exception $e) {
            return $this->errorResponse('Error in getting the token', 400);
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
            $integration = GetResponseIntegration::create($request->all());

            //Also add to integrations table
            $record_integration = Array ('user_id'=> $request->user_id,'getresponse'=>1 );

            //check if user has an active integrations record
            $record = Integration::where('user_id', $request->user_id)->first();

            if ($record) {
                $record->getresponse = 1;
                $record->aweber = 0;
                $record->active_campaign = 0;
                if ($record->isDirty()) {
                    $record->save();
                }
            } else {
                $record = Integration::create($record_integration);
            }
            
            $transform = new GetResponseIntegrationResource($integration);

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
     * @param  \App\Models\GetResponseIntegration  $integration
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {
            $integration = GetResponseIntegration::findOrFail($id);

            $transform = new GetResponseIntegrationResource($integration);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Integration not found', 400);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\GetResponseIntegration  $integration
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

            $integration = GetResponseIntegration::findOrFail($id);
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

            $transform = new GetResponseIntegrationResource($integration);
            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('integration not found', 400);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GetResponseIntegration $integration
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $integration = GetResponseIntegration::findOrFail($id);
            $integration->delete();

            return $this->singleMessage('integration Deleted', 202);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('integration not found', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GetResponseIntegration  $integration
     * @return \Illuminate\Http\Response
     */
    public function getByUser($id)
    {
        try {
            $integration = GetResponseIntegration::where('user_id', $id)->first();

            return $integration;
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Integration not found', 400);
        }
    }

    public function getExpiresIn(Request $request)
    {
        $now = Carbon::now('UTC');
        $now->addHours($request->expires_in/3600);
        return $now;
    }

    /**
     * Get Campaigns/Lists from GetResponse.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function getList(Request $request)
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

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            // Get user API details
            $details = GetResponseIntegration::where('user_id', $request->user_id)->first();
            
            if($details){
                // Check current token validity
                if ($this->validateToken($details)) {
                    // refresh token
                    $this->refreshToken($details);
                }
                $response = $this->makeListRequest($details);
                return $this->successResponse($response, 200);
            }else{
                return $this->successResponse(null, 200);
            }
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }
    }

    /**
     * Add Contact to a Campaigns/Lists on GetResponse.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addContact(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'email' => 'required|email',
                'campaignId' => 'required'
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
                'email.required' => 'Email is required',
                'email.email' => 'The email you entered is not a valid email',
                'campaignId.required' => 'Campiagn id is required'
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

            $form_data = [
                "name" => $request->name,
                "email" => $request->email,
                "dayOfCycle"=> "5",
                "campaign" => [
                    "campaignId" => $request->campaignId
                ],
            ];
            $response = $this->createContactRequest($details, $form_data);
            if ($response === null){
                $response = $request->name.' has been added succesfully';
            }
            return $this->successResponse($response, 200);
        }catch (Exception $e) {
            return $this->errorResponse('Error in adding a contact', 400);
        }
    }

    /**
     * Create Newletter for GetResponse.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createNewsletter(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'content' => 'required',
                'name' => 'required',
                'subject' => 'required',
                'campaignId' => 'required'
            ];

            $info = [
                'user_id.required' => 'User ID is required',   
                'user_id.exists' => 'That user does not exist', 
                'name.required' => 'The newsletter name is required',
                'subject.required' => 'The sudject of the newsletter is required',
                'campaignId.required' => 'Campiagn id is required'
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            // Get user details
            $user = User::findOrFail($request->user_id);

            // Get user API details
            $details = $this->getByUser($request->user_id);

            // Check current token validity
            if ($this->validateToken($details)) {
                // refresh token
                $this->refreshToken($details);
            }
            
            $now = Carbon::now('UTC');
            $now->addMinutes(3);
            $now = date("Y-m-d\\TH:i:sO", strtotime($now));

            $getFromFieldId = $this->getFromFieldId($details);
            $getFromFieldId = json_decode(json_encode($getFromFieldId), True);
            $fromFieldId = $getFromFieldId[0]['fromFieldId'];

            $form_data = array(
                "content" => [
                    "html" => false,
                    "plain" => $request->content
                ],
                "flags" => [
                    "openrate"
                ],
                "name"=> $request->campaignId,
                "type"=> "broadcast",
                "editor"=> "custom",
                "subject"=> $request->subject,
                "fromField" => (object)[
                    "fromFieldId"=> $fromFieldId
                ],
                "replyTo" => (object)[
                    "fromFieldId" => null
                ],
                "replyTo" => null,
                "campaign"=> (object)[
                    "campaignId"=> $request->campaignId
                ],
                "sendOn" => $now,
                "sendSettings" => [
                    "selectedCampaigns" => [
                        $request->campaignId
                    ],
                    "timeTravel" => "true",
                    "perfectTiming" => "false",
                    "selectedSegments"=>[],
                    "selectedSuppressions" => [],
                    "excludedCampaigns" => [],
                    "excludedSegments" => [],
                    "selectedContacts" => [
                        $request->campaignId
                    ]
                ]
            );
            $response = $this->createNewsletterRequest($details, $form_data);
            return $this->successResponse($response, 200);
        }catch (Exception $e) {
            return $this->errorResponse('Error in adding a contact', 400);
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
        $verb='POST'; 
        $base_url = env('GET_RESPONSE_ENDPOINT');
        $end_point = 'token';
        $data = ['grant_type'=>'refresh_token','refresh_token'=>$details->refresh_token];
        $now = Carbon::now('UTC');
        $authentication_type = 'basic';
        $access_token = $details->access_token;
        $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data);
        if ($response) {
           //save new data 
           $details->refresh_token = $response->refresh_token;
           $details->access_token = $response->access_token;
           $details->expires_in = $now->addHours($response->expires_in/3600);
           $details->save();
        }
        return $response;

    }

    public function genRandom($length)
    {
        $string = "abcdefghijklmnopqrstuvwxyz";
        $digits = "0123456789";
        $rand_gen = substr(str_shuffle($string.$digits), 0, $length);
        return $rand_gen;
    }

    public function validateToken($details)
    {
        $now = Carbon::now('UTC');
        $date = new Carbon($details->expires_in, 'UTC');
        return $now->greaterThanOrEqualTo($date);
    }

    public function getGroupList($group_id)
    {
        $integrationGroupList = IntegrationGroupList::where('group_id', $group_id)->first();
        return $integrationGroupList;;
  
    }

    public function getFromFieldId($data)
    {
        $verb='GET'; 
        $base_url = env('GET_RESPONSE_ENDPOINT');
        $end_point = 'from-fields';
        $authentication_type = 'token';
        $access_token = $data->access_token;
        $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data);
        return $response;
    }

    public function makeListRequest($data)
    {
        $verb='GET'; 
        $base_url = env('GET_RESPONSE_ENDPOINT');
        $end_point = 'campaigns';
        $authentication_type = 'token';
        $access_token = $data->access_token;
        $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data);
        return $response;

    }

    public function createContactRequest($data, $form_data)
    {
        $verb='POST'; 
        $base_url = env('GET_RESPONSE_ENDPOINT');
        $end_point= 'contacts';
        $access_token=$data->access_token;
        $authentication_type='token';
        $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data, $form_data);
        return $response;

    }

    public function createNewsletterRequest($data, $form_data){
        $verb='POST'; 
        $base_url = env('GET_RESPONSE_ENDPOINT');
        $end_point = 'newsletters';
        $access_token=$data->access_token;
        $authentication_type='token';
        $response = getresponseApiRequest($verb, $base_url, $end_point, $authentication_type, $access_token, $data, $form_data);
        return $response;
    }

}
