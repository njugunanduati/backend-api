<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\Currency as CurrencyResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class CurrencyController extends Controller
{
    use ApiResponses;
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index() {

        $currencies = Currency::all();//Get all currencies

        $transform = CurrencyResource::collection($currencies);

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

        try
        {
            $validator = Validator::make($request->all(),[
                'name' => 'required|unique:currency,name',
                'code' => 'required|string',
                'symbol' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $currency = new Currency;
            $currency->name = $request->input('name');
            $currency->code = $request->input('code');
            $currency->symbol = $request->input('symbol');

            $currency->save();

            $transform = new CurrencyResource($currency);

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
            $currency = Currency::findOrfail($id);//Get by id
            $transform = new CurrencyResource($currency);
            return $this->successResponse($transform,200);
        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Currency not found', 400);
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

        try
        {
            $validator = Validator::make($request->all(),[
                'name' => 'required|unique:currency,name',
                'code' => 'required|string',
                'symbol' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $currency = Currency::findOrFail($id);
            $currency->name = trim($request->input('name'));
            $currency->code = $request->input('code');
            $currency->symbol = $request->input('symbol');

            if($currency->isDirty()){
                $currency->save();
            }
            $transform = new CurrencyResource($currency);
            return $this->showMessage($transform, 200);

        }
        // catch(Exception $e) catch any exception
        catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('That currency not found', 400);
        }

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
            $currency = Currency::findOrFail($id);
            $currency->delete();
            return $this->singleMessage('Currency Deleted' ,201);

        }catch(ModelNotFoundException $e)
        {
            return $this->errorResponse('Currency not found', 400);
        }

    }
}
