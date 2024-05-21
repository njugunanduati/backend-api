<?php

namespace App\Http\Controllers;

use Validator;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\TrainingAccess;
use App\Models\MeetingNoteSetting;
use App\Notifications\NewStudent;
use App\Models\Role;
use App\Models\User;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\Session as SessionResource;
use App\Http\Resources\MeetingNote as NoteResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
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
     * Method to create new user entries into the  
     * training user table for all the students with group coaching enabled.
     * 
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {

            $role_id = 10;

            $users = User::whereRoleId($role_id)->get();

            foreach ($users as $value) {
                TrainingAccess::updateOrCreate(
                    ['user_id' =>  $value->id,
                    'training_software' => 0,	
                    'training_100k' => 0,
                    'training_lead_gen' => 0,	
                    'group_coaching' => 1],
                    ['group_coaching' => 1]
                );
            }
            return $this->successResponse('All current students have been given group-coaching access', 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This user does not exist', 404);
        }
    }

    public function createCompanyUser()
    {

        try {

            // All the companies that dont have user/student accounts
            // SELECT c.id FROM companies c WHERE c.contact_email NOT IN (SELECT u.email FROM users u);
            $companies = DB::table('companies')
            ->whereNotIn('contact_email', function ($query) { $query->select('email')->from('users');})
            ->select('companies.*')
            ->get();

            $role = Role::findOrFail(10);
            
            foreach ($companies as $key => $value) {

                User::where('email', trim($value->contact_email))->firstOr(function () use ($value, $role) {
                    $password = (string)rand(1000000, 9999999);
                    $nuser = new User;
                    $nuser->email = $value->contact_email;
                    $nuser->first_name = $value->contact_first_name;
                    $nuser->last_name = $value->company_name;
                    $nuser->company = $value->company_name;
                    $nuser->website = $value->company_website;
                    $nuser->password = $password;
                    $nuser->role_id = 10;	
                    $nuser->company_id = $value->id;
                    
                    $nuser->save();

                    $nuser->assignRole($role);
                    $nuser->companies()->attach($value->id);

                    // $nuser->notify(new NewStudent($nuser, $password));

                    $access = new TrainingAccess;
                    $access->user_id = $nuser->id;
                    $access->training_software = 0;
                    $access->training_100k = 0;
                    $access->training_lead_gen = 0;
                    $access->group_coaching = 0;
                    $access->save();

                });
            }
            
            return $this->successResponse('Company users created successfully', 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }

    
    public function createUserCompanyRelation()
    {

        try {

            // All the users that have a company
            $users = User::whereNotNull('company_id')->get();
            
            foreach ($users as $key => $value) {

                CompanyUser::where('user_id', $value->id)->where('company_id', $value->company_id)->firstOr(function () use ($value) {
                    $cu = new CompanyUser;
                    $cu->user_id = $value->id;
                    $cu->company_id = $value->company_id;
                    $cu->save();
                });
                
            }
            
            return $this->successResponse('Company users created successfully', 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }

    
    public function createTrainingAccess()
    {

        try {

            // All the companies that dont have user/student accounts
            // SELECT u.id FROM users u WHERE u.id NOT IN(SELECT user_id FROM training_user);
            $users = DB::table('users')
            ->whereNotIn('id', function ($query) { $query->select('user_id')->from('training_user');})
            ->select('users.id')
            ->get();
            
            foreach ($users as $key => $value) {

                TrainingAccess::firstOrCreate(
                        [
                            'user_id' => $value->id,
                            'training_software' => 0,	
                            'training_100k' => 0,
                            'training_lead_gen' => 0,	
                            'group_coaching' => 0
                        ]);
            }

            return $this->successResponse('Training access records have been created successfully', 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }

    public function createProfitMetrics()
    {

        try {


            $settings = MeetingNoteSetting::groupBy('company_id')->orderBy('company_id')->get();

            $company_ids = $settings->pluck('company_id')->all();

            foreach ($company_ids as $key => $value) {
                MeetingNoteSetting::firstOrCreate(
                        [
                            'company_id' => $value,
                            'type' => 'bottom',	
                            'name' => 'profits',
                            'label' => 'Weekly profits:',	
                            'placeholder' => 'Enter profits:',
                        ]);
            }

            return $this->successResponse('Profits metrics have been created successfully', 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This note does not exist', 404);
        }
    }
}