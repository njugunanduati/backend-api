<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\MailBox;
use App\Models\UserGroup;
use App\Models\GroupLesson;
use App\Models\MemberGroupLesson;
use App\Models\GroupCoachingPausedSession;
use App\Models\CustomGroupLesson;
use App\Models\GroupCoachingLessonMeeting;
use App\Models\GroupCoachingLessonMeetingSetting;
use App\Models\UserGroupTemplate;
use App\Http\Resources\MemberGroupLesson as MemberGroupLessonResource;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Jobs\ProcessEmail;

use App\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MemberController extends Controller
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
     * Update all the custom group lessons to add lesson_order in gc_lesson_meetings table
     * ---> This was a utility function that was needed to correct data in gc_lesson_meetings table
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function updategclm()
    {
        $usergroups = UserGroup::select(['id'])->where('group_id', 29)->get();
        
        foreach ($usergroups as $key => $group) {
            $lessons = CustomGroupLesson::where('user_group_id', $group->id)->orderBy('lesson_order')->get();
            
            foreach ($lessons as $k => $lesson) {
                $mgls = GroupCoachingLessonMeeting::where('group_id', $group->id)->where('lesson_id', $lesson->lesson_id)->update(['lesson_order' => $lesson->lesson_order]);
            }
        
        }
    }


    /**
     * Update / or create custom group lessons for all DRAFT Groups
     * ---> This was a utility function that was needed to correct data in custom_group_lessons table
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function updatecgl()
    {
        $groups = Group::select(['id'])->where('status', 'DRAFT')->get();

        $usergroups = [];
        foreach ($groups as $key => $group) {
            $ug = UserGroup::select(['id', 'user_id', 'group_id'])->where('group_id', $group->id)->first();
            if($ug){
                $usergroups[] = $ug;
            }
        }

        foreach ($usergroups as $key => $u) {

            $mgl = MemberGroupLesson::where('group_id', $u->id)->where('user_id', $u->user_id)->groupBy('lesson_id')->orderBy('lesson_order')->get();

            if(count($mgl) > 0){
               
                foreach ($mgl as $key => $lesson) {
                    CustomGroupLesson::updateOrCreate(
                        [
                            'group_id' => $u->group_id,
                            'user_group_id' => $u->id,
                            'lesson_id' => $lesson->lesson_id,
                            'lesson_order' => $lesson->lesson_order,
                            'lesson_length' => $lesson->lesson_length
                        ],
                        ['created_at' => $lesson->created_at, 'updated_at' => $lesson->created_at]
                    );
                }
            }
        }
        
        return $this->showMessage('Updated custom group lessons successfully', 200);
    }


    /**
     * Update all the custom group lessons to add lesson_order in member_group_lesson table
     * ---> This was a utility function that was needed to correct data in member_group_lesson table
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatemgls()
    {
        $usergroups = UserGroup::select(['id'])->where('group_id', 29)->get();
        
        foreach ($usergroups as $key => $group) {
            $lessons = CustomGroupLesson::where('user_group_id', $group->id)->orderBy('lesson_order')->get();
            
            foreach ($lessons as $k => $lesson) {
                $mgls = MemberGroupLesson::where('group_id', $group->id)->where('lesson_id', $lesson->lesson_id)->update(['lesson_order' => $lesson->lesson_order]);
            }
        
        }
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
    }

    /**
     * Store a newly created member.
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
                'email' => 'required|email',
                'invited_by' => 'required|exists:users,id',
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $messages = [
                'first_name.required' => 'The first name is required',
                'last_name.required' => 'The last name is required',
                'email.required' => 'Email is required',
                'email.email' => 'That is not a valid email address',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_id = $request->group_id;
            $user_group_id = $request->user_group_id;
            $invited_by = $request->invited_by;

            $check_user = User::where('email', trim($request->email))->first();
            $invitor = User::findOrFail($invited_by);
            $group = Group::findOrFail($group_id);
            $usergroup = UserGroup::findOrFail($user_group_id);

            $password = (string)rand(1000000, 9999999);

            // New student/user to PAS
            if (!$check_user) {

                $user = new User;

                $user->first_name = trim($request->input('first_name'));
                $user->last_name = trim($request->input('last_name'));
                $user->email = trim($request->input('email'));
                $user->password = $password;
                $user->role_id = 10;
                $user->created_by_id = ($this->user->id) ? $this->user->id : null;

                $role = Role::findOrFail(10);

                $user->save();
                $user->assignRole($role);

                $array = ['group_coaching' => 1];
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

                // add member to the member_group_lesson
                // $lessons = GroupLesson::where('group_id', $group_id)->get();
                $lessons = $usergroup->lessons();

                if ($group_id == 29) {
                    $this->addUserToCustomGroup($user->id, $group_id, $usergroup);
                    $this->preloadCustomGroupLessonMeetings($user->id, $invited_by, $group_id, $usergroup);
                } else {
                    $this->addMemberToGroup($lessons, $user->id, $invited_by, $usergroup);
                    $this->preloadLessonMeetings($lessons, $user->id, $invited_by, $usergroup);
                }

                $title = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;

                $messages = [];
                $summary = [];
                $messages[] = 'Welcome to my ' . $title;
                $messages[] = 'Here are your account details,';
                $messages[] = 'Username - <b>' . $user->email . '</b>';
                $messages[] = 'Password - <b>' . $password . '</b>';
                $summary[] = 'If you did not request access to this group, no further action is required';

                $notice = [
                    'user' => $invitor,
                    'student_name' => $user->first_name,
                    'to' => trim($user->email),
                    'messages' => $messages,
                    'summary' => $summary,
                    'subject' => 'Group Coaching Account Details - [' . $title . ']',
                    'copy' => [$invitor->email], // copy the coach
                    'bcopy' => [],
                ];

                ProcessEmail::dispatch($notice, 'newstudent');

                // Add student to the mailing list
                $this->addStudentToMailingList($user, $user_group_id);

                return $this->showMessage('User has been added to this group successfully', 201);
            } else {

                // Check if the user is already a member of the group
                $user_id = $check_user->id;
                $student = MemberGroupLesson::where('user_id', $user_id)->where('group_id', $user_group_id)->first();

                if (!$student) {

                    $array = ['group_coaching' => 1];
                    $check_user->trainingAccess()->updateOrCreate(['user_id' => $check_user->id], $array);

                    // $lessons = GroupLesson::where('group_id', $group_id)->get();
                    $lessons = $usergroup->lessons();

                    if ($group_id == 29) {
                        $this->addUserToCustomGroup($user_id, $group_id, $usergroup);
                        $this->preloadCustomGroupLessonMeetings($user_id, $invited_by, $group_id, $usergroup);
                    } else {
                        $this->addMemberToGroup($lessons, $user_id, $invited_by, $usergroup);
                        $this->preloadLessonMeetings($lessons, $user_id, $invited_by, $usergroup);
                    }

                    $title = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;

                    $messages = [];
                    $summary = [];
                    $messages[] = 'Welcome to my ' . $title;
                    $messages[] = 'You can access the group via the link below.';
                    $summary[] = 'If you did not request access to this group, no further action is required';

                    $notice = [
                        'user' => $invitor,
                        'student_name' => $check_user->first_name,
                        'to' => trim($check_user->email),
                        'messages' => $messages,
                        'summary' => $summary,
                        'subject' => 'Group Coaching Account Details - [' . $title . ']',
                        'copy' => [$invitor->email],
                        'bcopy' => [],
                    ];

                    ProcessEmail::dispatch($notice, 'newstudent');

                    // Add student to the mailing list
                    $this->addStudentToMailingList($check_user, $user_group_id);

                    return $this->showMessage('User has been added to this group', 201);
                } else {
                    // Student is a member of this group
                    return $this->showMessage('That user is already a member of this group', 200);
                }
            }
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    public function addMemberToGroup($lessons, $id, $invited_by, $group)
    {
        if ($lessons && sizeof($lessons) > 0) {

            foreach ($lessons as $key => $lesson) {
                $mgl = MemberGroupLesson::updateOrCreate(
                    [
                        'user_id' => (int)$id,
                        'group_id' => $group->id,
                        'lesson_id' => $lesson->id,
                        'invited_by' => $invited_by
                    ],
                    ['created_at' => $lesson->created_at, 'updated_at' => $lesson->created_at]
                );
            }
        }
    }

    /**
     * Resume sessions for a list of group lessons
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function resumeSessions(Request $request)
    {

        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $messages = [
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user/coach doe not exist',
                'group_id.required' => 'The group id is required',
                'group_id.exists' => 'That group template does not exist',
                'user_group_id.required' => 'The user group id is required',
                'user_group_id.exists' => 'That user group does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_id = $request->group_id;
            $user_group_id = $request->user_group_id;
            $user_id = $request->user_id;

            $paused = GroupCoachingPausedSession::where('user_id', $user_id)->where('group_id', $user_group_id)->first();

            $lesson_id = $paused->lesson_id;
            $request->lesson_id = $paused->lesson_id;

            $group = Group::findOrFail($group_id);
            $usergroup = UserGroup::findOrFail($user_group_id);

            $name = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;
            $subject = $name . ' - sessions resumed';

            $usergroup = UserGroup::findOrFail($user_group_id);

            // Mark the user group as resumed 
            $usergroup->update(['paused' => 0]);

            // Resume all the lessons this group
            $this->resumeGroupLessons($user_group_id, $user_id, $lesson_id);

            // Resume all the email reminders from this group
            $this->resumeEmailReminders($request);

            // Notify all the students about the resume in this group 
            $this->notifyMembersAboutResume($request);

            return $this->showMessage($subject . ' successfully.', 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Lessons not found', 400);
        }
    }


    /**
     * Pause sessions for a list of group lessons
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function pauseSessions(Request $request)
    {

        try {

            $rules = [
                'start' => 'required|string:max:30',
                'end' => 'required|string:max:30',
                'user_id' => 'required|exists:users,id',
                'lesson_id' => 'required|exists:lessons,id',
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'timezone' => 'required|string',
            ];

            $messages = [
                'start.required' => 'The start date is required',
                'end.required' => 'The end date is required',
                'user_id.required' => 'The user id is required',
                'user_id.exists' => 'That user/coach doe not exist',
                'lesson_id.required' => 'The lesson id is required',
                'lesson_id.exists' => 'That lesson does not exist',
                'group_id.required' => 'The group id is required',
                'group_id.exists' => 'That group template does not exist',
                'user_group_id.required' => 'The user group id is required',
                'user_group_id.exists' => 'That user group does not exist',
                'timezone.required' => 'Timezone is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_id = $request->group_id;
            $user_group_id = $request->user_group_id;
            $lesson_id = $request->lesson_id;
            $user_id = $request->user_id;
            $start = $request->start;
            $end = $request->end;
            $time_zone = $request->timezone;

            $group = Group::findOrFail($group_id);
            $usergroup = UserGroup::findOrFail($user_group_id);
            $meeting_time = $usergroup->meeting_time;

            $name = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;
            $subject = $name . ' - sessions paused';

            $usergroup = UserGroup::findOrFail($user_group_id);

            // Pause the user group
            $usergroup->update(['paused' => 1]);

            // Pause end date    
            $end_date = Carbon::createFromDate($end);

            // Get the date of the next session
            $next_meeting_date = $this->getNextLessonDate($end_date, $meeting_time);

            $all = $usergroup->allnextlessons(); // all lessons that havent taken place

            if($usergroup->group_id == 29){

                // Get a slice of the lessons that need to be paused
                $lesson_ids = array_column($all->toArray(), 'lesson_id');
                $index = array_search($lesson_id, $lesson_ids);
                $list = array_slice($lesson_ids, $index);

                // Get the lessons in this group
                $lessons = MemberGroupLesson::where('group_id', $user_group_id)
                    ->where('invited_by', $user_id)
                    ->whereIn('lesson_id', $list)
                    ->orderBy('lesson_order')->get();


                $custom_lessons = CustomGroupLesson::where('user_group_id', $user_group_id)
                    ->whereIn('lesson_id', $list)
                    ->orderBy('lesson_order')->get();

                $this->updatePausedCustomGroupLessons($custom_lessons, $end_date, $meeting_time);

            }else{
                // Get the lessons in this group
                
                $lesson_ids = array_column($all->toArray(), 'id');
                $index = array_search($lesson_id, $lesson_ids);
                $list = array_slice($lesson_ids, $index);

                // Get the meeting reminders in this group
            
                $all = DB::table('member_group_lesson')
                    ->join('lessons', 'lessons.id', '=', 'member_group_lesson.lesson_id')
                    ->select('member_group_lesson.*')
                    ->where('member_group_lesson.group_id', $user_group_id)
                    ->where('member_group_lesson.invited_by', $user_id)
                    ->whereIn('member_group_lesson.lesson_id',$list)
                    ->orderBy('lessons.next_lesson')->get(); //Get all user lessons

                $lessons = MemberGroupLesson::hydrate($all->toArray());
            }

            
            // Mark the lessons as paused and update the dates
            $this->updatePausedGroupLessons($lessons, $end_date, $meeting_time);

            // Create a reference of the paused group for future use
            GroupCoachingPausedSession::updateOrCreate(
                [
                    'user_id' => (int)$user_id,
                    'group_id'  => (int)$user_group_id,
                ],
                ['lesson_id'  => (int)$lesson_id, 'start_date' => $start, 'end_date' => $end, 'time_zone' => $time_zone]
            );

            if($usergroup->group_id == 29){

                $lesson_ids = array_column($all->toArray(), 'lesson_id');
                $index = array_search($lesson_id, $lesson_ids);
                $list = array_slice($lesson_ids, $index);

                // Get the meeting reminders in this group
                $meetings = GroupCoachingLessonMeeting::where('group_id', $user_group_id)
                    ->where('invited_by', $user_id)
                    ->whereIn('lesson_id', $list)
                    ->orderBy('lesson_order')->get();
            }else{

                // Get the meeting reminders in this group
                
                $lesson_ids = array_column($all->toArray(), 'lesson_id'); 
                
                $index = array_search($lesson_id, $lesson_ids);
                $list = array_slice($lesson_ids, $index);

                // Get the meeting reminders in this group

                $records = DB::table('gc_lesson_meetings')
                    ->join('lessons', 'lessons.id', '=', 'gc_lesson_meetings.lesson_id')
                    ->select('gc_lesson_meetings.*')
                    ->where('gc_lesson_meetings.group_id', $user_group_id)
                    ->where('gc_lesson_meetings.invited_by', $user_id)
                    ->whereIn('gc_lesson_meetings.lesson_id', array_unique($list))
                    ->orderBy('lessons.next_lesson')->get(); //Get all user lessons meetings
                   

                $meetings = GroupCoachingLessonMeeting::hydrate($records->toArray());

            }

            // Pause the all the email reminders from this group
            $this->updatePausedMeetingReminders($meetings, $end_date, $meeting_time);

            // Notify all the students about the pause in this group 
            $this->notifyMembersAboutPause($request, $next_meeting_date);


            return $this->showMessage($subject . ' successfully.', 200);
            
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Lessons not found', 400);
        }
    }


    /**
     * Get the date of the next lesson
     */
    public function getNextLessonDate($start, $meeting_time)
    {

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $start);

        $meets_on = Carbon::createFromDate($meeting_time);
        $weekday = $meets_on->isoFormat('dddd');

        $time = strtotime($meeting_time);

        $date = $date->next($weekday);
        $date->setTime(date('H', $time), date('i', $time), date('s', $time));

        return $date->format('M dS Y');
    }


    /**
     * Pause all the lessons in the group and update the dates
     */
    public function updatePausedCustomGroupLessons($lessons, $end, $meeting_time)
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $end);

        $meets_on = Carbon::createFromDate($meeting_time);
        $weekday = $meets_on->isoFormat('dddd');

        $time = strtotime($meeting_time);
        $date->setTime(date('H', $time), date('i', $time), date('s', $time));

        foreach ($lessons as $each => $lesson) {

            $length = (int)$lesson->lesson_length;

            $sdate_weekday = $date->isoFormat('dddd');

            if ($sdate_weekday != $weekday) {

                $new_meets_on = $date->next($weekday);
                $new_meets_on->setTime(date('H', $time), date('i', $time), date('s', $time));

                $lesson->created_at = $new_meets_on;
                $lesson->updated_at = $new_meets_on;

            } else {
                $lesson->created_at = $date;
                $lesson->updated_at = $date;
            }

            $lesson->save();

            if ($length > 1) {
                $date = $date->addWeeks($length);
                $date->setTime(date('H', $time), date('i', $time), date('s', $time));
            } else {
                $date = $date->next($weekday);
                $date->setTime(date('H', $time), date('i', $time), date('s', $time));
            }
        }
    }



    /**
     * Pause all the lessons in the group and update the dates
     */
    public function updatePausedGroupLessons($lessons, $end, $meeting_time)
    {

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $end);

        $meets_on = Carbon::createFromDate($meeting_time);
        $weekday = $meets_on->isoFormat('dddd');

        $time = strtotime($meeting_time);
        $date->setTime(date('H', $time), date('i', $time), date('s', $time));

        foreach ($lessons as $each => $lesson) {

            $length = (int)$lesson->lesson_length;

            $sdate_weekday = $date->isoFormat('dddd');

            if ($sdate_weekday != $weekday) {

                $new_meets_on = $date->next($weekday);
                $new_meets_on->setTime(date('H', $time), date('i', $time), date('s', $time));

                $lesson->created_at = $new_meets_on;
                $lesson->updated_at = $new_meets_on;
                $lesson->lesson_paused = 1;
            } else {
                $lesson->created_at = $date;
                $lesson->updated_at = $date;
                $lesson->lesson_paused = 1;
            }

            $lesson->save();

            $next = $each + 1;
            if ($next < sizeof($lessons)) {
                if ($lessons[$next]->lesson_id != $lessons[$each]->lesson_id) {
                    if ($length > 1) {
                        $date = $date->addWeeks($length);
                        $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                    } else {
                        $date = $date->next($weekday);
                        $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                    }
                }
            }
        }
    }

    /**
     * Pause all the email reminders and update the dates
     */
    public function updatePausedMeetingReminders($meetings, $end, $meeting_time)
    {

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $end);

        $meets_on = Carbon::createFromDate($meeting_time);
        $weekday = $meets_on->isoFormat('dddd');

        $time = strtotime($meeting_time);
        $date->setTime(date('H', $time), date('i', $time), date('s', $time));

        foreach ($meetings as $each => $meeting) {

            $length = (int)$meeting->mgl()->lesson_length;

            $sdate_weekday = $date->isoFormat('dddd');

            if ($sdate_weekday != $weekday) {

                $new_meets_on = $date->next($weekday);
                $new_meets_on->setTime(date('H', $time), date('i', $time), date('s', $time));

                $meeting->meeting_time = $new_meets_on;
                $meeting->created_at = $new_meets_on;
                $meeting->lesson_paused = 1;
            } else {
                $meeting->meeting_time = $date;
                $meeting->created_at = $date;
                $meeting->lesson_paused = 1;
            }

            $meeting->save();

            $meeting->settings->update([
                'three_days_coach_reminder' => 0,
                'three_days_reminder' => 0,
                'one_day_reminder' => 0,
                'one_hour_reminder' => 0,
                'three_min_before_reminder' => 0,
                'ten_min_after_reminder' => 0,
                'one_day_after_reminder' => 0,
            ]);

            $next = $each + 1;
            if ($next < sizeof($meetings)) {
                if ($meetings[$next]->lesson_id != $meetings[$each]->lesson_id) {
                    if ($length > 1) {
                        $date = $date->addWeeks($length);
                        $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                    } else {
                        $date = $date->next($weekday);
                        $date->setTime(date('H', $time), date('i', $time), date('s', $time));
                    }
                }
            }
        }
    }


    /**
     * Resume all the group lessons
     */
    public function resumeGroupLessons($user_group_id, $user_id, $lesson_id)
    {
        
        $usergroup = UserGroup::findOrFail($user_group_id);

        $all = $usergroup->allnextlessons();

        if($usergroup->group_id == 29){

            $lesson_ids = array_column($all->toArray(), 'lesson_id');
            $index = array_search($lesson_id, $lesson_ids);
            $list = array_slice($lesson_ids, $index);
            
            // Get the lessons
            $lessons = MemberGroupLesson::where('group_id', $user_group_id)
                ->where('invited_by', $user_id)
                ->whereIn('lesson_id', $list)
                ->orderBy('lesson_order')->get();
        }else{
            // Get the lessons
            
            $lesson_ids = array_column($all->toArray(), 'id');
            $index = array_search($lesson_id, $lesson_ids);
            $list = array_slice($lesson_ids, $index);

            // Get the meeting reminders in this group
            
            $all = DB::table('member_group_lesson')
                    ->join('lessons', 'lessons.id', '=', 'member_group_lesson.lesson_id')
                    ->select('member_group_lesson.*')
                    ->where('member_group_lesson.group_id', $user_group_id)
                    ->where('member_group_lesson.invited_by', $user_id)
                    ->whereIn('member_group_lesson.lesson_id', $list)
                    ->orderBy('lessons.next_lesson')->get(); //Get all user lessons

            $lessons = MemberGroupLesson::hydrate($all->toArray());
        }

        // Mark the lessons as resumed
        foreach ($lessons as $key => $lesson) {
            $lesson->update(['lesson_paused' => 0]);
        }
    }

    /**
     * Resume all the email reminders
     */
    public function resumeEmailReminders($request)
    {

        $user_group_id = $request->user_group_id;
        $lesson_id = $request->lesson_id;
        $user_id = $request->user_id;

        $usergroup = UserGroup::findOrFail($user_group_id);
        $all = $usergroup->allnextlessons();

        if($usergroup->group_id == 29){

            $lesson_ids = array_column($all->toArray(), 'lesson_id');
            $index = array_search($lesson_id, $lesson_ids);
            $list = array_slice($lesson_ids, $index);

            $meetings = GroupCoachingLessonMeeting::where('group_id', $user_group_id)
            ->where('invited_by', $user_id)
            ->whereIn('lesson_id', $list)
            ->orderBy('lesson_order')->get();

        }else{
           
            $lesson_ids = array_column($all->toArray(), 'id');
            $index = array_search($lesson_id, $lesson_ids);
            $list = array_slice($lesson_ids, $index);

            // Get the meeting reminders in this group
            $records = DB::table('gc_lesson_meetings')
                    ->join('lessons', 'lessons.id', '=', 'gc_lesson_meetings.lesson_id')
                    ->select('gc_lesson_meetings.*')
                    ->where('gc_lesson_meetings.group_id', $user_group_id)
                    ->where('gc_lesson_meetings.invited_by', $user_id)
                    ->whereIn('gc_lesson_meetings.lesson_id', $list)
                    ->orderBy('lessons.next_lesson')->get(); //Get all user lessons meetings

            $meetings = GroupCoachingLessonMeeting::hydrate($records->toArray());
        }

        // Mark the meetings as resumed
        foreach ($meetings as $key => $meeting) {
            $meeting->update(['lesson_paused' => 0]);
            $meeting->settings->update([
                'three_days_coach_reminder' => 1,
                'three_days_reminder' => 1,
                'one_day_reminder' => 1,
                'one_hour_reminder' => 1,
                'three_min_before_reminder' => 1,
                'ten_min_after_reminder' => 1,
                'one_day_after_reminder' => 1,
            ]);
        }
    }

    public function notifyMembersAboutPause($request, $next_meeting_date)
    {

        $group_id = $request->group_id;
        $user_group_id = $request->user_group_id;
        $lesson_id = $request->lesson_id;
        $user_id = $request->user_id;
        $start = $request->start;
        $end = $request->end;

        $coach = User::findOrFail($user_id);
        $group = Group::findOrFail($group_id);
        $usergroup = UserGroup::findOrFail($user_group_id);
        $lesson = Lesson::findOrFail($lesson_id);
        $members = $usergroup->members();

        // add coach to the recipients list
        $members[] = (object)[
            'first_name' => $coach->first_name,
            'last_name' => $coach->last_name,
            'email' => $coach->email
        ];

        $request->uuid = uniqid(); // Create a unique id for email
        $request->recipient = 'group'; // Create a unique id for email

        $name = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;
        $subject = $name . ' - Sessions paused.';

        $meeting_time = Carbon::createFromDate($usergroup->meeting_time)->format("h:i A");

        $request->message = $this->formatPauseMessage($lesson, $start, $end, $next_meeting_date, $usergroup->time_zone, $meeting_time);
        $request->subject = $subject;
        $request->from = $coach->email;

        $this->sendMultipleEmails($request, $members, $coach);
    }

    public function notifyMembersAboutResume($request)
    {

        $group_id = $request->group_id;
        $user_group_id = $request->user_group_id;
        $lesson_id = $request->lesson_id;
        $user_id = $request->user_id;

        $coach = User::findOrFail($user_id);
        $group = Group::findOrFail($group_id);
        $usergroup = UserGroup::findOrFail($user_group_id);
        $lesson = Lesson::findOrFail($lesson_id);
        $members = $usergroup->members();

        // add coach to the recipients list
        $members[] = (object)[
            'first_name' => $coach->first_name,
            'last_name' => $coach->last_name,
            'email' => $coach->email
        ];

        $request->uuid = uniqid(); // Create a unique id for email
        $request->recipient = 'group'; // Create a unique id for email

        $paused = GroupCoachingPausedSession::where('user_id', $user_id)->where('group_id', $user_group_id)->first();

        $name = (isset($usergroup->name) && strlen($usergroup->name) > 0) ? $usergroup->name : $group->title;
        $subject = $name . ' - Sessions resumed.';

        $request->message = $this->formatResumeMessage($lesson, $paused->end_date);
        $request->subject = $subject;
        $request->from = $coach->email;

        $this->sendMultipleEmails($request, $members, $coach);

        // Remove the reference of the paused group
        GroupCoachingPausedSession::where('user_id', $user_id)->where('group_id', $user_group_id)->delete();
    }

    public function formatPauseMessage($lesson, $start, $end, $next_meeting_date, $time_zone, $meeting_time)
    {

        $messages = [];

        $s = Carbon::createFromDate($start);
        $e = Carbon::createFromDate($end);

        $difference = $e->diffInDays($s);

        $messages[] = '<p>Hello </p>';
        $messages[] = '<p>We are pausing our group sessions for <b>' . $difference . '</b> days from <b>' . $s->toFormattedDateString() . '</b> to <b>' . $e->toFormattedDateString() . '</b>.</p>';
        $messages[] = '<p>We shall resume our normal sessions on <b>' . $next_meeting_date . ' ' . $meeting_time . ' ' . $time_zone . ' </b>, starting from <b><em>' . $lesson->title . '</em></b> lesson.</p>';

        return implode(" ", $messages);
    }

    public function formatResumeMessage($lesson, $end)
    {

        $messages = [];

        $e = Carbon::createFromDate($end);

        $messages[] = '<p>Hello </p>';
        $messages[] = '<p>We have resumed our normal group sessions, starting from <b><em>' . $lesson->title . '</em></b> lesson.</p>';

        return implode(" ", $messages);
    }

    /**
     * Send an email to multiple student in a group
     *
     * @param  \Illuminate\Http\Request  $request 
     * @return \Illuminate\Http\Response
     */
    public function sendMultipleEmails($request, $members, $coach)
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

            $integration = getIntegration($coach);

            if ($integration && ($integration->aweber == 1)) { // Send emails via Aweber
                // TO DO
                // Create function to send emails to AWeber list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendAweberBroadcast($coach, $request->user_group_id, $details);
            } else if ($integration && ($integration->active_campaign == 1)) { // Send emails via ActiveCampaign
                // TO DO
                // Create function to send emails to ActiveCampaign list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendACampaignBroadcast($coach, $request->user_group_id, $details);
            } else if ($integration && ($integration->getresponse == 1)) { // Send emails via GetResponse
                // TO DO
                // Create function to send emails to GetResponse list
                $details = (object)['message' => $request->message, 'subject' => $request->subject];
                sendGetResponseBroadcast($coach, $request->user_group_id, $details);
            } else { // Send emails via system template emails i.e MailGun
                $this->sendSingleEmail($request); // Send the email and all the members as BCC
            }

            $this->saveMultipleMailBox($request, $members, $coach); // Add records to the mailbox table
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

        $to = $request->to;

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
        $messages[] = $request->message;

        // Email without attachment
        $notice = [
            'user' => $this->user,
            'to' => trim($to),
            'messages' => $messages,
            'summary' => isset($request->summary) ? $request->summary : [],
            'subject' => $request->subject,
            'copy' => $copy,
            'bcopy' => $bcopy,
        ];

        ProcessEmail::dispatch($notice, 'groupcoaching');
    }

    /**
     * Send an email to multiple student in a group
     *
     * @param  \Illuminate\Http\Request  $request 
     * @return \Illuminate\Http\Response
     */
    public function saveMultipleMailBox($request, $members, $coach)
    {

        foreach ($members as $key => $member) {

            /*  Skip the first member $key == 0
                since the first member got the email and a mailbox record 
                created on sendSingleEmail above 
            */

            if ($key > 0) {
                // Save the email records to DB
                $box = new MailBox;

                if ($request->parent) {
                    $box->parent = (int)$request->parent;
                }

                if ($request->recipient == 'group') {
                    $box->group = (int)$request->user_group_id;
                }

                $box->from = trim($request->from);
                $summary = (isset($request->summary) && count($request->summary) > 0) ?
                    '<p>' . implode(" ", $request->summary) . '</p>' : '';

                $signature[] = '<p>Sincerely,<br/>';
                $signature[] = $coach->first_name . ' ' . $coach->last_name . '<br/>';
                $signature[] = '<em>' . $coach->email . '</em></p>';

                $box->to = $member->email;
                $box->uuid = $request->uuid;
                $box->subject = trim($request->subject);
                $url = $this->saveEmailToS3(base64_encode(trim($request->message . $summary . implode(" ", $signature))));
                $box->body = $url;
                $box->read = 0;
                $box->save();
            }
        } // End for each

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

    public function preloadLessonMeetings($lessons, $id, $invited_by, $group)
    {

        if ($lessons && sizeof($lessons) > 0) {

            foreach ($lessons as $key => $lesson) {
                $gclm = GroupCoachingLessonMeeting::updateOrCreate(
                    [
                        'user_id' => $id,
                        'group_id' => $group->id,
                        'lesson_id' => $lesson->id,
                        'invited_by' => $invited_by
                    ],
                    [
                        'meeting_time' => $lesson->created_at,
                        'time_zone' => $group->time_zone,
                        'meeting_url' => $group->meeting_url,
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
     * Get users by lesson & user group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLessonUsers(Request $request)
    {

        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $messages = [
                'user_id.required' => 'A user is required',
                'user_id.exists' => 'Provided user does not exist',
                'user_group_id.required' => 'A group is required',
                'user_group_id.exists' => 'Provided group does not exist',

            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }


            $lesson_users = MemberGroupLesson::where('group_id', $request->user_group_id)->where('lesson_id', $request->lesson_id)
                ->where('user_id', '!=', $request->user_id)->with('user')->get();

            $transform = MemberGroupLessonResource::collection($lesson_users);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('User/Group not found', 400);
        }
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

            $member_group_lesson = MemberGroupLesson::findOrFail($id);

            if ($request->has('lesson_access')) {
                $member_group_lesson->lesson_access = (int)trim($request->input('lesson_access'));
            }

            if ($request->has('lesson_length')) {
                $member_group_lesson->lesson_length = trim($request->input('lesson_length'));
            }

            if ($request->input('lesson_order')) {
                $member_group_lesson->lesson_order = trim($request->input('lesson_order'));
            }

            if ($member_group_lesson->isDirty()) {
                $member_group_lesson->save();
            }

            return $this->successResponse($member_group_lesson, 200);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 400);
        }
    }

    public function addUserToGroup($user_id, $group_id, $usergroup)
    {

        // add user to the group coaching
        $lessons = $usergroup->lessons();

        foreach ($lessons as $key => $lesson) {
            $mgl = MemberGroupLesson::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->id,
                    'invited_by' => $this->user->id
                ],
                ['created_at' => $lesson->created_at, 'updated_at' => $lesson->created_at]
            );
            
        }
    }

    public function addUserToCustomGroup($user_id, $group_id, $usergroup)
    {

        // add user to the group coaching
        $lessons = CustomGroupLesson::where('group_id', $group_id)->where('user_group_id', $usergroup->id)->orderBy('lesson_order')->get(); //Get all lessons

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
        $date->setTimezone('UTC');

        foreach ($lessons as $key => $lesson) {
            MemberGroupLesson::updateOrCreate(
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

}
