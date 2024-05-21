<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\User;
use App\Models\Prospect;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\Prospect as ProspectResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ProspectController extends Controller
{
    use ApiResponses;
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {

        $prospects = Prospect::all()->sortByDesc('id');//Get all prospects

        $transform = ProspectResource::collection($prospects);

        return $this->successResponse($transform,200);
    }

    
    /**
     * Search for prospects by user_id 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
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

            $user_id = intval($request->input('user_id'));
            
            $prospects = Prospect::where('user_id', $user_id)->get()->sortByDesc('id');

            $transform = ProspectResource::collection($prospects);
            
            return $this->successResponse($transform, 200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Prospects not found', 400);
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
    * Send Notifications
    *
    * @return \Illuminate\Http\Response
    */
    public function notifications($name, $user_id) {
        $user = User::findOrfail($user_id);
        sendProspectsRequestNotificationToCoach($user, $name);
        if (env('APP_ENV') != 'local') {
            sendProspectsRequestNotificationToAdmin($user, $name);
        }
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request) {

        try
        {
            
            $rules = [
                'user_id' => 'required|exists:users,id',
                'name' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
                'name.required' => 'Prospect list name is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $prospect = new Prospect;
            $prospect->user_id = $request->input('user_id');
            $prospect->name = $request->input('name');
            
            $prospect->save();

            $this->notifications($request->input('name'), $request->input('user_id'));

            $transform = new ProspectResource($prospect);

            return $this->showMessage($transform, 201);

        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Something went wrong', 400);
        }



    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {

        try
        {
            $prospect = Prospect::findOrfail($id);//Get by id
            $transform = new ProspectResource($prospect);
            return $this->successResponse($transform,200);
        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Prospect not found', 400);
        }
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

        try{
            
            $rules = [
                'user_id' => 'required|exists:users,id',
                'prospects' => 'required|file|mimes:csv,xlsx',
                'processed' => 'required',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User Not Found',
                'prospects.required' => 'Prospects list file is required',
                'prospects.mimes' => 'Prospects list must be an excel file either csv or xlsx',
                'processed.required' => 'Prospects status is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $url = '';
            $key = '';

            $prospect = Prospect::findOrFail($id);

            if($request->hasFile('prospects')){
                $file =  $request->file('prospects');
                $s3 = \AWS::createClient('s3');
                $key = date('mdYhia').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
                $url = $s3->putObject([
                    'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                    'Key'        => 'prospects/'.$key,
                    'ACL'          => 'public-read',
                    'ContentType' => $file->getMimeType(),
                    'SourceFile' => $file,
                ]);
                $url = $url->get('ObjectURL');
            }

            $prospect->url = $url;
            $prospect->key = $key;
            $prospect->processed = (int)$request->input('processed');

            if($prospect->isDirty()){
                $prospect->save();
            }

            $user_id = intval($request->input('user_id'));
            
            $prospects = Prospect::where('user_id', $user_id)->get()->sortByDesc('id');

            $user = User::findOrfail($user_id);
            sendProspectsNotificationToCoach($user);

            $user->prospects_notify = 1;
            $user->save();

            $transform = ProspectResource::collection($prospects);
            
            return $this->successResponse($transform, 200);

        }catch(ModelNotFoundException $e){
            return $this->errorResponse('That prospect not found', 400);
        }

      }

    /**
    * Delete File from AWS S3 bucket.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function fileDelete($key) {
        
        $s3 = \AWS::createClient('s3');
        
        $result = $s3->deleteObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'prospects/'.$key,
        ]);
        
        if ($result['DeleteMarker']){
            return true;
        } else {
            return false;
        }
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy(Request $request, $id) {

        try
        {
            $prospect = Prospect::findOrFail($id);
            
            if($prospect->key){
                $this->fileDelete($prospect->key);
            }
            
            $prospect->delete();

            $user_id = intval($request->input('user_id'));
            
            $prospects = Prospect::where('user_id', $user_id)->get()->sortByDesc('id');

            $transform = ProspectResource::collection($prospects);
            
            return $this->successResponse($transform, 200);

        }catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Prospect not found', 400);
        }

    }
}
