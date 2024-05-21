<?php

namespace App\Http\Controllers;

use Validator;
use App\Helpers\Cypher;
use App\Models\IncreasePricesExtra;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\PricesExtraResponse as ExtraResource;

class IncreasePricesExtraController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // 
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

            $validator = Validator::make($request->all(), [
                'current_customer_number' => 'required',
                'leaving_customer_number' => 'required',
                'may_happen' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            
            $assessment_id = $cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id'));

            $array = [
                'assessment_id' => $assessment_id,
                'current_customer_number' => $request->input('current_customer_number'),
                'leaving_customer_number' => $request->input('leaving_customer_number'),
                'may_happen' => $request->input('may_happen')
            ];

            $response = IncreasePricesExtra::updateOrCreate(['assessment_id' => $request->input('assessment_id')], $array);
            
            $transform = new ExtraResource($response);

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

