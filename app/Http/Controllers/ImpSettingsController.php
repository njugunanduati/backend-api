<?php

namespace App\Http\Controllers;

use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Helpers\Cypher;
use App\Traits\ApiResponses;
use App\Models\ImpSettings;
use App\Http\Resources\ImpSettingsResource as SettingsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ImpSettingsController extends Controller {

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
    public function index() {
        $settings = ImpSettings::all();//Get all Implementation settings

        $transform = SettingsResource::collection($settings);

        return $this->successResponse($transform,200);

    }

    /**
     * Search for implementation coaching by query 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
            ];

            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id')));
            $validator = Validator::make(['assessment_id' => $assessment_id], $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            
            $settings = ImpSettings::where('assessment_id', $assessment_id)->get();

            $transform = SettingsResource::collection($settings);

            return $this->successResponse($transform, 200);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation coaching not found', 400);
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

        try {

            $rules = [
                'assessment_id' => 'required|exists:assessments,id',
                'three_days' => 'required',
                'one_day' => 'required',
                'one_hour' => 'required',
                'meeting_type' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'Assessment ID is required',
                'assessment_id.exists' => 'Assessment Not Found',
                'three_days.required' => 'Three days status is required',
                'one_day.required' => 'One day status is required',
                'one_hour.required' => 'One hour status is required',
                'meeting_type.required' => 'Meeting type is required',
            ];
            $data = $request->all();
            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id')));
            $data['assessment_id']=$assessment_id;
            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $array = [
                'three_days' => (int)$request->input('three_days'),
                'one_day' => (int)$request->input('one_day'),
                'one_hour' => (int)$request->input('one_hour'),
                'meeting_sequence' => (int)$request->input('meeting_sequence'),
                'meeting_type' => (int)$request->input('meeting_type'),
            ];

            if($request->input('zoom_url')){
               $array['zoom_url'] = $request->input('zoom_url');
            }

            if($request->input('phone_number')){
               $array['phone_number'] = $request->input('phone_number');
            }

            if($request->input('meeting_address')){
               $array['meeting_address'] = $request->input('meeting_address');
            }

            $settings = ImpSettings::updateOrCreate([
                'assessment_id' => intval($data['assessment_id']),
            ], $array);

            if ($settings->meetings) {
                $settings->meetings()->delete();// Delete all meetings attached
            }

            if($request->input('meetings')){
                foreach ($request->input('meetings') as $key => $value) {
                    $inputs = [
                        'settings_id' => $settings->id, 
                        'meeting_day' => $value['meeting_day'],
                        'meeting_time' => $value['meeting_time'],
                        'time_zone' => $value['time_zone']
                    ];
                    $settings->meetings()->create($inputs);
                }
            }
            
            $transform = new SettingsResource($settings);

            return $this->successResponse($transform, 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('Implementation settings not found', 400);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        try {
            $response = ImpSettings::findOrFail($id);

            $transform = new SettingsResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That Implementation Coaching does not exist', 200);
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

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        try{
            $settings = ImpSettings::findOrFail($id);

            if ($settings->meetings) {
                $settings->meetings()->delete();// Delete all meetings attached
            }

            $settings->delete();

            return $this->singleMessage('Implementation settings Deleted' ,201);

        }catch (ModelNotFoundException $ex) {
           return $this->errorResponse('Implementation settings record does not exist', 404);
        }

    }
}
