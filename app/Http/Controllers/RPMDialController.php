<?php

namespace App\Http\Controllers;

use Validator;
use App\Helpers\Cypher;
use App\Models\RpmDial;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\RpmDialResponse as RpmDialResponseResource;

class RPMDialController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $responses = RpmDial::all(); //Get all responses

        $transform = RpmDialResponseResource::collection($responses);

        return $this->successResponse($transform, 200);
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
        try {
            $cypher = new Cypher;
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id'));

            $validator = Validator::make([
                'q1' => $request->input('q1'),
                'q2' => $request->input('q2'),
                'q3' => $request->input('q3'),
                'q4' => $request->input('q4'),
                'q5' => $request->input('q5'),
                'q6' => $request->input('q6'),
                'assessment_id' => $assessment_id,
                'success_factor' => $request->input('success_factor')
            ], [
                'assessment_id' => 'required|exists:assessments,id',
                'q1' => 'required',
                'q2' => 'required',
                'q4' => 'required',
                'q5' => 'required',
                'q6' => 'required',
                'success_factor' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $array = [
                'q1' => $request->input('q1'),
                'q2' => $request->input('q2'),
                'q3' => $request->input('q3'),
                'q4' => $request->input('q4'),
                'q5' => $request->input('q5'),
                'q6' => $request->input('q6'),
                'assessment_id' => $assessment_id,
                'success_factor' => $request->input('success_factor')
            ];

            $response = RpmDial::updateOrCreate(['assessment_id' => $assessment_id], $array);
            
            $transform = new RpmDialResponseResource($response);

            return $this->showMessage($transform, 201);
        } // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Response not found', 400);
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
        try {
            $response = RpmDial::where('assessment_id', '=', $id)->firstOrFail();
            
            $transform = new RpmDialResponseResource($response);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Response does not exist', 200);
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
        //
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
        try {

            $validator = Validator::make($request->all(), [
                'assessment_id' => 'required|exists:assessments,id',
                'q1' => 'required',
                'q2' => 'required',
                'q4' => 'required',
                'q5' => 'required',
                'q6' => 'required',
                'success_factor' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $response = RpmDial::findOrFail($id);


            $response->update([
                'assessment_id' => $request->input('assessment_id'),
                'q1' => $request->input('q1'),
                'q2' => $request->input('q2'),
                'q3' => $request->input('q3'),
                'q4' => $request->input('q4'),
                'q5' => $request->input('q5'),
                'q6' => $request->input('q6'),
                'success_factor' => $request->input('success_factor')
            ]);


            $transform = new RpmDialResponseResource($response);

            return $this->successResponse($transform, 200);
        } catch (Exception $ex) {

            return $this->errorResponse('Response not found', 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $response = RpmDial::findOrFail($id);

            $response->delete(); //Del the Response
            return $this->singleMessage('Response Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {

                return $this->errorResponse('Response not found', 400);
        }

        }
    }
}
