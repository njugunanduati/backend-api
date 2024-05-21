<?php

namespace App\Http\Controllers;

use Auth;
use Validator;
use App\Models\LoginTracker;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\LoginTracker as TrackerResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LoginTrackerController extends Controller
{
    use ApiResponses;

    protected $user;

    public function __construct() {
        // $this->middleware(['isAuthorized']); //isAdmin middleware lets only users with a //specific permission permission to access these resources
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {
        
        $responses = LoginTracker::all()->sortByDesc('id');

        $transform = TrackerResource::collection($responses);

        return $this->successResponse($transform, 200);
    }

    /**
    * Display a listing of the resource.
    * Filtered by year and month
    *
    * @return \Illuminate\Http\Response
    */
    public function monthly(Request $request) {

        $validator = Validator::make($request->all(), [
            'year' => 'required|string',
            'month' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $year = $request->input('year');
        $month = $request->input('month');

        $responses = LoginTracker::whereYear('created_at', '=', $year)->whereMonth('created_at', '=', $month)->get()->sortByDesc('id');

        $transform = TrackerResource::collection($responses);

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
        // 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        // 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        // 
    }
}
