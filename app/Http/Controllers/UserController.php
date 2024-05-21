<?php

namespace App\Http\Controllers;

use App\Models\UserStatus;
use Validator;
use Notification;
Use Image;
use Http;
// use cypher
use App\Helpers\Cypher;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Group;
use App\Http\Requests;
use App\Models\Company;
use App\Models\Lesson;
use App\Models\MailBox;
use App\Models\Payment;
use App\Models\ModuleSet;
use App\Models\UserGroup;
use App\Jobs\ProcessEmail;
use App\Models\Assessment;
use App\Models\Permission;
use App\Models\GroupLesson;
use App\Models\Integration;
use App\Models\UserCalendarURL;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\TrainingAccess;
use App\Notifications\NewUser;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\AweberIntegration;
use App\Models\CustomGroupLesson;
use App\Models\MemberGroupLesson;
use App\Models\StripeIntegration;
use App\Models\UserGroupTemplate;
use App\Models\UserChangesMetaData;
use App\Models\UserModuleAccessMetaData;
use App\Models\UserPasswordChangesMetaData;
use App\Models\UserCancelationMetaData;
use App\Notifications\RemindUser;
use App\Notifications\NotifyAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

use Illuminate\Notifications\Notifiable;
use App\Models\GroupCoachingLessonMeeting;
use App\Http\Resources\User as UserResource;
use App\Http\Resources\UserMini as UserMiniResource;
use App\Http\Resources\UserModuleAccessMetaDataResource;
use App\Http\Resources\UserChangesMetaDataResource;
use App\Http\Resources\UserPasswordChangesMetaDataResource;

use App\Http\Resources\Coach as CoachResource;
use App\Models\GroupCoachingLessonMeetingSetting;
use App\Http\Resources\Payment as PaymentResource;
use App\Http\Resources\UserDownload as UserDownload;
use App\Http\Resources\UserGroup as UserGroupResource;
use App\Http\Resources\NewUserGroup as NewUserGroupResource;

use App\Http\Resources\Assessment as AssessmentResource;
use App\Http\Resources\SimpleUser as SimpleUserResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\TrainingAccess as TrainingResource;
use App\Models\CustomQuestion;
use App\Models\UserSimulator;


