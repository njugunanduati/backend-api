<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\AssessmentTrail;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\AssessmentTrail as TrailResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class AssessmentTrailController extends Controller
{
    use ApiResponses;
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {

        $trails = AssessmentTrail::all();//Get all trails

        $transform = TrailResource::collection($trails);

        return $this->successResponse($transform,200);
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

        // 

    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {

        // 
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

        $validator = Validator::make($request->all(),[
            'path' => 'required|string',
            'module_name' => 'required|string',
            'trail' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        try {
            $assessment = Assessment::findOrFail($id);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That assessment does not exist', 404);
        }

        $trail = new AssessmentTrail;

        // If there is an existing record, use that instead of a new one
        if ($trail->where('assessment_id', $id)->where('path', $request->input('path'))->count()) {
            $trail = $trail->where('assessment_id', $id)->where('path', $request->input('path'))->first();
        }

        $trail->assessment_id = $id;
        $trail->path = $request->input('path');
        $trail->module_name = $request->input('module_name');
        $trail->trail = $request->input('trail');
        $trail->save();
        
        $transform = new TrailResource($trail);
        return $this->showMessage($transform, 200);

      }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id) {

        try
        {
            $trail = AssessmentTrail::findOrFail($id);
            $trail->delete();
            return $this->singleMessage('Assessment trail deleted' ,201);

        }catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Assessment trail not found', 400);
        }

    }
}
