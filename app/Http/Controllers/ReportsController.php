<?php

namespace App\Http\Controllers;

use Mail;
use Validator;
use App\Jobs\ProcessEmail;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function sendMail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'messages' => 'required|string',
                'subject' => 'required|string',
                'to' => 'required|email',
                'cc' => 'nullable|string',
                ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $copy = [];

            if($request->cc){
               $cleancc = str_replace(' ', '', $request->cc);
               $copy = explode(',' ,trim($cleancc));
            }

            $details = [
                'user' => $this->user,
                'to' => trim($request->to), 
                'messages' => [$request->message, '2 Second line', '3 Third line', '4 Fourth line'],
                'subject' => $request->subject, 
                'copy' => $copy,
                'bcopy' => [],
            ];
            
            ProcessEmail::dispatch($details);

            return $this->showMessage('Your Email Was sent Successfully', 200);


        } catch(Exception $e){

            return $this->errorResponse('Error occured while trying to send email', 400);

        }
    }
}