use Log;
class UserController extends Controller
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
        $users = User::all(); //Get all users

        $transform = UserResource::collection($users);

        return $this->successResponse($transform, 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateIncome(Request $request)
    {
        try {

             $rules = [
                'type' => 'required',
                'amount' => 'required',
                'id' => 'required|exists:users,id',
            ];

            $messages = [
                'id.required' => 'User ID is required',
                'id.exists' => 'User Not Found',
                'type.required' => 'Type is required',
                'amount.required' => 'Amount is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $id = $request->id;
            $type = trim($request->type);
            $amount = trim($request->amount);

            $user = User::findOrfail($id);

            if($type == 'monthly'){
                $user->monthly_income = $amount;
            }elseif($type == 'annual'){
                $user->annual_income = $amount;
            }
            
            $user->save();

            $user = $user->refresh();

            if(($user->monthly_income == null) || (strlen($user->monthly_income) == 0)){
                $data = [
                    'id' => $user->id,
                    'monthly_income' => 0,
                    'annual_income' => 0,
                ];
            }elseif (($user->annual_income == null) || (strlen($user->annual_income) == 0)) {
               $data = [
                    'id' => $user->id,
                    'monthly_income' => (float)$user->monthly_income,
                    'annual_income' => (float)$user->monthly_income * 12,
                ];
            }else{
               $data = [
                    'id' => $user->id,
                    'monthly_income' => (float)$user->monthly_income,
                    'annual_income' => (float)$user->annual_income,
                ]; 
            }

            return $this->successResponse($data, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This user does not exist', 404);
        }
    }




    /**
     * List all coaches 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function coachList(Request $request)
    {

        try {

            $rules = [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ];

            $messages = [
                'email.required' => 'Email is required',
                'email.email' => 'That is not a valid email address',
                'password.required' => 'End date is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $credentials = request(['email', 'password']);

            if (!$token = auth()->attempt($credentials)) {
                return $this->errorResponse('Invalid Credentials. Please try again', 401);
            }

            $user = $request->user();

            // Inactive user
            if ($user->role_id == 5) {
                return $this->errorResponse('Sorry, your account is temporarily suspended', 401);
            }

            // Only super admin can user endpoint to login
            if ($user->role_id != 1) {
                return $this->errorResponse('Sorry, you do not have access to this resource', 401);
            }

            $users = User::all(); //Get all users but students

            $transform = CoachResource::collection($users);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Search for users by query and list 20 records
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function search(Request $request)
    {
        try {

            $query = $request->input('query');
            $last_id = $request->input('last');

            if (empty($query)) {
                if (empty($last_id)) {
                    $users = User::where('id', '>', 1)->orderBy('id')->limit(20)->get();
                } else {
                    $users = User::where('id', '>', $last_id)->orderBy('id')->limit(20)->get();
                }
            } else {
                if (empty($last_id)) {
                    $users = User::where('id', '>', 1)
                        ->where(function ($q) use ($query) {
                            $q->where('first_name', 'LIKE', '%' . $query . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $query . '%')
                                ->orWhere('email', 'LIKE', $query)
                                ->orWhere('company', 'LIKE', $query);
                        })->orderBy('id')->limit(20)->get();
                } else {
                    $users = User::where('id', '>', $last_id)
                        ->where(function ($q) use ($query) {
                            $q->where('first_name', 'LIKE', '%' . $query . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $query . '%')
                                ->orWhere('email', 'LIKE', $query)
                                ->orWhere('company', 'LIKE', $query);
                        })->orderBy('id')->limit(20)->get();
                }
            }
            $transform = UserResource::collection($users);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    public function searchCoachUsers(Request $request)
    {
        try {

            $query = $request->input('query');
            $user_id = $request->input('user_id');
            $user_group_id = $request->input('user_group_id');

            $current = DB::table('member_group_lesson')
                ->select('member_group_lesson.user_id')
                ->where('member_group_lesson.group_id', $user_group_id)
                ->where('member_group_lesson.invited_by', $user_id)
                ->groupBy('member_group_lesson.user_id')->orderBy('member_group_lesson.user_id')->get();

            $ids = [];

            foreach ($current as $key => $u) {
                $ids[] = $u->user_id;
            }

            if (empty($query)) {

                $users = DB::table('users')
                    ->join('member_group_lesson', 'users.id', '=', 'member_group_lesson.user_id')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'users.website', 'users.profile_pic')
                    ->where(function ($a) use ($user_id) {
                        $a->where('member_group_lesson.invited_by', $user_id);
                    })->where(function ($b) use ($user_group_id) {
                        $b->where('member_group_lesson.group_id', '!=', $user_group_id);
                    })->where(function ($c) {
                        $c->where('member_group_lesson.user_id', '!=', DB::raw('member_group_lesson.invited_by')); // drop off the coach
                    })->where(function ($d) use ($ids) {
                        $d->whereNotIn('member_group_lesson.user_id', $ids);
                    })->groupBy('member_group_lesson.user_id')->orderBy('member_group_lesson.user_id')->get();
            } else {

                $users = DB::table('users')
                    ->join('member_group_lesson', 'users.id', '=', 'member_group_lesson.user_id')
                    ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.role_id', 'users.website', 'users.profile_pic')
                    ->where(function ($a) use ($query) {
                        $a->where('users.first_name', 'LIKE', '%' . $query . '%')->orWhere('users.last_name', 'LIKE', '%' . $query . '%');
                    })->where(function ($b) use ($user_id) {
                        $b->where('member_group_lesson.invited_by', $user_id);
                    })->where(function ($c) use ($user_group_id) {
                        $c->where('member_group_lesson.group_id', '!=', $user_group_id);
                    })->where(function ($d) {
                        $d->where('member_group_lesson.user_id', '!=', DB::raw('member_group_lesson.invited_by')); // drop off the coach
                    })->where(function ($e) use ($ids) {
                        $e->whereNotIn('member_group_lesson.user_id', $ids);
                    })->groupBy('member_group_lesson.user_id')->orderBy('member_group_lesson.user_id')->get();
            }

            return $this->successResponse($users, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }


    /**
     * Download users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function download(Request $request)
    {
        try {

            $rules = [
                'role' => 'required',
            ];

            $messages = [
                'role.required' => 'Role ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $role = intval($request->input('role'));
            $inactive = 2;

            if ($role > 0) {
                // Get users by the role ID
                $users = User::where('role_id', $role)
                                ->where('user_status_id', '!=', $inactive)
                                ->orderBy('id')->get();
            } else {
                // Get all the users
                $users = User::where('user_status_id', '!=', $inactive)->get();
            }

            $transform = UserDownload::collection($users);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }



    /**
     * Add a moduleset to all users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addModulesetAllUsers(Request $request)
    {

        try {

            $rules = [
                'id' => 'required|exists:module_sets,id',
            ];

            $messages = [
                'id.required' => 'A moduleset ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            if ($this->user->role->name != 'Super Administrator') {
                return $this->showMessage('You are not authorized to perform this action', 200);
            }

            $users = User::all();

            foreach ($users as $key => $user) {
                // Attach moduleset id to user without creating duplicate attachments
                $user->module_sets()->syncWithoutDetaching($request->id);
            }

            return $this->showMessage('Moduleset added successfully to all users', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to add a moduleset to all users', 400);
        }
    }

    /**
     * Send a PDF of user assessments analysis via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function analysisEmail(Request $request)
    {

        try {

            $rules = [
                'start' => 'required|string',
                'end' => 'required|string',
                'attachment' => 'required',
                'to' => 'required|email',
                'from' => 'required|email',
                'cc' => 'nullable|string',
            ];

            $messages = [
                'start.required' => 'Start date is required',
                'end.required' => 'End date is required',
                'attachment.required' => 'A base64 string attachment is required',
                'to.required' => 'Enter an email recipient',
                'from.required' => 'Send email is required',
                'to.email' => 'Please enter a valid recipient email address',
                'from.email' => 'Please enter a valid sender email address',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $time = Carbon::now();

            $file_name = strtolower('Client Assessment Analysis - ' . $this->user->company . ' - ' . $time->toDateString() . '.pdf');
            $file_name = preg_replace('/\s+/', '_', $file_name);

            Storage::disk('local')->put('pdfs/' . $file_name, base64_decode($request->attachment));

            $copy = [];

            if ($request->cc) {
                $cleancc = str_replace(' ', '', $request->cc);
                $copy = explode(',', trim($cleancc));
            }

            $bcopy = [];

            if ($request->bcc) {
                $cleanbcc = str_replace(' ', '', $request->bcc);
                $bcopy = explode(',', trim($cleanbcc));
            }

            $messages = [];

            $messages[] = 'Attached here is the client assessment analysis for ' . trim($this->user->company);
            $messages[] = 'During the period: <b>' . trim($request->start) . '</b> to <b>' . trim($request->end) . '</b>';
            $messages[] = 'Thank you';

            $notice = [
                'user' => $this->user,
                'to' => trim($request->to),
                'type' => 'local',
                'messages' => $messages,
                'file_name' => $file_name,
                'subject' => 'Client Assessment Analysis - ' . trim($this->user->company),
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice, 'attachment');

            return $this->showMessage('Your email was sent successfully', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }

    public function saveEmailToS3($text)
    {
        $s3 = \AWS::createClient('s3');
        $key = date('mdYhia') . '_' . str_random(6);
        $url = $s3->putObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'mailbox/emails/' . $key,
            'ACL'          => 'public-read',
            'ContentType' => 'text/plain',
            'Body' => $text,
        ]);

        $url = $url->get('ObjectURL');
        return $url;
    }

    public function savePaymentLogsToS3($text)
    {
        $s3 = \AWS::createClient('s3');
        $key = date('mdYhia') . '_' . str_random(6);
        $url = $s3->putObject([
            'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
            'Key'        => 'payments/' . $key,
            'ACL'          => 'public-read',
            'ContentType' => 'text/plain',
            'Body' => $text,
        ]);

        $url = $url->get('ObjectURL');
        return $url;
    }



    /**
     * Send new student first email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newStudentNotification(Request $request)
    {

        try {

            $rules = [
                'id' => 'required|exists:users,id',
                'invited_by' => 'required|exists:users,id',
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'password' => 'required',
            ];

            $info = [
                'id.required' => 'User ID is required',
                'id.exists' => 'That user does not exist',
                'invited_by.required' => 'invited by ID is required',
                'invited_by.exists' => 'That invited by user does not exist',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'That user group does not exist',
                'password.required' => 'Student password is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {
                $invitor = User::findOrFail($request->invited_by);
                $group = Group::findOrFail($request->group_id);
                $usergroup = UserGroup::findOrFail($request->user_group_id);
                $user = User::findOrFail($request->id);

                $array = [
                    'group_coaching' => 1,
                    'training_software' => 0,
                    'training_100k' => 0, 
                    'prep_roleplay' => 0,
                    'training_jumpstart' => 0, 
                    'training_lead_gen' => 0, 
                    'flash_coaching' => 0
                ];
                $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);

                $title = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;

                $messages = [];
                $summary = [];
                $messages[] = 'Welcome to my ' . $title;
                $messages[] = 'Here are your account details,';
                $messages[] = 'Username - <b>' . $user->email . '</b>';
                $messages[] = 'Password - <b>' . $request->password . '</b>';
                $summary[] = 'If you did not request access to this group, no further action is required';

                $notice = [
                    'user' => $invitor,
                    'student_name' => $user->first_name,
                    'to' => trim($user->email),
                    'messages' => $messages,
                    'summary' => $summary,
                    'subject' => 'Group Coaching Account Details - [' . $title . ']',
                    'copy' => [$invitor->email],
                    'bcopy' => [],
                ];

                ProcessEmail::dispatch($notice, 'newstudent');

                // Add student to the mailing list
                $this->addStudentToMailingList($user, $request->user_group_id);

                return $this->showMessage('New Member email sent successfully', 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }


    /**
     * Add a student to a coaches mailing list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addStudentToMailingList($student, $user_group_id)
    {

        $coach = $this->user;
        $integration = getIntegration($coach);

        // Add this student to coache's Mailing List
        if ($integration && $integration->aweber == 1) { // AWeber
            addSingleAweberSubscriber($coach, $user_group_id, $student->email, $student->first_name . ' ' . $student->last_name);
        } else if ($integration && $integration->active_campaign == 1) { // ActiveCampaign
            addSingleACampaignSubscriber($coach, $user_group_id, $student->email, $student->first_name, $student->last_name);
        } else if ($integration && $integration->getresponse == 1) { // GetResponse
            addSingleGetResponseSubscriber($coach, $user_group_id, $student->email, $student->first_name . ' ' . $student->last_name);
        }
    }

    /**
     * Add an existing user as a student to a group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function newStudentUser(Request $request)
    {

        try {

            $rules = [
                'id' => 'required|exists:users,id',
                'invited_by' => 'required|exists:users,id',
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $info = [
                'id.required' => 'User ID is required',
                'id.exists' => 'That user does not exist',
                'invited_by.required' => 'invited_by ID is required',
                'invited_by.exists' => 'That invited_by user does not exist',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'That user group does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $user_id = $request->id;
                $group_id = $request->group_id;
                $user_group_id = $request->user_group_id;
                $invited_by = $request->invited_by;

                $student = MemberGroupLesson::where('user_id', $user_id)->where('group_id', $user_group_id)->first();

                // User is not a member of this group
                if (!$student) {

                    $invitor = User::findOrFail($invited_by);
                    $usergroup = UserGroup::findOrFail($user_group_id);
                    $user = User::findOrFail($user_id);
                    $group = Group::findOrFail($group_id);

                    $array = [
                        'group_coaching' => 1,
                        'training_software' => 0,
                        'training_100k' => 0, 
                        'prep_roleplay' => 0,
                        'training_jumpstart' => 0, 
                        'training_lead_gen' => 0, 
                        'flash_coaching' => 0
                    ];
                    $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);

                    if ($group_id == 29) {
                        $this->addUserToCustomGroup($user_id, $group_id, $usergroup);
                        $this->preloadCustomGroupLessonMeetings($user_id, $invited_by, $group_id, $usergroup);
                    } else {
                        $this->addUserToGroup($user_id, $group_id, $usergroup);
                        $this->preloadLessonMeetings($user_id, $invited_by, $group_id, $usergroup);
                    }

                    $title = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;

                    $messages = [];
                    $summary = [];
                    $messages[] = 'Welcome to my ' . $title;
                    $messages[] = 'You can access the group via the link below.';
                    $summary[] = 'If you did not request access to this group, no further action is required';

                    $notice = [
                        'user' => $invitor,
                        'student_name' => $user->first_name,
                        'to' => trim($user->email),
                        'messages' => $messages,
                        'summary' => $summary,
                        'subject' => 'Group Coaching Account Details - [' . $title . ']',
                        'copy' => [$invitor->email],
                        'bcopy' => [],
                    ];

                    ProcessEmail::dispatch($notice, 'newstudent');

                    // Add student to the mailing list
                    $this->addStudentToMailingList($user, $request->user_group_id);

                    return $this->showMessage('User has been added to this group', 200);
                } else {
                    // Student is a member of this group
                    return $this->showMessage('That user is already a member of this group', 200);
                }
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }

    /**
     * Send a single email to student
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendSingleEmail($request)
    {
        $cypher = new Cypher();
        $attachment = '';
        if ($request->attachments && strlen($request->attachments) > 0) {
            $attachment = $cypher->decrypt(env('HASHING_SALT'), base64_decode($request->attachments));

            $delimiter = 'com';
            $words = explode($delimiter, $attachment);
        }
        $app_content_url = env('APP_CONTENT_URL');

        $to = trim($request->to);
        $touser = User::where('email', $to)->first();

        $copy = [];

        if ($request->cc) {
            $cleancc = str_replace(' ', '', $request->cc);
            $copy = explode(',', trim($cleancc));
        }

        $bcopy = [];

        if ($request->bcc && (strlen($request->bcc) > 0)) {
            $cleanbcc = str_replace(' ', '', $request->bcc);
            $bcopy = explode(',', trim($cleanbcc));
        }

        $messages = [];
        $messages[] = Str::of($request->message)->markdown([
            'html_input' => 'strip',
        ]);

        // Email has an attachment
        if (($attachment && (strlen($attachment) > 0)) && ($words[0].$delimiter === $app_content_url)) {
            $cleanat = str_replace(' ', '', $attachment);
            $attachments = explode(',', trim($cleanat));

            $notice = [
                'user' => $this->user,
                'to' => trim($to),
                'type' => 'url',
                'messages' => $messages,
                'attachments' => $attachments,
                'subject' => $request->subject,
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice, 'attachment');
        } else {
            // Email without attachment
            $notice = [
                'user' => $this->user,
                'to' => trim($to),
                'messages' => $messages,
                'subject' => $request->subject,
                'copy' => $copy,
                'bcopy' => $bcopy,
            ];

            ProcessEmail::dispatch($notice);
        }

        if ($request->type == 'mailbox') {

            // Save the emails to DB
            $box = new MailBox;

            if ($request->parent) {
                $box->parent = (int)$request->parent;
            }

            if ($request->recipient == 'group') {
                $box->group = (int)$request->group;
            }

            $box->from = trim($request->from);
            $box->to = trim($to);
            $box->uuid = $request->uuid;
            $box->from_id = $request->from_id;
            $box->to_id = ($touser)? $touser->id : null;
            $box->subject = $request->subject;
            $url = $this->saveEmailToS3(Str::of($request->message)->markdown([
                'html_input' => 'strip',
            ]));
            $box->body = $url;
            $box->read = 0;
            if ($attachment && (strlen($attachment) > 0)) {
                $box->attachments = trim($attachment);
            }

            $box->save();
        }
    }


    /**
     * Send an email to multiple student in a group
     *
     * @param  \Illuminate\Http\Request  $request 
     * @return \Illuminate\Http\Response
     */
    public function saveMultipleMailBox($request, $members)
    {

        foreach ($members as $key => $member) {

            /*  Skip the first member $key == 0
                since the first member got the email and a mailbox record 
                created on sendSingleEmail above 
            */

            if ($key > 0) {
                
                $touser = User::where('email', $member->email)->first();

                // Save the email records to DB
                $box = new MailBox;

                if ($request->parent) {
                    $box->parent = (int)$request->parent;
                }

                if ($request->recipient == 'group') {
                    $box->group = (int)$request->group;
                }

                $box->from = trim($request->from);
                $box->to = $member->email;
                $box->from_id = $request->from_id;
                $box->to_id = ($touser)? $touser->id : null;
                $box->uuid = $request->uuid;
                $box->subject = trim($request->subject);
                $url = $this->saveEmailToS3(Str::of($request->message)->markdown([
                    'html_input' => 'strip',
                ]));
                $box->body = $url;
                $box->read = 0;
                if ($request->attachments && (strlen($request->attachments) > 0)) {
                    $box->attachments = trim($request->attachments);
                }

                $box->save();
            }
        } // End for each

    }


    /**
     * Send an email to multiple student in a group
     *
     * @param  \Illuminate\Http\Request  $request 
     * @return \Illuminate\Http\Response
     */
    public function sendMultipleEmails($request, $members)
    {

        if (count($members) > 0) {

            // Create a blind copy (BCC) email for all the group members
            $bcopy = '';

            foreach ($members as $key => $member) {
                if ($key == 0) {
                    $request->to = $member->email;
                } else {
                    $bcopy .= $member->email . ',';
                }
            }

            $bcopy = rtrim($bcopy, ','); // Strip whitespace (or other characters) from the end of a string

            $request->bcc = $bcopy;

            $integration = getIntegration($this->user);

            if ($integration && ($integration->aweber == 1)) { // Send emails via Aweber
                // TO DO
                // Create function to send emails to AWeber list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendAweberBroadcast($this->user, $request->group, $details);
            } else if ($integration && ($integration->active_campaign == 1)) { // Send emails via ActiveCampaign
                // TO DO
                // Create function to send emails to ActiveCampaign list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendACampaignBroadcast($this->user, $request->group, $details);
            } else if ($integration && ($integration->getresponse == 1)) { // Send emails via GetResponse
                // TO DO
                // Create function to send emails to GetResponse list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendGetResponseBroadcast($this->user, $request->group, $details);
            } else { // Send emails via system template emails i.e MailGun
                $this->sendSingleEmail($request); // Send the email and all the members as BCC
            }

            $this->saveMultipleMailBox($request, $members); // Add records to the mailbox table
        }
    }


    /**
     * Send notes/messages to students/coaches via email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function studentEmail(Request $request)
    {
        try {

            $rules = [
                'to' => 'required|email',
                'from' => 'required|email',
                'from_id' => 'required|exists:users,id',
                'cc' => 'nullable|string',
                'message' => 'required|string',
                'subject' => 'required|string',
                'type' => 'required|string',
                'recipient' => 'required|string',
            ];

            $info = [
                'to.required' => 'Enter an email recipient',
                'from.required' => 'Send email is required',
                'from_id.exists' => 'That user does not exist',
                'to.email' => 'Please enter a valid recipient email address',
                'from.email' => 'Please enter a valid sender email address',
                'message.required' => 'Message is required',
                'subject.required' => 'Subject is required',
                'type.required' => 'Type is required',
                'recipient.required' => 'Recipient type is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $request->uuid = uniqid(); // Create a unique id for email

            if ($request->recipient == 'group') {
                // Get all the users under this group
                $usergroup = UserGroup::findOrFail($request->group);
                $members = $usergroup->members();
                return $this->sendMultipleEmails($request, $members);
            } else {
                $this->sendSingleEmail($request);
            }

            return $this->showMessage('Your email was sent successfully', 200);
        } catch (Exception $e) {

            return $this->errorResponse('Error occured while trying to send email', 400);
        }
    }


    /**
     * Save a new coach group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveNewGroup(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_id' => 'required|exists:users,id',
                'time_zone' => 'required',
                'status' => 'required',
            ];

            $info = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'status.required' => 'Group type is required.',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {
                if($request->has('id')){

                    $group = UserGroup::updateOrCreate(
                        [
                            'id' => $request->id,
                        ],
                        ['name' => $request->name,
                         'meeting_day' => $request->meeting_day,
                         'time_zone'=> $request->time_zone ,
                         'meeting_time'=> $request->meeting_time ,
                         'meeting_url'=> $request->meeting_url ,
                         'price'=> $request->price ,
                         'user_id'=> $request->user_id ,
                         'group_id'=> $request->group_id ,
                         'active'=> $request->active ,
                         'status'=> $request->status ,
                        ]
                    );

                    $transform = new NewUserGroupResource($group);

                return $this->successResponse($transform, 200);

                }else{

                    $user_id = $request->user_id;
                    $group_id = $request->group_id;

                    $group = new UserGroup;
                    $group->name = trim($request->name);
                    $group->user_id = $user_id;
                    $group->group_id = $group_id;
                    $group->meeting_day = $request->meeting_day;
                    $group->meeting_time = $request->meeting_time;
                    $group->time_zone = $request->time_zone;
                    $group->meeting_url = trim($request->meeting_url);
                    $group->price = $request->price;
                    $group->status = $request->status;
                    $group->save();

                    $this->addUserToGroup($user_id, $group_id, $group);
                    $this->preloadLessonMeetings($user_id, $user_id, $group_id, $group);

                    $transform = new NewUserGroupResource($group);

                    return $this->successResponse($transform, 200);
                }
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group template not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to get user group templates', 400);
        }
    }




    /**
     * Get coach group details
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getGroupDetails(Request $request)
    {

        try {

            $rules = [
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $info = [
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'That user group does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $user_group_id = $request->user_group_id;

                $group = UserGroup::findOrFail($user_group_id);

                $transform = new UserGroupResource($group);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying get user group', 400);
        }
    }


    /**
     * Update coach group details
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateGroupDetails(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_id' => 'required|exists:users,id',
                'meeting_day' => 'required',
                'next_meeting_time' => 'required',
                'time_zone' => 'required',
                'meeting_url' => 'required',
                'price' => 'required',
                'user_group_id' => 'required|exists:user_groups,id',
                'status' => 'required',
            ];

            $info = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'meeting_day.required' => 'Meeting day is required',
                'next_meeting_time.required' => 'Next meeting time is required',
                'time_zone.required' => 'Timezone is required',
                'meeting_url.required' => 'Meeting URL is required',
                'price.required' => 'Price is required',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'That user group does not exist',
                'status.required' => 'Group type is required.',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $user_id = $request->user_id;
                $group_id = $request->group_id;
                $user_group_id = $request->user_group_id;

                $group = UserGroup::updateOrCreate(
                    [
                        'id' => $user_group_id,
                        'user_id' => $user_id,
                        'group_id' => $group_id,
                    ],
                    [
                        'name' => ($request->name) ? trim($request->name) : '',
                        'meeting_day' => $request->meeting_day,
                        'time_zone' => $request->time_zone,
                        'meeting_url' => trim($request->meeting_url),
                        'price' => $request->price,
                        'status'=> $request->status ,
                    ]
                );

                $next_meeting_time = $request->next_meeting_time;

                // Update group lessons that havent taken place
                if($next_meeting_time != 'past'){

                    $status = ($request->all)? true : false;

                    $next_meeting_time = ($status)? $group->meeting_time : $next_meeting_time;

                     if ($group_id == 29) {

                        //get custom group lesson
                        $this->updateCustomGroupLessons($group, $user_group_id, $next_meeting_time, $status);
                        $this->updateUserCustomGroupMembers($group, $user_group_id, $next_meeting_time, $status, $request->lesson_id);
                        $this->updatePreloadCustomGroupLessonMeetings($group, $user_group_id, $next_meeting_time, $status, $request->lesson_id);

                        $transform = new UserGroupResource($group);

                        return $this->successResponse($transform, 200);
                    }

                    $this->updateUserGroupMembers($group, $next_meeting_time, $status, $request->lesson_id);
                    $this->updatePreloadLessonMeetings($group, $next_meeting_time, $status, $request->lesson_id);
                }

                $transform = new UserGroupResource($group);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to update user group', 400);
        }
    }


    public function updateUserGroupMembers($usergroup, $next_meeting_time, $status, $lesson_id)
    {
        
        // Get all next lessons
        $all = ($status)? $usergroup->lessons() : $usergroup->allnextlessons();

        $lesson_ids = array_column($all->toArray(), 'id');

        if($lesson_id){
            array_unshift($lesson_ids, (int)$lesson_id);
        }

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $next_meeting_time);
        $date->setTimezone('UTC');
        
        // update user to the group coaching
        $records = DB::table('member_group_lesson')
            ->join('lessons', 'lessons.id', '=', 'member_group_lesson.lesson_id')
            ->select('member_group_lesson.*')
            ->where('member_group_lesson.group_id', $usergroup->id)
            ->where('member_group_lesson.invited_by', $usergroup->user_id)
            ->whereIn('member_group_lesson.lesson_id', $lesson_ids)
            ->orderBy('lessons.next_lesson')->get(); //Get all user lessons

        $lessons = MemberGroupLesson::hydrate($records->toArray());
        $time = strtotime($next_meeting_time);

        foreach ($lessons as $each => $lesson) {

            $lesson->created_at = $date;
            $lesson->updated_at = $date;
            $lesson->save();

            $next = $each + 1;
            if ($next < sizeof($lessons)) {
                if ($lessons[$next]->lesson_id != $lessons[$each]->lesson_id) {
                    $date = $date->next($usergroup->meeting_day);
                    $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                }
            }
        }
    }


    public function updatePreloadLessonMeetings($usergroup, $next_meeting_time, $status, $lesson_id)
    {

        // Get all next lessons
        $all = ($status)? $usergroup->lessons() : $usergroup->allnextlessons();

        $lesson_ids = array_column($all->toArray(), 'id');

        if($lesson_id){
            array_unshift($lesson_ids, (int)$lesson_id);
        }

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $next_meeting_time);
        $date->setTimezone('UTC');
        
        // preload meetings for this user 
        $records = DB::table('gc_lesson_meetings')
            ->join('lessons', 'lessons.id', '=', 'gc_lesson_meetings.lesson_id')
            ->select('gc_lesson_meetings.*')
            ->where('gc_lesson_meetings.group_id', $usergroup->id)
            ->where('gc_lesson_meetings.invited_by', $usergroup->user_id)
            ->whereIn('gc_lesson_meetings.lesson_id', $lesson_ids)
            ->orderBy('lessons.next_lesson')->get(); //Get all user lessons

        $lessons = GroupCoachingLessonMeeting::hydrate($records->toArray());
        $time = strtotime($next_meeting_time);

        foreach ($lessons as $each => $lesson) {

            $lesson->time_zone = $usergroup->time_zone;
            $lesson->meeting_url = $usergroup->meeting_url;
            $lesson->meeting_time = $date;
            $lesson->created_at = $date;
            $lesson->updated_at = $date;
            $lesson->save();

            $next = $each + 1;
            if ($next < sizeof($lessons)) {
                if ($lessons[$next]->lesson_id != $lessons[$each]->lesson_id) {
                    $date = $date->next($usergroup->meeting_day);
                    $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                }
            }
        }
    }


    /**
     * Update student has paid 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function studentHasPaid(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:user_groups,id',
                'student_id' => 'required|exists:users,id',
                'coach_id' => 'required|exists:users,id',
                'successful' => 'required',
                'type' => 'required',
                'log' => 'required',
            ];

            $info = [
                'student_id.required' => 'User ID is required',
                'student_id.exists' => 'That user does not exist',
                'coach_id.required' => 'Coach ID is required',
                'coach_id.exists' => 'That coach does not exist',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'successful.required' => 'Success status is required',
                'type.required' => 'Type is required',
                'log.required' => 'Log details are required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $coach_id = $request->coach_id;
                $student_id = $request->student_id;
                $group_id = $request->group_id;

                $url = $this->savePaymentLogsToS3(base64_encode(trim($request->log)));

                $payment = Payment::updateOrCreate(
                    [
                        'coach_id' => $coach_id,
                        'student_id' => $student_id,
                        'group_id' => $group_id,
                    ],
                    [
                        'paid' => (int) $request->successful,
                        'type' => $request->type,
                        'log' => $url,
                    ]
                );

                $transform = new PaymentResource($payment);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to update user group', 400);
        }
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

    public function freeRegister(Request $request)
    {

        try {

            $rules = [
                'first_name' => 'required|string:max:100',
                'last_name' => 'required|string:max:100',
                'email' => 'required|string|email|unique:users',
                'company' => 'required|string',
            ];

            $messages = [
                'email.required' => 'The email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'The email address you entered already exist',
                'first_name.required' => 'The first name is required',
                'last_name.required' => 'The last name is required',
                'company.required' => 'The company name is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $password = (string)rand(1000000, 9999999);

            $user = new User;
            $user->first_name = trim($request->input('first_name'));
            $user->last_name = trim($request->input('last_name'));
            $user->email = trim($request->input('email'));
            $user->company = trim($request->input('company'));

            $user->password = $password;
            $user->role_id = 7; // Freelancer Role ID

            $role = Role::findOrFail(7);

            $user->save();
            $user->assignRole($role);
            $user->module_sets()->attach(3); // Attach free profit jumpstart moduleset to the new user
            $user->module_sets()->attach(4); // Attach free profit deep dive moduleset to the new user

            $user->notify(new NewUser($user, $password));

            $transform = new UserResource($user);

            return $this->showMessage($transform, 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }



    /**
     * Update roles of a list of users 
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function updateMultiUsersRoles(Request $request)
    {

        try {

            $rules = [
                'users' => 'required|string',
                'role_id' => 'required|exists:roles,id',
            ];

            $messages = [
                'users.required' => 'The users list is required',
                'role_id.required' => 'The role ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $role_id = $request->input('role_id');
            $list = trim($request->input('users'));

            $array = explode(",", $list);
            $count = 0;
            $found = [];
            $names = [];
            $notfound = [];
            foreach ($array as $key => $email) {
                $email = trim($email);
                if (strlen($email) > 0) {
                    $user = User::where('email', $email)->first();
                    if ($user) {
                        $user->roles()->detach();

                        $role = Role::findOrFail($role_id);
                        $user->assignRole($role);

                        $user->role_id = $role_id;

                        if ($user->isDirty()) {
                            $user->save();
                        }
                        $count++;
                        $found[] = $user->email;
                        $names[] = $user->first_name . ' ' . $user->last_name;
                    } else {
                        $notfound[] = $email;
                    }
                }
            }

            return $this->showMessage([
                'message' => $count . ' users roles updated',
                'found_email' => $found,
                'found_name' => $names,
                'not_found' => $notfound
            ], 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
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

            $rules = [
                'first_name' => 'required|string:max:100',
                'last_name' => 'required|string:max:100',
                'email' => 'required|string|email|unique:users',
                'company' => 'required|string',
                'role_id' => 'required|exists:roles,id',
            ];

            $messages = [
                'email.required' => 'The email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'The email address you entered already exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('first_name')))){
                return $this->errorResponse('First name contains invalid characters', 400);
            }

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('last_name')))){
                return $this->errorResponse('Last name contains invalid characters', 400);
            }

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('company')))){
                return $this->errorResponse('Company name contains invalid characters', 400);
            }

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

            $user->password = $password;
            $user->role_id = $request->input('role_id');
            $user->created_by_id = ($this->user->id) ? $this->user->id : null;

            if ($request->has('website')) {
                $user->website = trim($request->input('website'));
            }

            if ($request->has('owner')) {
                $user->owner = trim($request->input('owner'));
            }

            $role = Role::findOrFail((int)$request->input('role_id'));

            $date = Carbon::now();

            $user->save();
            $user->assignRole($role);
            $user->module_sets()->attach(5); // Attach profit jumpstart moduleset to the new user
            $module_sets = ModuleSet::find(5);
            $access = 'Granted';
            UserModuleAccessMetaData::create([
                'user_id' => $user->id,
                'module_name' => $module_sets->alias,
                'description' => $module_sets->alias.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                'changed_by' => $this->user->id,
                'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
            ]);
            // $user->module_sets()->attach(6); // Attach breakthrough 40 moduleset to the new user
            $user->module_sets()->attach(7); // Attach jumpstart 40 moduleset to the new user
            $access = 'Granted';
            $module_sets = ModuleSet::find(7);
            UserModuleAccessMetaData::create([
                'user_id' => $user->id,
                'module_name' => $module_sets->alias,
                'description' => $module_sets->alias.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                'changed_by' => $this->user->id,
                'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
            ]);
            $training_access = new TrainingAccess;
            $training_access->user_id = $user->id;
            $training_access->save();

            $date = Carbon::now();

            if ((int)$training_access->training_software === 1) {
                $access = 'Granted';
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Software Training',
                    'description' => 'Software Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }
            if ((int)$training_access->training_100k === 1) {
                $access = 'Granted';
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => '100k Training',
                    'description' => '100k Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }
            if ((int)$training_access->training_lead_gen === 1) {
                $access = 'Granted';
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Lead Gen Training',
                    'description' => 'Lead Gen Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$training_access->prep_roleplay === 1) {
                $access = 'Granted';
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Prep Roleplay',
                    'description' => 'Prep Roleplay access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$training_access->training_jumpstart === 1) {
                $access = 'Granted';
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Jumpstart Training',
                    'description' => 'Jumpstart Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            $user->notify(new NewUser($user, $password));

            $transform = new UserResource($user);

            return $this->showMessage($transform, 201);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
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
            $user = User::findOrFail($id);
            $transform = new UserResource($user);

            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
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
            $cypher = new Cypher();
            $id = $cypher->decryptID(env('HASHING_SALT'), $id);
            $id = intval($id);
            $user = User::findOrFail($id);

            $role_id = intval($cypher->decryptID(env('HASHING_SALT'), trim($request->role_id)));

            $rules = [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'edit_password' => 'required|boolean',
                'company' => 'required|string',
                'role_id' => 'required|exists:roles,id',
            ];

            $messages = [
                'email.required' => 'The email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'The email address you entered already exist',
            ];

            $validator = Validator::make([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'edit_password' => $request->edit_password,
                'company' => $request->company, 
                'role_id' => intval($cypher->decryptID(env('HASHING_SALT'), $request->role_id)),
                'id' => intval($cypher->decryptID(env('HASHING_SALT'), $request->id))
            ], $rules, $messages);

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('first_name')))){
                return $this->errorResponse('First name contains invalid characters', 400);
            }

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('last_name')))){
                return $this->errorResponse('Last name contains invalid characters', 400);
            }

            if (!preg_match(env('CSV_INJECTION_REGEX'), trim($request->input('company')))){
                return $this->errorResponse('Company name contains invalid characters', 400);
            }

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $change_description = '';
            if ($request->has('change_description') && $request->input('change_description') !== '') {
                $change_description = trim($request->input('change_description'));
            }
            $date = Carbon::now();
            if (trim($request->input('first_name') !== $user->first_name)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->first_name, 
                    'new_value' => trim($request->input('first_name')),
                    'description' => $change_description,
                    'column_name' => 'first_name', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->first_name = trim($request->input('first_name'));
            }
            if (trim($request->input('last_name') !== $user->last_name)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->last_name, 
                    'new_value' => trim($request->input('last_name')),
                    'description' => $change_description, 
                    'column_name' => 'last_name', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->last_name = trim($request->input('last_name'));
            }
            if (trim($request->input('email') !== $user->email)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->email, 
                    'new_value' => trim($request->input('email')),
                    'description' => $change_description,
                    'column_name' => 'email', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->email = trim($request->input('email'));
            }
            if (trim($request->input('company') !== $user->company)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->company, 
                    'new_value' => trim($request->input('company')),
                    'description' => $change_description, 
                    'column_name' => 'company', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->company = trim($request->input('company'));
            }

            if ($request->has('website') && trim($request->input('website')) !== $user->website) {
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->website, 
                    'new_value' => trim($request->input('website')),
                    'description' => $change_description,
                    'column_name' => 'website', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->website = trim($request->input('website'));
            }

            if ($request->has('owner') && trim($request->input('owner')) !== $user->owner) {
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->owner, 
                    'new_value' => trim($request->input('owner')),
                    'description' => $change_description, 
                    'column_name' => 'owner', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->owner = trim($request->input('owner'));
            }

            if ($request->input('edit_password')) {
                $account_name = $user->first_name.' '.$user->last_name;
                $changers_name = $this->user->first_name.' '.$this->user->last_name;
                UserPasswordChangesMetaData::create([
                    'user_id' => $user->id, 
                    'description' => $account_name.'`s account password has been changed by '.$changers_name,  
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->password = $request->input('password');
            }
            $role = Role::findOrFail($role_id);

            if ($role_id !== $user->role_id) {
                $previous_role = Role::findOrFail($user->role_id);
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $previous_role->name, 
                    'new_value' => $role->name,
                    'description' => $change_description,
                    'column_name' => 'role', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                if ($role_id === 5) {
                    UserCancelationMetaData::create([
                        'user_id' => $user->id,
                        'canceled_by' => $this->user->id, 
                        'canceled_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                }
                if ($previous_role->id === 9) {
                    $training_access = TrainingAccess::where('user_id', $user->id)->first();
                    $training_access->licensee_onboarding = 0;
                    $training_access->save();

                }
                $user->roles()->detach(); // detach any existing roles

                $role = Role::findOrFail($role_id);
                $user->assignRole($role);

                $user->role_id = $role_id;
            }

            $this->updateStatus($user,$request,$change_description,$date);

            if ($user->isDirty()) {
                $user->save();
            }

            $transform = new UserResource($user);
            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function updateStudent(Request $request, $id)
    {

        try {

            $user = User::findOrFail($id);
            $user_company = Company::findOrFail($user->company_id);

            $rules = [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
                'edit_password' => 'required|boolean',
                'role_id' => 'required|exists:roles,id',
            ];

            $messages = [
                'email.required' => 'The email address is required',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'The email address you entered already exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            $date = Carbon::now();
            if (trim($request->input('first_name') !== $user->first_name)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->first_name, 
                    'new_value' => trim($request->input('first_name')), 
                    'column_name' => 'first_name', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->first_name = trim($request->input('first_name'));
                $user_company->contact_first_name = trim($request->input('first_name'));
            }

            if (trim($request->input('last_name') !== $user->last_name)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->last_name, 
                    'new_value' => trim($request->input('last_name')), 
                    'column_name' => 'last_name', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->last_name = trim($request->input('last_name'));
                $user_company->contact_last_name = trim($request->input('last_name'));
            }

            if (trim($request->input('email') !== $user->email)){
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $user->email, 
                    'new_value' => trim($request->input('email')), 
                    'column_name' => 'email', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->email = trim($request->input('email'));
                $user_company->contact_email = trim($request->input('email'));
            }

            if ($request->has('company') && (strlen($request->company) > 0)) {
                if (trim($request->input('company') !== $user->company)){
                    UserChangesMetaData::create([
                        'user_id' => $user->id, 
                        'current_value' => $user->company, 
                        'new_value' => trim($request->input('company')), 
                        'column_name' => 'company', 
                        'changed_by' => $this->user->id,
                        'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                    $user->company = trim($request->input('company'));
                    $user_company->company_name = trim($request->input('company'));
                }
            }

            if ($request->has('website') && (strlen($request->website) > 0)) {
                if (trim($request->input('website') !== $user->website)){
                    UserChangesMetaData::create([
                        'user_id' => $user->id, 
                        'current_value' => $user->website, 
                        'new_value' => trim($request->input('website')), 
                        'column_name' => 'website', 
                        'changed_by' => $this->user->id,
                        'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                    $user->website = trim($request->input('website'));
                    $user_company->company_website = trim($request->input('website'));
                }
            }

            if ($request->has('owner') && (strlen($request->owner) > 0)) {
                if (trim($request->input('owner') !== $user->owner)){
                    UserChangesMetaData::create([
                        'user_id' => $user->id, 
                        'current_value' => $user->website, 
                        'new_value' => trim($request->input('owner')), 
                        'column_name' => 'owner', 
                        'changed_by' => $this->user->id,
                        'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                    $user->owner = trim($request->input('owner'));
                }
            }

            if ($request->input('edit_password')) {
                $account_name = $user->first_name.' '.$user->last_name;
                $changers_name = $this->user->first_name.' '.$this->user->last_name;
                UserPasswordChangesMetaData::create([
                    'user_id' => $user->id, 
                    'description' => $account_name.'`s account password has been changed by '.$changers_name,  
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);
                $user->password = $request->input('password');
            }
            $role = Role::findOrFail((int)$request->input('role_id'));

            if ($user->role_id !== (int)$request->input('role_id')) {
                $previous_role = Role::findOrFail($user->role_id);
                UserChangesMetaData::create([
                    'user_id' => $user->id, 
                    'current_value' => $previous_role->name, 
                    'new_value' => $role->name, 
                    'column_name' => 'role', 
                    'changed_by' => $this->user->id,
                    'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                ]);

            }
            $user->roles()->detach(); // detach any existing roles

            $role = Role::findOrFail($request->input('role_id'));
            $user->assignRole($role);

            $user->role_id = $request->input('role_id');
            $change_description = '';
            if ($request->has('change_description') && $request->input('change_description') !== '') {
                $change_description = trim($request->input('change_description'));
            }
            $this->updateStatus($user,$request,$change_description,$date);

            if ($user->isDirty()) {
                $user->save();
            }

            if ($user_company->isDirty()) {
                $user_company->save();
            }

            $user = $user->refresh();

            $this->updateClientCompany($user);

            $user = $user->refresh();

            $data = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'company' => $user->company,
                'company_id' => $user->company_id,
                'website' => $user->website,
                'role_id' => $user->role_id,
                'profile_pic' => $user->profile_pic,
                'title' => $user->title,
                'location'=> $user->location,
                'time_zone'=> $user->time_zone,
                'phone_number'=> $user->phone_number,
                'birthday'=> $user->birthday,
                'facebook'=> $user->facebook,
                'twitter'=> $user->twitter,
                'linkedin'=>$user->linkedin,
                'trainings' => new TrainingResource($user->trainingAccess),
            ];

            return $this->successResponse($data, 200);
            
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }



    /**
     * Update or create a new company account associated to this student / user
     *
     * @param  $company
     * 
     */
    private function updateClientCompany($user){

            $company = DB::table('companies')->where('contact_email', trim($user->email))->first();

            TrainingAccess::firstOrCreate([ 'user_id' => $user->id]);
                
            if($company){

                $a = Company::hydrate([$company])->first();
                
                if($user->company){
                     $a->company_name = $user->company;
                }else{
                    $a->company_name = $user->first_name .' - Company';
                }

                if($user->website){
                    $a->company_website = $user->website;
                }

                if($user->title){
                    $a->contact_title = $user->title;
                }

                if($user->time_zone){
                    $a->time_zone = $user->time_zone;
                }

                if($user->contact_phone){
                    $a->contact_phone = $user->phone_number;
                }
                
                $a->save();
                $user->company_id = $a->id;

                if ($user->isDirty()) {
                    $user->save();
                }

            }else{

                $b = new Company;
                
                if ($user->first_name) {
                    $b->contact_first_name = $user->first_name;
                }

                if ($user->last_name) {
                    $b->contact_last_name = $user->last_name;
                }

                if ($user->title) {
                    $b->contact_title = $user->title;
                }

                if ($user->phone_number) {
                    $b->contact_phone = $user->phone_number;
                }

                if ($user->email) {
                    $b->contact_email = $user->email;
                }

                if ($user->company) {
                    $b->company_name = $user->company;
                }else{
                    $b->company_name = $user->first_name .' - Company';
                }

                if ($user->website) {
                    $b->company_website = $user->website;
                }

                if ($user->time_zone) {
                    $b->time_zone = $user->time_zone;
                }

                $b->save();
                $b = $b->refresh();
                $user->company_id = $b->id;
                $user->save();
                $user->companies()->attach($b->id);
            }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function updateStudentProfile(Request $request, $id)
    {
        try {

            $user = User::findOrFail($id);

            if ($request->has('time_zone') && (strlen($request->time_zone) > 0)) {
                $user->time_zone = trim($request->input('time_zone'));
            }

            if ($request->has('title') && (strlen($request->title) > 0)) {
                $user->title = trim($request->input('title'));
            }

            if ($request->has('location') && (strlen($request->location) > 0)) {
                $user->location = trim($request->input('location'));
            }

            if ($request->has('phone_number') && (strlen($request->phone_number) > 0)) {
                $user->phone_number = trim($request->input('phone_number'));
            }

            if ($request->has('birthday') && (strlen($request->birthday) > 0)) {
                $user->birthday = trim($request->input('birthday'));
            }

            if ($request->has('facebook') && (strlen($request->facebook) > 0)) {
                $user->facebook = trim($request->input('facebook'));
            }

            if ($request->has('twitter') && (strlen($request->twitter) > 0)) {
                $user->twitter = trim($request->input('twitter'));
            }

            if ($request->has('linkedin') && (strlen($request->linkedin) > 0)) {
                $user->linkedin = trim($request->input('linkedin'));
            }

            $user->save();

            $user = $user->refresh();

            $this->updateClientCompany($user);

            $user = $user->refresh();

            $data = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'company' => $user->company,
                'company_id' => $user->company_id,
                'website' => $user->website,
                'role_id' => $user->role_id,
                'profile_pic' => $user->profile_pic,
                'title' => $user->title,
                'location'=> $user->location,
                'time_zone'=> $user->time_zone,
                'phone_number'=> $user->phone_number,
                'birthday'=> $user->birthday,
                'facebook'=> $user->facebook,
                'twitter'=> $user->twitter,
                'linkedin'=>$user->linkedin,
                'trainings' => new TrainingResource($user->trainingAccess),
            ];

            return $this->successResponse($data, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function updateUserProfile(Request $request)
    {
        try {

            $rules = [
                'id' => 'required|exists:users,id',
            ];

            $messages = [
                'id.required' => 'User ID is required',
                'id.exists' => 'That user cannot be found',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::findOrFail($request->id);

            if ($request->has('time_zone') && (strlen($request->time_zone) > 0)) {
                $user->time_zone = trim($request->input('time_zone'));
            }

            if ($request->has('title') && (strlen($request->title) > 0)) {
                $user->title = trim($request->input('title'));
            }

            if ($request->has('location') && (strlen($request->location) > 0)) {
                $user->location = trim($request->input('location'));
            }

            if ($request->has('phone_number') && (strlen($request->phone_number) > 0)) {
                $user->phone_number = trim($request->input('phone_number'));
            }

            if ($request->has('birthday') && (strlen($request->birthday) > 0)) {
                $user->birthday = trim($request->input('birthday'));
            }

            if ($request->has('facebook') && (strlen($request->facebook) > 0)) {
                $user->facebook = trim($request->input('facebook'));
            }

            if ($request->has('twitter') && (strlen($request->twitter) > 0)) {
                $user->twitter = trim($request->input('twitter'));
            }

            if ($request->has('linkedin') && (strlen($request->linkedin) > 0)) {
                $user->linkedin = trim($request->input('linkedin'));
            }

            if (
                ($request->has('fifteen_url')) && (strlen($request->fifteen_url) > 0) ||
                ($request->has('thirty_url')) && (strlen($request->thirty_url) > 0) ||
                ($request->has('forty_five_url')) && (strlen($request->forty_five_url) > 0)  ||
                ($request->has('sixty_url')) && (strlen($request->sixty_url) > 0)
            ) {
                $meetings = UserCalendarURL::firstOrNew(['user_id' => $request->id]);
                
                if(($request->has('fifteen_url')) && (strlen($request->fifteen_url) > 0)){
                   $meetings->fifteen_url = $request->fifteen_url; 
                }

                if(($request->has('thirty_url')) && (strlen($request->thirty_url) > 0)){
                   $meetings->thirty_url = $request->thirty_url; 
                }

                if(($request->has('forty_five_url')) && (strlen($request->forty_five_url) > 0)){
                   $meetings->forty_five_url = $request->forty_five_url; 
                }

                if(($request->has('sixty_url')) && (strlen($request->sixty_url) > 0)){
                   $meetings->sixty_url = $request->sixty_url; 
                }

                if ($meetings->isDirty()) {
                    $meetings->save();
                }
            }

            $user->save();

            $user = $user->refresh();

            $company = DB::table('companies')->where('contact_email', trim($user->email))->first();

            if($company){

                $a = Company::hydrate([$company])->first();
                
                if($user->company){
                     $a->company_name = $user->company;
                }else{
                    $a->company_name = $user->first_name .' - Company';
                }

                if($user->website){
                    $a->company_website = $user->website;
                }

                if($user->title){
                    $a->contact_title = $user->title;
                }

                if($user->time_zone){
                    $a->time_zone = $user->time_zone;
                }

                if($user->contact_phone){
                    $a->contact_phone = $user->phone_number;
                }
                
                $a->save();
                $user->company_id = $a->id;

                if ($user->isDirty()) {
                    $user->save();
                }
            }

            $user = $user->refresh();

            $data = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'company' => ($user->company)? $user->company : '',
                'company_id' => $user->company_id,
                'website' => $user->website,
                'role_id' => $user->role_id,
                'profile_pic' => ($user->profile_pic)? $user->profile_pic : '',
                'title' => ($user->title)? $user->title : '',
                'location'=> ($user->location)? $user->location : '',
                'time_zone'=> ($user->time_zone)? $user->time_zone : '',
                'phone_number'=> ($user->phone_number)? $user->phone_number : '',
                'birthday'=> ($user->birthday)? $user->birthday : '',
                'facebook'=> ($user->facebook)? $user->facebook : '',
                'twitter'=> ($user->twitter)? $user->twitter : '',
                'linkedin'=>($user->linkedin)? $user->linkedin : '',
                'calendarurls'=>($user->calendarurls)? $user->calendarurls : null,
            ];

            return $this->successResponse($data, 200);

        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }



    public function addUserToGroup($user_id, $group_id, $usergroup)
    {

        // add user to the group coaching
        $lessons = GroupLesson::where('group_id', $group_id)->get(); //Get all lessons

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
        $date->setTimezone('UTC');

        foreach ($lessons as $key => $lesson) {
            $mgl = MemberGroupLesson::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->lesson_id,
                    'invited_by' => $this->user->id
                ],
                ['created_at' => $date, 'updated_at' => $date]
            );
            $date = $date->next($usergroup->meeting_day);
            $date->setTime(date('H', strtotime($usergroup->meeting_time)), date('i', strtotime($usergroup->meeting_time)), date('s', strtotime($usergroup->meeting_time)));
        }
    }

    public function addStripe($user_id, $stripe_id)
    {

        // add user stripe id
        // This is for staging purposes
        // should be disabled on production

        $si = StripeIntegration::updateOrCreate(['user_id' => $user_id], ['stripe_id' => $stripe_id]);
        $pi = Integration::updateOrCreate(['user_id' => $user_id], ['stripe' => 1]);
    }

    public function preloadLessonMeetings($user_id, $invited_by, $group_id, $usergroup)
    {

        // preload meetings for this user 
        // $lessons = GroupLesson::where('group_id', $group_id)->get(); //Get all lessons
        $lessons = $usergroup->lessons();

        foreach ($lessons as $key => $lesson) {
            $gclm = GroupCoachingLessonMeeting::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->id,
                    'invited_by' => $invited_by
                ],
                [
                    'meeting_time' => $lesson->created_at,
                    'time_zone' => $usergroup->time_zone,
                    'meeting_url' => $usergroup->meeting_url,
                    'coach_notes' => '',
                    'coach_action_steps' => '',
                    'close_lesson' => 0,
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->created_at
                ]
            );
            
            $gclms = new GroupCoachingLessonMeetingSetting;
            $gclms->lesson_meeting_id = $gclm->id;
            $gclms->save();
        }
    }

    private function sendFlashCoachingWelcomeEmail($user){
        
        $messages = [];

        $messages[] = "Congratulations on your subscription to the PAS Flash Coaching module.";
        $messages[] = "To access it, log out of your software, do a hard refresh (normally a Ctrl + Shift + R ... and hold it for 3 seconds), then log back in again. You'll see the Flash Coaching Portal on the left side as a drop down of the Coaching Portal >. It looks like this:";
        $messages[] = "<img src='https://cdn.profitaccelerationsoftware.com/images/flash_image.png' alt='Flash Coaching Menu'>";
        $messages[] = "You can click on the Flash Coaching Link and access it there. You can also watch the Flash Coaching Training in the same drop down menu.";
        $messages[] = "Remember that Toria's group calls are every Wednesday at 2pm Pacific and you can ask her any question about Flash Coaching there. You can register for her weekly call at this link: <a target='_blank' href='https://focused.com/flash-coaching-mastermind-registration'>https://focused.com/flash-coaching-mastermind-registration</a>.";
        $messages[] = "If you have a technical question, please send it to <a href='mailto:support@focused.com'>support@focused.com</a>.";
        $messages[] = "All the best,";

        $notice = [
            'client_name' => $user->first_name,
            'messages' => $messages,
            'to' => $user->email,
            'copy' => [],
            'bcopy' => [],
            'subject' => 'Flash Coaching software in your PAS',
        ];

        ProcessEmail::dispatch($notice, 'general');
    }

    /**
     * Update the training specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function changeTrainings(Request $request, $id)
    {

        try {

            $user = User::findOrFail($id);

            $rules = [
                'training_software' => 'required',
                'training_100k' => 'required',
                'training_lead_gen' => 'required',
                'group_coaching' => 'required',
                'prep_roleplay' => 'required',
                'training_jumpstart' => 'required',
                'flash_coaching' => 'required',
                'lead_generation' => 'required',
                'licensee_advisor' => 'required',
                'quotum_access' => 'required',
                'simulator' => 'required',
            ];

            $messages = [
                'training_software.required' => 'Software training is required',
                'training_100k.required' => '100k training is required',
                'training_lead_gen.required' => 'Lead generation training is required',
                'group_coaching.required' => 'Group coaching is required',
                'prep_roleplay.required' => 'Roleplay Prep training is required',
                'training_jumpstart.required' => 'Flash coaching is required',
                'flash_coaching.required' => 'Flash coaching is required',
                'lead_generation.required' => 'Lead generation is required',
                'licensee_advisor.required' => 'Licensee Advisor is required',
                'quotum_access.required' => 'Quotum access is required',
                'simulator.required' => 'Simulator is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $training_user = TrainingAccess::where('user_id','=', $user->id)->first();
            $date = Carbon::now();

            if ((int)$request->input('training_software') != $training_user->training_software) {
                $access = '';
                if ((int)$request->input('training_software') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Software Training',
                    'description' => 'Software Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('training_100k') != $training_user->training_100k) {
                $access = '';
                if ((int)$request->input('training_100k') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => '100k Training',
                    'description' => '100k Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('training_lead_gen') != $training_user->training_lead_gen) {
                $access = '';
                if ((int)$request->input('training_lead_gen') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Lead Gen Training',
                    'description' => 'Lead Gen Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('group_coaching') != $training_user->group_coaching) {
                $access = '';
                if ((int)$request->input('group_coaching') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Group Coaching',
                    'description' => 'Group Coaching access has been '.$access.' by '.$this->user->first_name.' '.$this->user->first_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('prep_roleplay') != $training_user->prep_roleplay) {
                $access = '';
                if ((int)$request->input('prep_roleplay') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Prep Roleplay',
                    'description' => 'Prep Roleplay access has been '.$access.' by '.$this->user->first_name.' '.$this->user->first_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('training_jumpstart') != $training_user->training_jumpstart) {
                $access = '';
                if ((int)$request->input('training_jumpstart') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Jumpstart Training',
                    'description' => 'Jumpstart Training access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('flash_coaching') !== (int)$training_user->flash_coaching) {
                $access = '';
                if ((int)$request->input('flash_coaching') === 1) {
                    $access = 'Granted';
                    // Send the welcome Email
                    $this->sendFlashCoachingWelcomeEmail($user);
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Flash Coaching',
                    'description' => 'Flash Coaching access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('lead_generation') !== (int)$training_user->lead_generation) {
                $access = '';
                if ((int)$request->input('lead_generation') === 1) {
                    $access = 'Granted';
                    // Send the welcome Email
                    // $this->sendFlashCoachingWelcomeEmail($user);
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Lead Generation',
                    'description' => 'Lead Generation access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            if ((int)$request->input('quotum_access') !== (int)$training_user->quotum_access) {
                $access = '';
                if ((int)$request->input('quotum_access') === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Quotum Access',
                    'description' => 'Quotum access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            $array = [
                'training_software' => (int)$request->input('training_software'),
                'training_100k' => (int)$request->input('training_100k'),
                'training_lead_gen' => (int)$request->input('training_lead_gen'),
                'group_coaching' => (int)$request->input('group_coaching'),
                'prep_roleplay' => (int)$request->input('prep_roleplay'),
                'training_jumpstart' => (int)$request->input('training_jumpstart'),
                'flash_coaching' => (int)$request->input('flash_coaching'),
                'lead_generation' => (int)$request->input('lead_generation'),
                'licensee_advisor' => (int)$request->input('licensee_advisor'),
                'quotum_access' => (int)$request->input('quotum_access'),
                'simulator' => (int)$request->input('simulator'),
            ];

            $user->trainingAccess()->updateOrCreate(['user_id' => $id], $array);
            $groups = Group::all();

            if ((int)$request->input('group_coaching') == 1) {
                $user_group_temp = UserGroupTemplate::whereUserId($user->id)->get();
                if (!$request->input('groups') && sizeof($user_group_temp) === 0) {
                    foreach ($groups as $key => $item) {
                        $group_id = $item['id'];
                        UserGroupTemplate::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'group_id' => $group_id,
                            ],
                            []
                        );

                    }
                } else {
                    $groups = $request->input('groups');
                    if ($groups) {
                        foreach ($groups as $key => $item) {
                            $group_id = $item['id'];
                            $access = (int)$item['access'];
                            $group = Group::findOrFail($group_id);
                            if ($access > 0) {
                                $access = 'Granted';
                                $user_group_template = UserGroupTemplate::where('user_id', $id)->where('group_id', $group_id)->first();
                                if (!isset($user_group_template)) {
                                    UserModuleAccessMetaData::create([
                                        'user_id' => $user->id,
                                        'module_name' => $group->title,
                                        'description' => $group->title.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                                        'changed_by' => $this->user->id,
                                        'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                                    ]);
                                }
                                UserGroupTemplate::updateOrCreate(
                                    [
                                        'user_id' => $user->id,
                                        'group_id' => $group->id,
                                    ],
                                    []
                                );
                            } else {
                                UserGroupTemplate::where('user_id', $id)->where('group_id', $group_id)->delete();
                                $access = 'Revoked';
                                UserModuleAccessMetaData::updateOrCreate([
                                    'user_id' => $user->id,
                                    'module_name' => $group->title,
                                    'description' => $group->title.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                                    'changed_by' => $this->user->id,
                                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                                ],[]
                            );
                            }
                        }
                    }
                }
            } 
            if(!(!!$user->trainingAccess->simulator)){
                //disable token
                $user_sim = UserSimulator::where('user_id', $id);
                $user_sim->delete();
            } else {
                // Restore token
                $deleted_user_sim = UserSimulator::withTrashed()->where('user_id', $id)->first();

                if ($deleted_user_sim) {
                    $deleted_user_sim->restore();
                }

            }
            // Revoke current user token
            $user->tokens()->delete();

            $transform = new TrainingResource($user->trainingAccess);
            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Update the onboarding status for the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function changeOnboarding(Request $request)
    {

        try {

            $rules = [
                'onboarding' => 'required',
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'onboarding.required' => 'Software training is required',
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $date = Carbon::now();
            $user = User::findOrFail($request->user_id);

            if ((int)$request->onboarding != $user->onboarding) {
                $access = '';
                if ((int)$request->onboarding === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Software Onboarding',
                    'description' => 'Software Onboarding access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            $user->onboarding = (int)$request->onboarding;
            $user->save();

            // Revoke current user token
            $user->tokens()->delete();

            return $this->singleMessage('User onboarding status changed successfully', 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }


    /**
     * Update user show_tour in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function toggleShowTour(Request $request, $id)
    {

        try {

            $user = User::findOrFail($id);

            $rules = [
                'show_tour' => 'required|boolean',
            ];

            $messages = [
                'show_tour.required' => 'The show tour value is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user->show_tour = (int)trim($request->input('show_tour'));

            if ($user->isDirty()) {
                $user->save();
            }

            $transform = new UserResource($user);
            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Update user prospects_notify in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function toggleProspectsNotify(Request $request, $id)
    {

        try {

            $user = User::findOrFail($id);

            $rules = [
                'prospects_notify' => 'required|boolean',
            ];

            $messages = [
                'prospects_notify.required' => 'The prospects notify value is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user->prospects_notify = (int)trim($request->input('prospects_notify'));

            if ($user->isDirty()) {
                $user->save();
            }

            $transform = new UserResource($user);
            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
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
            $user = User::findOrFail($id);
            

            // $user->roles()->detach();

            // DB::table('company_user')->where('user_id', $id)->delete(); //Delete company_user associated
            // DB::table('assessment_user')->where('user_id', $id)->delete(); //Delete company_user associated
            // DB::table('module_set_user')->where('user_id', $id)->delete(); //Delete company_user associated
            // DB::table('role_users')->where('user_id', $id)->delete(); //Delete role_user associated
            // DB::table('credits')->where('user_id', $id)->delete(); //Delete credits associated
            // DB::table('login_tracker')->where('user_id', $id)->delete(); //Delete login tracker associated
            // DB::table('prospects')->where('user_id', $id)->delete(); //Delete prospects associated
            // DB::table('training_user')->where('user_id', $id)->delete(); //Delete training_user associated
            // DB::table('user_training_analysis')->where('user_id', $id)->delete(); //Delete user_training_analysis associated
            // DB::table('group_coaching_analytics')->where('user_id', $id)->delete(); //Delete group_coaching_analytics associated
            // DB::table('member_group_lesson')->where('user_id', $id)->delete(); //Delete member_group_lesson associated
            // DB::table('user_groups')->where('user_id', $id)->delete(); //Delete user_groups associated
            // DB::table('gc_lesson_meetings')->where('user_id', $id)->delete(); //Delete gc_lesson_meetings associated

            $user->delete(); //Delete the user
            return $this->singleMessage('User Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    /**
     * Get single users by company name
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $name
     * @return \Illuminate\Http\Response
     */

    public function companyUsers(Request $request)
    {

        $owner = $request->input('owner');

        try {
            $users = User::where([['owner', '=', $owner], ['role_id', '<>', 4]])->get()->sortByDesc('id');

            $transform = SimpleUserResource::collection($users);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Users do not exist', 404);
        }
    }


    /**
     * Notify admin about a new user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object $current_user & new_user
     * @return \Illuminate\Http\Response
     */

    public function notifyAdmin(Request $request)
    {
        try {
            $notice = $request;
            Notification::route('mail', env('EMAIL_SUPPORT_TO'))->notify(new NotifyAdmin($notice));
            return $this->showMessage('Admin successfully notified about the new user', 200);
        } catch (Exception $ex) {

            return $this->errorResponse('Error occured while trying to notify admin via email: ' . $ex->getMessage(), 400);
        }
    }


    /**
     * get user assessment by user id
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */

    public function assessmentsByUserId(Request $request, $user_id)
    {

        try {

            $user = User::findOrFail($user_id);

            $transform = AssessmentResource::collection($user->assessments()->get());

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('User not found', 404);
        }
    }

    /**
     * edit user assessment by user id
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $user_id
     * @param  int $assessment_id
     * @return \Illuminate\Http\Response
     */

    public function editAssessmentPermissions(Request $request, $assessment_id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'view_rights' => 'required|boolean',
                'edit_rights' => 'required|boolean',
                'report_rights' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            $user = User::findOrFail($request->user_id);

            if (!$request->user()->roleGreaterThan($user->role->name) && $request->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this user', 201);
            }

            $user->assessments()->detach($assessment_id);

            $user->assessments()->attach($assessment_id, [
                'view_rights' => $request->input('view_rights'),
                'edit_rights' => $request->input('edit_rights'),
                'report_rights' => $request->input('report_rights'),
            ]);

            // $transform = $user->assessments()->get(); all user assessments by the specific $user->id

            return $this->singleMessage('Permissions Edited', 201);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    /**
     * remove user assessment by user id
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $user_id
     * @param  int $assessment_id
     * @return \Illuminate\Http\Response
     */

    public function detachUserAssessment(Request $request, $assessment_id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::findOrFail($request->user_id);

            if (!$request->user()->roleGreaterThan($user->role->name) && $request->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this user', 201);
            }

            $user->assessments()->detach($assessment_id);

            return $this->singleMessage('Assessment has been detached from this user', 200);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    /**
     * remove user moduleset by moduleset id
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $user_id
     * @param  int $moduleset_id
     * @return \Illuminate\Http\Response
     */

    public function detachUserModuleset(Request $request, $moduleset_id)
    {

        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user = User::findOrFail($request->user_id);

            if (!$request->user()->roleGreaterThan($user->role->name) && $request->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this user', 201);
            }

            $user->module_sets()->detach($moduleset_id);
            $module_set = ModuleSet::find($moduleset_id);

            $date = Carbon::now();

            $access = 'Revoked';
            UserModuleAccessMetaData::create([
                'user_id' => $user->id,
                'module_name' => $module_set->alias,
                'description' => $module_set->alias.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                'changed_by' => $this->user->id,
                'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
            ]);

            return $this->singleMessage('Moduleset has been detached successfully', 200);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    public function sendUserEmail(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->input('user_id');

            $user = User::find($user_id);

            $password = (string)rand(1000000, 9999999);
            $user->password = $password;
            $user->save();
            $user->notify(new RemindUser($user, $password));
            return $this->singleMessage('Email with new a new password has been sent to ' . $user->first_name . ' successfully.', 200);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    public function addModuleSet(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'module_set_id' => 'required|exists:module_sets,id',
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request->input('user_id');

            $user = User::find($user_id);


            if (!$user->roleGreaterThan($user->role->name) && $user->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this', 401);
            }

            $user->module_sets()->syncWithoutDetaching($request->input('module_set_id'));

            $module_set = ModuleSet::find($request->input('module_set_id'));

            $date = Carbon::now();
            $access = 'Granted';
            UserModuleAccessMetaData::create([
                'user_id' => $user->id,
                'module_name' => $module_set->alias,
                'description' => $module_set->alias.' access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                'changed_by' => $this->user->id,
                'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
            ]);

            $transform = $user->module_sets()->get();

            return $this->showMessage($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    public function deleteModuleSet(Request $request, $user_id, $module_set_id)
    {
        try {

            $user = User::find($user_id);
            $module_set = ModuleSet::find($module_set_id);

            if (!$user->roleGreaterThan($user->role->name) && $user->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this', 401);
            }

            $user->module_sets()->detach($module_set_id);
            
            return $this->singleMessage('Moduleset Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    public function toggleCompanyPermissions(Request $request, $user_id, $company_id)
    {
        try {

            $user = User::find($user_id);

            if (!$user->roleGreaterThan($user->role->name) && $user->role->name != 'Super Administrator') {
                return $this->singleMessage('You are not authorized to edit this', 401);
            }

            if ($user->companies->contains($company_id)) {
                $user->companies()->detach($company_id);
            } else {
                $user->companies()->attach($company_id);
            }

            return $this->singleMessage('Company Permissions Edited', 201);
        }
        // catch(Exception $e) catch any exception
        catch (Exception $e) {
            return $this->errorResponse('User does not exist.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delGroupMember(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'user_group_id' => 'required|exists:user_groups,id'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            DB::table('group_coaching_analytics')->where('user_id', $request->user_id)->where('user_group_id', $request->user_group_id)->delete(); //Delete group_coaching_analytics associated
            DB::table('member_group_lesson')->where('user_id', $request->user_id)->where('group_id', $request->user_group_id)->delete(); //Delete member_group_lesson associated
            DB::table('gc_lesson_meetings')->where('user_id', $request->user_id)->where('group_id', $request->user_group_id)->delete(); //Delete gc_lesson_meetings associated

            return $this->singleMessage('Member was data Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('Member was not found', 400);
        }
    }

    /**
     * Save a new custom group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addCustomGroup(Request $request)
    {

        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $info = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $user_id = $request->user_id;
                $group_id = $request->group_id; //Get custom group ID

                $group = new UserGroup;
                $group->name = trim($request->name);
                $group->user_id = $user_id;
                $group->group_id = $group_id;
                $group->meeting_day = $request->meeting_day;
                $group->meeting_time = $request->meeting_time;
                $group->time_zone = $request->time_zone;
                $group->meeting_url = trim($request->meeting_url);
                $group->price = $request->price;
                $group->save();

                //Create custom group lessons
                $this->createCustomGroupLesson($request->lessons, $group);
                //Add user to custom group 
                $this->addUserToCustomGroup($user_id, $group_id, $group);

                $this->preloadCustomGroupLessonMeetings($user_id, $user_id, $group_id, $group);

                $transform = new UserGroupResource($group);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group template not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to get user group templates', 400);
        }
    }

    /**
     * Save a new group_lesson
     *
     * @param  $request
     * @return \Illuminate\Http\Response
     */
    public function createCustomGroupLesson($lessons, $group)
    {

        try {
            $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $group->meeting_time);
            $start_date->setTimezone('UTC');

            if ($lessons && sizeof($lessons) > 0) {

                foreach ($lessons as $key => $lesson) {

                    $group_lesson = new CustomGroupLesson;
                    $group_lesson->lesson_id = $lesson['lesson_id'];
                    $group_lesson->user_group_id = $group->id;
                    $group_lesson->group_id = $group->group_id;
                    $group_lesson->lesson_order = $key;
                    $group_lesson->lesson_length = (int)$lesson['lesson_length'];
                    $group_lesson->created_at = $start_date;
                    $group_lesson->save();

                    //created & updated including length of lesson

                    $start_date->addWeeks((int)$lesson['lesson_length']);
                }

                return $this->showMessage($group_lesson, 200);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to create lessons for this template', 400);
        }
    }

    public function addUserToCustomGroup($user_id, $group_id, $usergroup)
    {

        // add user to the group coaching
        $lessons = CustomGroupLesson::where('group_id', $group_id)->where('user_group_id', $usergroup->id)->orderBy('lesson_order')->get(); //Get all lessons

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
        $date->setTimezone('UTC');

        foreach ($lessons as $key => $lesson) {
            $mgl = MemberGroupLesson::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->lesson_id,
                    'invited_by' => $this->user->id,
                    'lesson_order' => $lesson->lesson_order,
                    'lesson_length' => $lesson->lesson_length
                ],
                ['created_at' => $lesson->created_at, 'updated_at' => $lesson->updated_at]
            );
        }
    }

    public function preloadCustomGroupLessonMeetings($user_id, $invited_by, $group_id, $usergroup)
    {

        // preload meetings for this user 
        $lessons = CustomGroupLesson::where('group_id', $group_id)->where('user_group_id', $usergroup->id)->orderBy('lesson_order')->get(); //Get all lessons

        foreach ($lessons as $key => $lesson) {
            $gclm = GroupCoachingLessonMeeting::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->lesson_id,
                    'invited_by' => $invited_by
                ],
                [
                    'lesson_order' => $lesson->lesson_order,
                    'meeting_time' => $lesson->created_at,
                    'time_zone' => $usergroup->time_zone,
                    'meeting_url' => $usergroup->meeting_url,
                    'coach_notes' => '',
                    'coach_action_steps' => '',
                    'close_lesson' => 0,
                    'created_at' => $lesson->created_at,
                    'updated_at' => $lesson->updated_at
                ]
            );
            $gclms = new GroupCoachingLessonMeetingSetting;
            $gclms->lesson_meeting_id = $gclm->id;
            $gclms->save();
        }
    }

    public function updateCustomGroupLessons($usergroup, $user_group_id, $next_meeting_time, $status)
    {

        // Get all next lessons
        $all = ($status)? $usergroup->lessons() : $usergroup->allnextlessons();

        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $next_meeting_time);
        $start_date->setTimezone('UTC');

        $lessons = CustomGroupLesson::hydrate($all->toArray());

        foreach ($lessons as $each => $lesson) {

            $lesson->created_at = $start_date;
            $lesson->updated_at = $start_date;
            $lesson->save();

            $start_date->addWeeks($lesson->lesson_length);

        }
    }

    public function updateUserCustomGroupMembers($usergroup, $user_group_id, $next_meeting_time, $status, $lesson_id)
    {

        // Get all next lessons
        $all = ($status)? $usergroup->lessons() : $usergroup->allnextlessons();
        
        $lesson_ids = array_column($all->toArray(), 'lesson_id');

        if($lesson_id){
            array_unshift($lesson_ids, (int)$lesson_id);
        }

        // update user to the group coaching
        $records = DB::table('member_group_lesson')
            ->select('*')
            ->where('member_group_lesson.group_id', $usergroup->id)
            ->whereIn('member_group_lesson.lesson_id', $lesson_ids)
            ->orderBy('member_group_lesson.lesson_order')->get(); //Get all user lessons

        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $next_meeting_time);
        $start_date->setTimezone('UTC');

        $lessons = MemberGroupLesson::hydrate($records->toArray());

        foreach ($lessons as $each => $lesson) {

            $custom_group_lesson = CustomGroupLesson::where('user_group_id', $user_group_id)->where('lesson_id', $lesson->lesson_id)->first();

            $lesson->created_at = $start_date;
            $lesson->updated_at = $start_date;
            $lesson->lesson_length = $custom_group_lesson->lesson_length;
            $lesson->lesson_order = $custom_group_lesson->lesson_order;
            $lesson->save();

            //retreive & updated including using length of lesson

            $next = $each + 1;
            if ($next < sizeof($lessons)) {
                if ($lessons[$next]->lesson_id != $lessons[$each]->lesson_id) {
                   $start_date->addWeeks($custom_group_lesson->lesson_length); 
                }
            }

        }
    }

    public function updatePreloadCustomGroupLessonMeetings($usergroup, $user_group_id, $next_meeting_time, $status, $lesson_id)
    {

        // Get all next lessons
        $all = ($status)? $usergroup->lessons() : $usergroup->allnextlessons();

        $lesson_ids = array_column($all->toArray(), 'lesson_id');

        if($lesson_id){
            array_unshift($lesson_ids, (int)$lesson_id);
        }
        
        // preload meetings for this user 
        
        $records = DB::table('gc_lesson_meetings')
                    ->select('*')
                    ->where('gc_lesson_meetings.group_id', $usergroup->id)
                    ->whereIn('gc_lesson_meetings.lesson_id', $lesson_ids)
                    ->orderBy('gc_lesson_meetings.lesson_order')->get(); //Get all user lessons

        $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $next_meeting_time);
        $start_date->setTimezone('UTC');

        $lessons = GroupCoachingLessonMeeting::hydrate($records->toArray());

        foreach ($lessons as $each => $lesson) {
            $custom_group_lesson = CustomGroupLesson::where('user_group_id', $user_group_id)->where('lesson_id', $lesson->lesson_id)->first();

            $lesson->time_zone = $usergroup->time_zone;
            $lesson->meeting_url = $usergroup->meeting_url;
            $lesson->lesson_order = $custom_group_lesson->lesson_order;
            $lesson->meeting_time = $start_date;
            $lesson->created_at = $start_date;
            $lesson->save();

            //retreive & updated including using length of lesson

            $next = $each + 1;
            if ($next < sizeof($lessons)) {
                if ($lessons[$next]->lesson_id != $lessons[$each]->lesson_id) {
                    $start_date->addWeeks($custom_group_lesson->lesson_length);
                }
            }
            
        }
    }

    /**
     * Create the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateprofilephoto(Request $request)
    {
        try {
            $rules = [
                'id' => 'required|exists:users,id',
                'file' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
            ];

            $cypher = new Cypher();
            $id = $cypher->decryptID(env('HASHING_SALT'), $request->input('id'));
            $id = intval($id);

            $data = $request->all();
            $data['id'] = $id;
            $messages = [
                'id.required' => 'The user ID is required',
                'id.exists' => 'The user does not exists',
                'file.required' => 'Profile picture is required'
            ];

            $validator = Validator::make($data, $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            if ($request->hasFile('file')) {

                $file = $request->file('file');
                $s3 = \AWS::createClient('s3');
                $user_id = $id;
                $key = $user_id . '.' . $file->getClientOriginalExtension();
                $uploadfile = $s3->putObject([
                    'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                    'Key'        => 'profile_pictures/' . $key,
                    'ACL'          => 'public-read',
                    'ContentType' => $file->getMimeType(),
                    'SourceFile' => $file,
                ]);

                $url = $uploadfile->get('ObjectURL');
                
                $user = User::findOrfail($user_id);
                $user->profile_pic = $url;
                $user->save();
            }

            $transform = new UserResource($user);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('That user was not found', 400);
        }
    }

    public function updateOnboarding(Request $request, $id)
    {

        try {

            $user = User::findOrFail($id);

            if ($request->has('onboarding_status')) {
                $user->onboarding_status = $request->input('onboarding_status');
            }

            if ($request->has('business_onboarding_status')) {
                $user->business_onboarding_status = $request->input('business_onboarding_status');
            }

           

            if ($user->isDirty()) {
                $user->save();
            }

            $transform = new UserMiniResource($user);
            return $this->successResponse($transform, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    
    /**
     * Update the onboarding status for the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changeLicensee(Request $request)
    {

        try {

            $rules = [
                'licensee' => 'required',
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'licensee.required' => 'Status is required',
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $date = Carbon::now();
            $user = User::findOrFail($request->user_id);

            if ((int)$request->licensee != $user->licensee_access) {
                $access = '';
                if ((int)$request->licensee === 1) {
                    $access = 'Granted';
                }else{
                    $access = 'Revoked';
                }
                UserModuleAccessMetaData::create([
                    'user_id' => $user->id,
                    'module_name' => 'Licensee Access',
                    'description' => 'Licensee access has been '.$access.' by '.$this->user->first_name.' '.$this->user->last_name, 
                    'changed_by' => $this->user->id,
                    'changed_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date)
                ]);
            }

            $array = [
                'licensee_onboarding' => (int)$request->input('licensee'),
            ];

            $user->trainingAccess()->updateOrCreate(['user_id' => $user->id], $array);

            // Revoke current user token
            $user->tokens()->delete();

            $transform = new TrainingResource($user->trainingAccess);
            return $this->successResponse($transform, 200);

            return $this->singleMessage('User licensee status changed successfully', 201);
        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }


    /**
     * Query Highrise
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getHighRiseInfo(Request $request)
    {

        try {
            $rules = [
                'name' => 'required|string',
            ];

            $messages = [
                'name.required' => 'Name is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $email = $request->email;
            $name = trim($request->name);

            $KEY = env('HIGHRISE_KEY', 'cb90d6813348cb4c8d87f8c6680bd26f');
            $URL = env('HIGHRISE_URL', 'https://leaderpublishingworldwide1.highrisehq.com');

            if(empty($request->email)){
                $response = Http::withBasicAuth($KEY, 'X')->get($URL .'/people/search.xml', [
                    'term' => $name,
                ]);
            }else{
                $response = Http::withBasicAuth($KEY, 'X')->get($URL .'/people/search.xml', [
                    'term' => $name,
                    'criteria[email]' => $email,
                ]);
            }

            if($response->successful()){
                return $this->successXMLResponse($response, 200);
            }else{
                return $this->errorXMLResponse('Error searching Highrise. User not found', 400);
            }
        }catch (ModelNotFoundException $e) {
            return $this->errorXMLResponse('Error searching Highrise.', 400);
        }
    }


    /**
     * Query User Account Logs
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserAccountLogs(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required|exists:users,id',
            ];

            $messages = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'That user does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $user_id = $request['user_id'];

            $user_module_Access_meta_data = UserModuleAccessMetaData::where('user_id', $user_id)->orderBy('id')->get();
            $user_changes_meta_data = UserChangesMetaData::where('user_id', $user_id)->orderBy('id')->get();
            $user_password_changes_meta_data = UserPasswordChangesMetaData::where('user_id', $user_id)->orderBy('id')->get();

            $transform1 = UserModuleAccessMetaDataResource::collection($user_module_Access_meta_data);
            $transform2 = UserChangesMetaDataResource::collection($user_changes_meta_data);
            $transform3 = UserPasswordChangesMetaDataResource::collection($user_password_changes_meta_data);
            $transform = [
                'module_access' => $transform1, 
                'user_changes' => $transform2,
                'user_password_changes' => $transform3,
            ];            
            return $this->successResponse($transform, 200);


        }catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }

    }

    function updateStatus($user, $request, $change_description = '', $date)
    {
        try {
            if ($request->input('status')) {
                $previousStatus = UserStatus::findOrFail($user->user_status_id);

                $currentStatus = UserStatus::findOrFail($request->input('status'));

                if ($previousStatus->name == 'Inactive' && $currentStatus->name == 'Active') {
                    $statusName = 'Reactivated';
                } else {
                    $statusName = $currentStatus->name;
                }

                if ($previousStatus->name !== $currentStatus->name) {
                    UserChangesMetaData::create([
                        'user_id' => $user->id,
                        'current_value' => $previousStatus->name,
                        'new_value' => $statusName,
                        'description' => $change_description,
                        'column_name' => 'status',
                        'changed_by' => $this->user->id,
                        'changed_date' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                }

                $statustoSave = UserStatus::where('name', $statusName)->first();

                if ($statustoSave->name == 'Inactive') {
                    UserCancelationMetaData::create([
                        'user_id' => $user->id,
                        'canceled_by' => $this->user->id,
                        'canceled_at' => Carbon::createFromFormat('Y-m-d H:i:s', $date),
                    ]);
                    $user->invalidateTokens();
                }

                $user->user_status_id = $statustoSave->id;

            }
        } catch (Exception $e) {
            $this->errorResponse(500, json_encode($e));
        }
    }
}
