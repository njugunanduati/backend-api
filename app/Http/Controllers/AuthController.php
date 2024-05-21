<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Cache;
use DateTime;
use Mail;
use Validator;
use Exception;
use GuzzleHttp\Client;
use App\Helpers\Cypher;

use App\Mail\UserOtpEmail;
use App\Models\User;
use App\Models\LoginTracker;
use App\Models\LoginOtp;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\LoginOtpResource;
use App\Http\Resources\TrainingAccess as TrainingResource;
use App\Http\Resources\UserCalendarURL as CalendarResource;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;


use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'company' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed',
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()
                ->json([
                    'code' => 1,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
        }

        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->company = $request->company;
        $user->password = bcrypt($request->password);
        $user->created_by_id = (Auth::check()) ? Auth::user()->id :null;
        if ($request->has('website')) {
            $user->website = $request->website;
        }

        $user->save();
        $user->assignRole($request->role);
        $user->module_sets()->attach(5); // Attach quickcore moduleset to the new user

        return response()->json([
            'data' => $user,
            'message' => 'Successfully created user!'
        ], 201);
    }

    /**
     * Login first step for otp
     *
     * @param  [string] email
     * @param  [string] password
     */
    public function loginotp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'app' => 'required|string'
        ]);

        $credentials = request(['email', 'password']);
        $cypher = new Cypher;

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Invalid Credentials. Please try again', 400);
        }

        $user = $request->user();

        // Inactive user
        if (!$user->isActive()) {
            return $this->errorResponse('Sorry, your account is temporarily suspended', 400);
        }
        
        if ($user->role_id == 10 && $cypher->decryptID(env('HASHING_SALT'), $request->app) !== "student"){
            return $this->errorResponse('Sorry, you do not have access to this resource', 400);
        }

        $checkOtpStatus = $this->checkOTPStatus($user->id);

        $response = [
            "message" => "Credentials are correct.",
            "user_id" => $user->id,
            "user_role" => $user->role_id,
            "verified" => $cypher->encryptID($checkOtpStatus->verified, env('HASHING_SALT')),
        ];

        return $this->successResponse($response, 200);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Invalid Credentials. Please try again', 400);
        }

        $user = $request->user();

        // Active and role checks
        if (!$user->isActive()) {
            return $this->errorResponse('Sorry, your account is temporarily suspended', 400);
        }
        if ($user->role_id == 10) {
            return $this->errorResponse('Sorry, you do not have access to this resource', 400);
        }

        // Set token expiration based on 'remember_me' input
        $daysToAdd = ($request->input('remember_me') == 14) ? 14 : 30; // Default to 30 days
        $expiresAt = Carbon::now()->addDays($daysToAdd);

        $device_id = md5($_SERVER['HTTP_USER_AGENT']);//get device ID from current agent
        $dt = new DateTime();
        $date = $dt->format('Y-m-d H:i:s');

        if($request->has('remember_me')){
            LoginOtp::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $device_id,
                    
                ],
                [
                    'remember_me' => (int)$request['remember_me'],
                    'otp' => base64_decode($request['otp']),
                    'otp_date' => $date,
                ],
            );
        }
            
        // Token creation with consideration for 'remember_me' input
        $tokenResult = $user->createToken($request->email, ['pas:' . $user->role_id], $expiresAt);

        $lookup = ($request->input('lookup')) ? $request->input('lookup') : [];

        if (array_key_exists('ip', $lookup)) {

            $track = [
                'user_id' => $user->id,
                'browser' => ($request->input('browser')) ? $request->input('browser') : null,
                'ip' => (array_key_exists('ip', $lookup)) ? $lookup['ip'] : null,
                'city' => (array_key_exists('city', $lookup)) ? $lookup['city'] : null,
                'country_name' => (array_key_exists('country_name', $lookup)) ? $lookup['country_name'] : null,
                'timezone' => (array_key_exists('timezone', $lookup)) ? $lookup['timezone'] : null,
                'latitude' => (array_key_exists('latitude', $lookup)) ? $lookup['latitude'] : null,
                'longitude' => (array_key_exists('longitude', $lookup)) ? $lookup['longitude'] : null,
            ];

            LoginTracker::create($track);
        }

        $monthly = 0;
        $annual = 0;

        if (($user->monthly_income == null) || (strlen($user->monthly_income) == 0)) {
            $monthly = 0;
            $annual = 0;
        } elseif (($user->annual_income == null) || (strlen($user->annual_income) == 0)) {
            $monthly = (float) $user->monthly_income;
            $annual = (float) $user->monthly_income * 12;
        } else {
            $monthly = (float) $user->monthly_income;
            $annual = (float) $user->annual_income;
        }

        $data = [
            'access_token' => $tokenResult->plainTextToken,
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'company' => $user->company,
            'website' => ($user->website) ? $user->website : '',
            'role_id' => $user->role_id,
            'licensee' => $user->licensee_access,
            'tos_check' => $user->tos_check,
            'show_tour' => $user->show_tour,
            'meeting_url' => ($user->meeting_url) ? $user->meeting_url : '',
            'profile_pic' => ($user->profile_pic) ? $user->profile_pic : '',
            'title' => ($user->title) ? $user->title : '',
            'location' => ($user->location) ? $user->location : '',
            'time_zone' => ($user->time_zone) ? $user->time_zone : '',
            'phone_number' => ($user->phone_number) ? $user->phone_number : '',
            'birthday' => ($user->birthday) ? $user->birthday : '',
            'facebook' => ($user->facebook) ? $user->facebook : '',
            'twitter' => ($user->twitter) ? $user->twitter : '',
            'linkedin' => ($user->linkedin) ? $user->linkedin : '',
            'onboarding' => $user->onboarding,
            'trainings' => new TrainingResource($user->trainingAccess),
            'calendarurls' => isset($user->calendarurls) ? new CalendarResource($user->calendarurls) : null,
            'prospects_notify' => $user->prospects_notify,
            'module_sets' => $user->module_sets()->get(),
            'monthly_income' => $monthly,
            'annual_income' => $annual,
            'status' => $user->status,
        ];

        return $this->successResponse($data, 200);
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function studentlogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Invalid Credentials. Please try again', 400);
        }

        $user = $request->user();

        // Active and role checks
        if (!$user->isActive()) {
            return $this->errorResponse('Sorry, your account is temporarily suspended', 400);
        }

        // Set token expiration based on 'remember_me' input
        $daysToAdd = ($request->input('remember_me') == 14) ? 14 : 30; // Default to 30 days
        $expiresAt = Carbon::now()->addDays($daysToAdd);

        $device_id = md5($_SERVER['HTTP_USER_AGENT']);//get device ID from current agent
        $dt = new DateTime();
        $date = $dt->format('Y-m-d H:i:s');

        if($request->has('remember_me')){
            LoginOtp::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $device_id,
                    
                ],
                [
                    'remember_me' => (int)$request['remember_me'],
                    'otp' => base64_decode($request['otp']),
                    'otp_date' => $date,
                ],
            );
        }

        // Token creation with consideration for 'remember_me' input
        $tokenResult = $user->createToken($request->email, ['pas:' . $user->role_id], $expiresAt);

        $lookup = ($request->input('lookup')) ? $request->input('lookup') : [];

        if (array_key_exists('ip', $lookup)) {

            $track = [
                'user_id' => $user->id,
                'browser' => ($request->input('browser')) ? $request->input('browser') : null,
                'ip' => (array_key_exists('ip', $lookup)) ? $lookup['ip'] : null,
                'city' => (array_key_exists('city', $lookup)) ? $lookup['city'] : null,
                'country_name' => (array_key_exists('country_name', $lookup)) ? $lookup['country_name'] : null,
                'timezone' => (array_key_exists('timezone', $lookup)) ? $lookup['timezone'] : null,
                'latitude' => (array_key_exists('latitude', $lookup)) ? $lookup['latitude'] : null,
                'longitude' => (array_key_exists('longitude', $lookup)) ? $lookup['longitude'] : null,
            ];

            LoginTracker::create($track);
        }

        $data = [
            'access_token' => $tokenResult->plainTextToken,
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'company' => $user->company,
            'company_id' => $user->company_id,
            'website' => ($user->website) ? $user->website : '',
            'role_id' => $user->role_id,
            'profile_pic' => ($user->profile_pic) ? $user->profile_pic : '',
            'title' => ($user->title) ? $user->title : '',
            'location' => ($user->location) ? $user->location : '',
            'time_zone' => ($user->time_zone) ? $user->time_zone : '',
            'phone_number' => ($user->phone_number) ? $user->phone_number : '',
            'birthday' => ($user->birthday) ? $user->birthday : '',
            'facebook' => ($user->facebook) ? $user->facebook : '',
            'twitter' => ($user->twitter) ? $user->twitter : '',
            'linkedin' => ($user->linkedin) ? $user->linkedin : '',
            'trainings' => new TrainingResource($user->trainingAccess),
            'status' => $user->status,
        ];

        return $this->successResponse($data, 200);
    }

    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 7560
        ];
    }
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {

        try {
            // Track the logout time when user ends session 
            $user = $request->user();
            $user->last_login = now();
            $user->save();

            // Revoke the token that was used to authenticate the current request...
            $request->user()->currentAccessToken()->delete();

            // Alternatively, if you want to ensure a complete logout from all devices/sessions:
            // $request->user()->tokens()->delete();

            return $this->successResponse(['logout' => true], 200);
        } catch (TokenExpiredException $e) {
            return $this->singleMessage(['Token expired'], 200);
        }
    }


    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function verifyToken(Request $request)
    {

        try {
            $request->validate([
                'token' => 'required|string',
            ]);
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->errorResponse('Invalid token. Please try again', 400);
            }
        } catch (TokenExpiredException $e) {

            return $this->errorResponse('Invalid token. Please try again', 403);
        } catch (TokenInvalidException $e) {

            return $this->errorResponse('Invalid token. Please try again', 403);
        } catch (JWTException $e) {

            return $this->errorResponse('Please provide a token.', 400);
        }
        // the token is valid and we have found the user via the sub claim      
        return $this->successResponse(compact('user'), 200);
    }

    public function mockSanctumRequest(Request $request)
    {

        try {

            $user = $request->user();
        } catch (Exception $e) {

            return $this->errorResponse('Error occured. Please try again', 403);
        }

        // the token is valid and we have found the user via the sub claim      
        return $this->successResponse(['authenticated' => true], 200);
    }
    public function generateOTP(Request $request)
    {
        try {
            $data = $request->all();
            $user_id = base64_decode($data['user_id']);
            $user = User::find((int) $user_id);
            $device_id = md5($_SERVER['HTTP_USER_AGENT']);
            if ($user) {
                $mCode = $this->randomGen();
            } else {
                return $this->errorResponse('User does not exist. Please try again', 400);
            }
            $otp_code = $mCode;
            $dt = new DateTime();
            $date = $dt->format('Y-m-d H:i:s');

            LoginOtp::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'device_id'=> $device_id,
                ],
                [
                    'otp' => $otp_code,
                    'otp_date' => $date,
                ],
            );
            $login_otp = LoginOtp::where('user_id', '=', $user_id)->first();

            $notice = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'to' => trim($user->email),
                'from' => env('mail_from_address', 'pas@focused.com'),
                'otp' => $login_otp->otp,
                'subject' => 'Your OTP from Profit Acceleration Software',
            ];

            Mail::to($user->email)->send(new UserOtpEmail($notice));

            $transform = new LoginOtpResource($login_otp);

            return $this->successResponse($transform, 200);
        } catch (Exception $e) {
            return $this->errorResponse('Error occured. Please try again:  '.$e->getMessage(), 403);
        }
    }

    public function randomGen()
    {
        do {
            $num = sprintf('%06d', mt_rand(111, 999989));
        } while (preg_match("~^(\d)\\1\\1\\1|(\d)\\2\\2\\2$|0000~", $num));
        return $num;
    }

    public function verifyOTP($otp, $user_id)
    {
        try {
            $token = base64_decode($otp);
            $allowed_minutes = 5;
            $login_otp = LoginOtp::where('user_id', '=', $user_id)->first();
            $date = new DateTime($login_otp->otp_date);
            $now = new DateTime();
            $difference = $date->diff($now)->format("%i");
            if ((int) $token === (int) $login_otp->otp) {
                if ((int) $difference <= (int) $allowed_minutes) {
                    return (object) ['verified' => true, 'message' => 'Token is verified'];
                } else {
                    return (object) ['verified' => false, 'message' => 'Token is expired'];
                }
            } else {
                return (object) ['verified' => false, 'message' => 'Token is invalid'];
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured', 400);
        }
    }

    public function checkOTPStatus($user_id)
    {
        try {
            $device_id = md5($_SERVER['HTTP_USER_AGENT']);
            $login_otp = LoginOtp::where('user_id', $user_id)->where('device_id', $device_id)->first();
            if ($login_otp) {
                if (is_null($login_otp->remember_me) || is_null($login_otp->device_id)) {
                    return (object) ['verified' => 'false', 'message' => 'Otp has expired'];
                }
                if ($login_otp->device_id !== $device_id) {
                    return (object) ['verified' => 'false', 'message' => 'Otp has expired'];
                }
                $allowed_days = $login_otp->remember_me;
                $date = Carbon::createFromDate($login_otp->otp_date)->format('Y-m-d H:i:s');
                $now = Carbon::now();
                $difference = $now->diffInDays($date);
                if ((int) $difference <= (int) $allowed_days) {
                    return (object) ['verified' => 'true', 'message' => 'Otp is valid'];
                } else {
                    return (object) ['verified' => 'false', 'message' => 'Otp has expired'];
                }
            } else {
                return (object) ['verified' => 'false', 'message' => 'No Otp found'];
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured', 400);
        }
    }

    public function verifyRecaptchaToken(Request $request)
    {
        try {
            if ($request->has('token')) {

                $token = $request->token;
                $response = $this->validateRecaptcha($token);
                if ($response->success) {

                    return $this->successResponse($response, 200);

                }
                return $this->errorResponse('Verification failed. Please try again', 400);
            }
        } catch (Exception $ex) {
            return $this->errorResponse($ex->getMessage(), 400);
        }
    }

    public function validateRecaptcha($token)
    {
        $client = new Client;
        $response = $client->post(
            config('services.recaptcha.site'),
            [
                'form_params' =>
                [
                    'secret' => config('services.recaptcha.secret'),
                    'response' => $token
                ]
            ]
        );
        $body = json_decode((string) $response->getBody());
        return $body;
    }
}