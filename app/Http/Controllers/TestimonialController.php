<?php

namespace App\Http\Controllers;

use Mail;
use Validator;
use Carbon\Carbon;
use App\Models\Testimonial;
use App\Models\Referral;
use App\Models\User;
use App\Mail\ReferralClient;
use App\Mail\ReferralCoach;
use App\Mail\SendTestimonial;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\TestimonialResource;
use App\Http\Resources\ReferralResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TestimonialController extends Controller
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
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addTestimonial(Request $request)
    {
        try {

            $rules = [
                'client' => 'required|exists:users,id',
                'coach' => 'required|exists:users,id',
                'rating' => 'required',
            ];

            $messages = [
                'client.required' => 'Client is required',
                'client.exists' => 'Client Not Found',
                'coach.required' => 'Coach is required',
                'coach.exists' => 'Coach Not Found',
                'rating.required' => 'Rating is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);


            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $client_id = $request->client;
            $coach_id = $request->coach;
            $rating = $request->rating;
            $msg = trim($request->testimonial);

            $coach = User::whereId($coach_id)->first();
            $client = User::whereId($client_id)->first();

            // Notify coach about the testimonial
            Mail::to($coach->email)->send(new SendTestimonial($coach, $client, $msg, $rating));

            $testimonial = new Testimonial;
            $testimonial->client = $client_id;
            $testimonial->coach = $coach_id;
            $testimonial->rating = $rating;
            $testimonial->testimonial = $msg;
            $testimonial->save();

            $transform = new TestimonialResource($testimonial);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }

    /**
     * Update resource and display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addReferral(Request $request)
    {
        try {
            $rules = [
                'referred_to' => 'required|exists:users,id',
                'referred_by' => 'required|exists:users,id',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'phone_number' => 'required',
            ];

            $messages = [
                'referred_to.required' => 'Coach details are required',
                'referred_to.exists' => 'Coach Not Found',
                'referred_by.required' => 'Client details are required',
                'referred_by.exists' => 'Client Not Found',
                'first_name.required' => 'Rating is required',
                'last_name.required' => 'Rating is required',
                'email.required' => 'Email is required',
                'phone_number.required' => 'Phone number is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);


            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $referral = new Referral;
            $referral->first_name = $request->input('first_name');
            $referral->last_name = $request->input('last_name');
            $referral->email = $request->input('email');
            $referral->phone_number = $request->input('phone_number');
            $referral->referred_to = $request->input('referred_to');
            $referral->referred_by = $request->input('referred_by');
            $referral->save();

            $transform = new ReferralResource($referral);

            $coach_details = User::whereId($referral->referred_to)->first();
            $client_details = User::whereId($referral->referred_by)->first();

            // send emails for the referrals
            Mail::to($coach_details->email)->send(new ReferralCoach($coach_details, $referral, $client_details));
            Mail::to($client_details->email)->send(new ReferralClient($coach_details, $referral, $client_details));

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Error saving referral', 404);
        }
    }




}
