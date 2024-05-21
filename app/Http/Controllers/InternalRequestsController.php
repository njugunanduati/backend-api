<?php

namespace App\Http\Controllers;

use Validator;
use Notification;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\TrainingAccess;
use App\Notifications\NewUser;
use Illuminate\Validation\Rule;
use Illuminate\Notifications\Notifiable;
use App\Models\GroupCoachingLessonMeeting;
use App\Http\Resources\User as UserResource;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\TrainingAccess as TrainingResource;

class InternalRequestsController extends Controller
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addSalesFormMember(Request $request)
    {

        try {

            $rules = [
                'first_name' => 'required|string:max:100',
                'last_name' => 'required|string:max:100',
                'email' => 'required|string|email|unique:users',
                'role' => 'required',
                'das' => 'required',
                
            ];

            $messages = [
                'email.required' => 'The email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'The email address you entered already exist',
                'role.required' => 'The PAS Role is required',
                'das.required' => 'The DAS access flag is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            $role_name = $request->input('role');

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $password = (string)rand(1000000, 9999999);

            $user = new User;
            $user->first_name = trim($request->input('first_name'));
            $user->last_name = trim($request->input('last_name'));
            $user->email = trim($request->input('email'));
            $user->company = trim($request->input('company'));
            $user->onboarding = 0;
            $gcs_access = $request->input('gcs') == 'Yes' || false;
            $das_access = $request->input('das') == 'Yes' || false;

            $user->password = $password;

            switch ($role_name) {
            
                case 'Platinum':
                    $user->role_id = 8;
                    break;
                default:
                    $user->role_id = 4;
                    break;
            }

            if ($request->has('website')) {
                $user->website = trim($request->input('website'));
            }

            if ($request->has('owner')) {
                $user->owner = trim($request->input('owner'));
            }

            $role = Role::findOrfail($user->role_id);

            $user->save();
            $user->assignRole($role);
            $user->module_sets()->attach(5); // Attach profit jumpstart moduleset to the new user
            // $user->module_sets()->attach(6); // Attach breakthrough 40 moduleset to the new user
            $user->module_sets()->attach(7); // Attach jumpstart 40 moduleset to the new user
            $training_access = new TrainingAccess;
            $training_access->user_id = $user->id;
            if($gcs_access){
                $training_access->group_coaching = 1;
            }
            if($das_access){
                $user->module_sets()->attach([1,2]); // Attach DAS modules 
            }
            $training_access->save();

            $user->notify(new NewUser($user, $password));

            $transform = new UserResource($user);

            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }
}





