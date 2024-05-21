<?php

namespace App\Http\Controllers;

use Exception;
use Validator;

use App\Models\User;
use App\Models\ActiveCampaignIntegration;
use App\Models\Integration;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\ActiveCampaignIntegrationResource;
use PHPUnit\Util\Json;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ActiveCampaignController extends Controller
{
    use ApiResponses;

     /**
    * Get user active campaign credentials (url and api_key)
    *
    * @return \Illuminate\Http\Response
    */
    public function getCredentials($id)
    {
        try {

            $user = User::findOrFail($id);

            $findAcCreds = ActiveCampaignIntegration::whereUserId($user->id)->first();

            if($findAcCreds){
                $transform = ActiveCampaignIntegrationResource::make($findAcCreds);
                return $this->successResponse($transform, 200);
            }else{
                return $this->successResponse(null, 200);
            }
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('No credentials set for this user.', 404);
        }
    }

    /**
    * Add user url and api_key
    *
    * @return \Illuminate\Http\Response
    */
    public function addCredentials(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
                'url' => 'required',
                'api_key' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User does not exist',
                'url.required' => 'Url is required',
                'api_key.required' => 'Api Key is required',
            ];

            $validator = Validator::make($request->json()->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $data = $request->json()->all();
            $findAcCreds = ActiveCampaignIntegration::updateOrCreate(
                [
                    'user_id' => $data['user_id'], 
                ], 
                [
                    'url' => $data['url'], 
                    'api_key' => $data['api_key'],
                    'is_active' => true,
                ]
            );

            $integration = Integration::updateOrCreate(
                [
                    'user_id' => $data['user_id'],
                ],
                [
                    'active_campaign' => true,
                    'aweber' => false
                ]
            );
            $transform = ActiveCampaignIntegrationResource::make($findAcCreds);
            return $this->successResponse($transform, 200);

        } catch(Exception $e){
            return $this->errorResponse($e->getMessage(), 400);

        }

    }

    /**
    * Deactivate the active campaign integration
    *
    * @return \Illuminate\Http\Response
    */
    public function deleteCredentials($id)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id'
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
            ];

            $validator = Validator::make(['user_id' => $id], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $findAcCreds = ActiveCampaignIntegration::whereUserId($id)->first();
            $findAcCreds->is_active = false;
            $findAcCreds->save();

            $integration = Integration::whereUserId($id)->first();
            $integration->active_campaign = false;
            $integration->save();
    
            $transform = ActiveCampaignIntegrationResource::make($findAcCreds);
            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }

    }

    /**
    * Get lists for a coach.
    *
    * @return \Illuminate\Http\Response
    */
    public function viewLists($user_id)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id'
            ];
            $messages = [
                'user_id.required' => 'User ID is required',
            ];
            $validator = Validator::make(['user_id' => $user_id], $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::find($user_id);

            $active_campaign = ActiveCampaignIntegration::whereUserId($user_id)->first();
            
            if($active_campaign){
                $url = $active_campaign->url;
                $api_key = $active_campaign->api_key;

                $params = array(
                    'api_key'      => $api_key,
                    'api_action'   => 'list_add',
                    'api_output'   => 'json',
                );
                $params = array(
                    'api_key'      => $api_key,
                    'api_action'   => 'list_list',
                    'api_output'   => 'json',
                    'ids'          => 'all',
                    'full'         => 0,
                );
                // This section takes the input fields and converts them to the proper format
                $query = "";
                foreach( $params as $key => $value ) $query .= urlencode($key) . '=' . urlencode($value) . '&';
                $query = rtrim($query, '& ');

                // clean up the url
                $url = rtrim($url, '/ ');

                // This sample code uses the CURL library for php to establish a connection,
                // submit your request, and show (print out) the response.
                if ( !function_exists('curl_init') ) die('CURL not supported. (introduced in PHP 4.0.2)');

                // If JSON is used, check if json_decode is present (PHP 5.2.0+)
                if ( $params['api_output'] == 'json' && !function_exists('json_decode') ) {
                    die('JSON not supported. (introduced in PHP 5.2.0)');
                }

                // define a final API request - GET
                $api = $url . '/admin/api.php?' . $query;

                $response = $this->getCurl($api);

                if ( !$response ) {
                    die('Nothing was returned. Do you have a connection to Email Marketing server?');
                }

                $result = json_decode($response, TRUE);



                if ($result['result_code'] == 1){

                    $r = [];
                    foreach( $result as $key => $v ){
                        if(is_numeric($key)){
                          $r[] = $v;  
                        }
                    }

                    $response_data = array(
                        'data' => $r,
                        'status_code' => $result['result_code'],
                        'message'=> $result['result_message'],
                    );
                    return response()->json($response_data, 200);
                }else{
                    $response_data = array(
                        'status_code' => $result['result_code'],
                        'message'=> $result['result_message'],
                    );
                    return response()->json($response_data, 404);

                }
            }

            return response()->json(null, 200);
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }

    }

    /**
    * Add a new contact to a list.
    * send the message using the campaign send api
    *
    * @return \Illuminate\Http\Response
    */
    public function addContact(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id'
            ];
            $messages = [
                'user_id.required' => 'User ID is required',
            ];
            $request_data = $request->json()->all();
            $validator = Validator::make(['user_id' => $request_data['user_id']], $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::find($request_data['user_id']);

            $active_campaign = ActiveCampaignIntegration::whereUserId($request_data['user_id'])->first();
            $url = $active_campaign->url;
            $api_key = $active_campaign->api_key;
            $list_id = $request_data['list_id'];

            $params = array(
                'api_key'      => $api_key,
                'api_action'   => 'contact_add',
                'api_output'   => 'json',
            );

            // here we define the data we are posting in order to perform an update
            $post = array(
                'email'                    => $request_data['email'],
                'first_name'               => $request_data['first_name'],
                'last_name'                => $request_data['last_name'],
                'phone'                    => '',
                'customer_acct_name'       => $user->company,
                'tags'                     => 'api',
                //'ip4'                    => '127.0.0.1',
                // assign to lists:
                'p['.$list_id.']'                   => intval($list_id), // example list ID (REPLACE '123' WITH ACTUAL LIST ID, IE: p[5] = 5)
                'status['.$list_id.']'              => 1, // 1: active, 2: unsubscribed (REPLACE '123' WITH ACTUAL LIST ID, IE: status[5] = 1)
                'instantresponders[0]' => 1, // set to 0 to if you don't want to sent instant autoresponders
            );

            // This section takes the input fields and converts them to the proper format
            $query = "";
            foreach( $params as $key => $value ) $query .= urlencode($key) . '=' . urlencode($value) . '&';
            $query = rtrim($query, '& ');

            // This section takes the input data and converts it to the proper format
            $data = "";
            foreach( $post as $key => $value ) $data .= urlencode($key) . '=' . urlencode($value) . '&';
            $data = rtrim($data, '& ');

            // clean up the url
            $url = rtrim($url, '/ ');

            // This sample code uses the CURL library for php to establish a connection,
            // submit your request, and show (print out) the response.
            if ( !function_exists('curl_init') ) die('CURL not supported. (introduced in PHP 4.0.2)');

            // If JSON is used, check if json_decode is present (PHP 5.2.0+)
            if ( $params['api_output'] == 'json' && !function_exists('json_decode') ) {
                die('JSON not supported. (introduced in PHP 5.2.0)');
            }

            // define a final API request - GET
            $api = $url . '/admin/api.php?' . $query;
            $response = $this->postCurl($api, $data);

            if ( !$response ) {
                die('Nothing was returned. Do you have a connection to Email Marketing server?');
            }
            // Result info that is always returned
            $result = json_decode($response, TRUE);

            if ($result['result_code'] == 1){
                $response_data = array(
                    'status_code' => $result['result_code'],
                    'subscriber_id' => $result['subscriber_id'],
                    'message'=> $result['result_message'],
                );
                return response()->json($response_data, 201);
            }else{
                $response_data = array(
                    'status_code' => $result['result_code'],
                    'message'=> $result['result_message'],
                );
                return response()->json($response_data, 404);

            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }
    }

    /**
    * Create a new message.
    *
    * @return \Illuminate\Http\Response
    */
    public function createMessage(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id'
            ];
            $messages = [
                'user_id.required' => 'User ID is required',
            ];
            $request_data = $request->json()->all();
            $validator = Validator::make(['user_id' => $request_data['user_id']], $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $user = User::find($request_data['user_id']);
            $active_campaign = ActiveCampaignIntegration::whereUserId($request_data['user_id'])->first();
            $url = $active_campaign->url;
            $api_key = $active_campaign->api_key;
            $list_id = $request_data['list_id'];
            $html_body = $request_data['body'];


            $params = array(
                'api_key'      => $api_key,
                'api_action'   => 'message_add',
                'api_output'   => 'json'
            );

            // here we define the data we are posting in order to perform an update
            $post = array(
                'format'    => 'html',
                'subject'      => $request_data['subject'],
                'fromemail'      => $user->email,
                'fromname'      => $user->first_name.' '.$user->last_name,
                'reply2'            => $user->email,
                'priority'        => '1',
                'charset'          => 'utf-8',
                'encoding'        => 'quoted-printable',
                'htmlconstructor'    => 'editor',
                'html'            => $html_body,
                'text'            => strip_tags($html_body),
                'textfetch'            => $user->website,
                'textfetchwhen'      => 'send',
                'p['.$list_id.']'    => intval($list_id), // example list ID
            );


            // This section takes the input fields and converts them to the proper format
            $query = "";
            foreach( $params as $key => $value ) $query .= urlencode($key) . '=' . urlencode($value) . '&';
            $query = rtrim($query, '& ');
            $data = "";
            foreach( $post as $key => $value ) $data .= urlencode($key) . '=' . urlencode($value) . '&';
            $data = rtrim($data, '& ');
            $url = rtrim($url, '/ ');
            // This sample code uses the CURL library for php to establish a connection,
            // submit your request, and show (print out) the response.
            if ( !function_exists('curl_init') ) die('CURL not supported. (introduced in PHP 4.0.2)');
            // If JSON is used, check if json_decode is present (PHP 5.2.0+)
            if ( $params['api_output'] == 'json' && !function_exists('json_decode') ) {
                die('JSON not supported. (introduced in PHP 5.2.0)');
            }
            // define a final API request - GET
            $api = $url . '/admin/api.php?' . $query;
            $response = $this->postCurl($api, $data);

            if ( !$response ) {
                die('Nothing was returned. Do you have a connection to Email Marketing server?');
            }
            $result = json_decode($response, TRUE);

            // Result info that is always returned

            if ($result['result_code'] == 1){
                $message_response = array(
                    'status_code' => $result['result_code'],
                    'message_id' => $result['id'],
                    'subject' => $request_data['subject'],
                    'message'=> $result['result_message'],
                );
                return response()->json($message_response, 201);
            }else{
                $message_response = array(
                    'status_code' => $result['result_code'],
                    'message'=> $result['result_message'],
                );
                return response()->json($message_response, 404);

            }

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }

    }

    /**
    * Create a new campaign.
    *
    * @return \Illuminate\Http\Response
    */
    public function createCampaign(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id'
            ];
            $messages = [
                'user_id.required' => 'User ID is required',
            ];
            $request_data = $request->json()->all();
            $validator = Validator::make(['user_id' => $request_data['user_id']], $rules, $messages);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $active_campaign = ActiveCampaignIntegration::whereUserId($request_data['user_id'])->first();
            $url = $active_campaign->url;
            $api_key = $active_campaign->api_key;
            $list_id = $request_data['list_id'];
            $message_id = $request_data['message_id'];

            $params = array(
                'api_key'      => $api_key,
                'api_action'   => 'campaign_create',
                'api_output'   => 'json'
            );
            $post = array(
                'type' => $request_data['type'],
                'segmentid'        => 0,
                'name'      => $request_data['campaign_name'].':' . date("m/d/Y H:i", strtotime("now")),
                'sdate'      => date("Y-m-d H:i", strtotime("now")),
                'status'        => 1,
                // if campaign should be visible via public side
                'public'        => 0,
                'tracklinks'    => 'all',
                'trackreads'    => 1,
                'trackreplies'    => 0,
                // append unsubscribe link to the bottom of HTML body
                'htmlunsub'        => 1,
                // append unsubscribe link to the bottom of TEXT body
                'textunsub'        => 1,
                'p['.$list_id.']'  => 2,
                'm['.$message_id.']'  => 100,
            );

            // This section takes the input fields and converts them to the proper format
            $query = "";
            foreach( $params as $key => $value ) $query .= urlencode($key) . '=' . urlencode($value) . '&';
            $query = rtrim($query, '& ');
            $data = "";
            foreach( $post as $key => $value ) $data .= urlencode($key) . '=' . urlencode($value) . '&';
            $data = rtrim($data, '& ');
            $url = rtrim($url, '/ ');
            // This sample code uses the CURL library for php to establish a connection,
            // submit your request, and show (print out) the response.
            if ( !function_exists('curl_init') ) die('CURL not supported. (introduced in PHP 4.0.2)');
            // If JSON is used, check if json_decode is present (PHP 5.2.0+)
            if ( $params['api_output'] == 'json' && !function_exists('json_decode') ) {
                die('JSON not supported. (introduced in PHP 5.2.0)');
            }
            // define a final API request - GET
            $api = $url . '/admin/api.php?' . $query;
            $response = $this->postCurl($api, $data);

            if ( !$response ) {
                die('Nothing was returned. Do you have a connection to Email Marketing server?');
            }
            $result = json_decode($response, TRUE);

            if ($result['result_code'] == 1){
                $response_data = array(
                'status_code' => $result['result_code'],
                'campaign_id' => $result['id'],
                'campaign_name' => $request_data['campaign_name'],
                'message'=> $result['result_message'],
                );
                return response()->json($response_data, 201);
            }else{
                $response_data = array(
                    'status_code' => $result['result_code'],
                    'message'=> $result['result_message'],
                );
                return response()->json($response_data, 404);
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse($ex->getMessage(), 404);
        }
    
    }


    public function getCurl($api)
    {
        $request = curl_init($api); // initiate curl object
        curl_setopt($request, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); // Returns response data instead of TRUE(1)
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        $response = (string)curl_exec($request);
        curl_close($request);
        return $response;
    }

    
    public function postCurl($api, $data)
    {
        $request = curl_init($api);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($request,CURLOPT_MAXREDIRS, 10);
        curl_setopt($request, CURLOPT_TIMEOUT, 0);
        curl_setopt($request, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); // uncomment if you get no gateway response and are using HTTPS
        curl_setopt($request, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cookie: PHPSESSID=478ec09ab8d3d25f0a521b8a7f5f54ef; em_acp_globalauth_cookie=06444ea1-f7f1-4ecf-9de0-85dc8894aa88'
                ),
        );
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
        $response = curl_exec($request);
        curl_close($request);
        return $response;
    }

}