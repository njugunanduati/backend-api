<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\Group;
use App\Models\UserGroup;
use App\Models\Lesson;
use App\Models\ResourceType;
use App\Models\UserGroupTemplate;
use App\Models\GroupCoachingLessonMeeting;
use App\Models\GroupCoachingLessonMeetingSetting;
use App\Models\CustomGroupLesson;
use App\Models\MemberGroupLesson;
use App\Models\UserModuleAccessMetaData;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use App\Http\Resources\Group as GroupResource;
use App\Http\Resources\UserGroupTemplate as UserGroupTemplateResource;
use App\Http\Resources\UserGroup as UserGroupResource;
use App\Http\Resources\NewUserGroup as NewUserGroupResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;



class GroupController extends Controller
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

        $groups = Group::all(); //Get all groups

        $transform = GroupResource::collection($groups);

        return $this->successResponse($transform, 200);
    }

    /**
     * Display a listing of the resource by user id.
     *
     * @return \Illuminate\Http\Response
     */
    public function userGroups($user_id)
    {

        $groups = Group::where('owner', 'PAS')->get()->except(29); //Get all groups except ID 29 since that is the custom group
        $user = User::findOrfail($user_id);
        $date = Carbon::now();
        foreach ($groups as $key => $group) {
            $count = UserGroupTemplate::where('group_id', $group->id)->where('user_id', $user_id)->count();
            if ($count > 0) {
                $group->access = true;
            } else {
                $group->access = false;
            }
        }

        $transform = GroupResource::collection($groups);

        return $this->successResponse($transform, 200);
    }

    /**
     * Display the resource by group id.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserGroup(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_id = $request->group_id;

            $usergroup = UserGroup::whereGroupId($group_id)->first();

            $transform = new NewUserGroupResource($usergroup);
            return $this->successResponse($transform, 200);

        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to get user group', 400);
        }
        
    }

     /**
     * Display a listing of the resource by user id.
     *
     * @return \Illuminate\Http\Response
     */
    public function editCustomGroup(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'That group does not exist',
                'user_group_id.required' => 'User group ID is required',
                'user_group_id.exists' => 'That user group does not exist',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $group_id = $request->group_id;
                $user_group_id = $request->user_group_id;

                $group = Group::findOrFail($group_id);

                $template = UserGroupTemplate::select('id', 'user_id', 'group_id')->where('group_id', $group_id)->first();

                $group->status = 'DRAFT';
                $group->save();
                $user_group = UserGroup::findOrFail($user_group_id);
                $resourcetypes = ResourceType::select('id', 'name', 'owner', 'order')->get();

                $lessons = $user_group->customlessons();
                
                $mgl = $user_group->membergrouplessons();
                $resources = [];

                // Hunt for all the resources
                if(count($lessons) > 0){
                    foreach ($lessons as $key => $each) {
                        $lesson = Lesson::findOrFail($each->id);
                        
                        $temp = $lesson->resources->map(function ($e) {
                                return collect($e)->only(['id', 'description', 'resource_type_id', 'lesson_id', 'url']);
                                });
                        
                        array_push($resources, ...$temp);
                    }
                }

                 $transform = [
                     'group' => $group,
                     'usergroup' => $user_group,
                     'lessons' => $lessons,
                     'resources' => $resources,
                     'template' => $template,
                     'types' => $resourcetypes,
                     'mgl' => $mgl,
                    ];

                return $this->successResponse($transform, 200);

            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group template not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to get user group templates', 400);
        }
    }


    /**
     * Display a listing of the resource by user id.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserGroups($user_id)
    {

        $groups = UserGroup::whereUserId($user_id)->get(); //Get all user groups

        if ($groups) {
            foreach ($groups as $key => $group) {
                $group->title = $group->group->title;
            }

            $transform = UserGroupResource::collection($groups);
            return $this->successResponse($transform, 200);
        } else {

            return $this->successResponse(null, 200);
        }
    }

    /**
     * Get coach group templates
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getGroupTemplates(Request $request)
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

                // Get all the templates except the custom group. Since its not available as a template

                // Your Eloquent query executed by using get()

                $templates = DB::table('user_group_templates')
                ->join('groups', 'groups.id', '=', 'user_group_templates.group_id')
                ->select('user_group_templates.*')
                ->where('user_group_templates.user_id', '=', $user_id)
                ->where('groups.owner', '=', 'PAS')
                ->where('groups.status', '!=', 'custom')
                ->whereNull('groups.deleted_at')->get();
                $templates = UserGroupTemplate::hydrate($templates->toArray());
                $transform = UserGroupTemplateResource::collection($templates);


                $transform = UserGroupTemplateResource::collection($templates);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('User group template not found', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to get user group templates', 400);
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
                'title' => 'required',
            ];

            $info = [
                'title.required' => 'Title is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                if($request->has('id') && $request->id != NULL ){
                    $group = Group::find($request->id);

                    $meeting_time = $this->convertTime($request->has('meeting_time') ? $request->meeting_time : $group->meeting_time , $request->has('time_zone') ? $request->time_zone : $group->time_zone);

                    $group = Group::updateOrCreate(
                        [
                            'id' => $request->id,
                        ],
                        ['title' => $request->has('title') ? $request->title : $group->title,
                         'meets_on' => $request->has('meeting_day') ? $request->meeting_day : $group->meets_on,
                         'time_zone'=> $request->has('time_zone') ? $request->time_zone : $group->time_zone,
                         'description'=> $request->has('description') ? $request->description : $group->description,
                         'meeting_time'=> $meeting_time ,
                         'meeting_url'=> $request->has('meeting_url') ? $request->meeting_url :$group->meeting_url ,
                         'price'=> $request->has('price') ? $request->price : $group->price ,
                         'owner'=> $request->has('owner') ? $request->owner : $group->owner ,
                         'status'=> $request->has('status') ? $request->status : $group->title ,
                         'group_img'=> $request->has('group_img') ? $request->group_img : $group->group_img , 
                         'template_video'=> $request->has('template_video') ? $request->template_video : $group->template_video ,
                         'intro_image'=> $request->has('intro_image') ? $request->intro_image : $group->intro_image ,
                         'intro_video'=>$request->has('intro_video') ? $request->intro_video : $group->intro_video,
                        ]
                    );

                    $usergroup = UserGroup::where('user_id', $this->user->id)->where('group_id', $group->id)->get();

                    $usergroup = UserGroup::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'group_id' =>  $group->id,
                        ],
                        ['name' => $request->has('title') ? $request->title : $usergroup[0]->name,
                         'meeting_day' => $request->has('meeting_day') ? $request->meeting_day : $usergroup[0]->meeting_day,
                         'time_zone'=> $request->has('time_zone') ? $request->time_zone : $usergroup[0]->time_zone,
                         'meeting_time'=> $meeting_time ,
                         'meeting_url'=> $request->has('meeting_url') ? $request->meeting_url :$usergroup[0]->meeting_url ,
                         'price'=> $request->has('price') ? $request->price : $usergroup[0]->price ,
                         'active'=> 1 ,
                        ]
                    );

                    if($group->status == 'open'){

                        //do this when lesson is published

                        $this->addUserToGroup($this->user->id, $group->id, $usergroup);
                        $this->createCustomGroupLesson($this->user->id, $group->id, $usergroup);

                        $this->preloadCustomGroupLessonMeetings($group);
                        

                    }

                    $transform = new GroupResource($group);

                return $this->successResponse($transform, 200);

                }else{

                    $meeting_time = $this->convertTime($request->meeting_time, $request->time_zone);

                    $group = new Group;
                    $group->title = trim($request->title);
                    $group->meets_on = $request->meeting_day;
                    $group->time_zone = $request->time_zone;
                    $group->meeting_time = $request->meeting_time;
                    $group->description = $request->description;
                    $group->meeting_url = trim($request->meeting_url);
                    $group->price = $request->price;
                    $group->owner = $request->has('owner') ? $request->owner : 'PAS';
                    $group->status = $request->has('status') ? $request->status : 'DRAFT';
                    $group->group_img = $request->has('group_img') ? $request->group_img : '';
                    $group->template_video = $request->has('template_video') ? $request->template_video : '';
                    $group->intro_image = $request->has('intro_image') ? $request->intro_image : '';
                    $group->intro_video = $request->has('intro_video') ? $request->intro_video : '';
                    $group->save();

                    $usergroup = UserGroup::updateOrCreate(
                        [
                            'user_id' => $this->user->id,
                            'group_id' =>  $group->id,
                        ],
                        ['name' => $group->title,
                         'meeting_day' => $group->meets_on,
                         'time_zone'=> $group->time_zone,
                         'meeting_time'=> $group->meeting_time,
                         'meeting_url'=> $group->meeting_url,
                         'price'=> $group->price,
                         'active'=> 1,
                        ]
                    );

                    if($group->status == 'open'){
                        //do this when lesson is published
                        $this->addUserToGroup($this->user->id, $group->id, $usergroup);
                        $this->createCustomGroupLesson($this->user->id, $group->id, $usergroup);
                        $this->preloadCustomGroupLessonMeetings($group);
                    }

                    $transform = new GroupResource($group);

                    return $this->successResponse($transform, 200);

                }

                
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this group', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this group', 400);
        }
    }

    public function convertTime($meeting_time, $time_zone)
    {

        $meeting = Carbon::createFromDate($meeting_time);

        $user_date = Carbon::createMidnightDate($meeting->year, $meeting->month, $meeting->day, $time_zone);//meeting day

        $user_date->setTime($meeting->hour, $meeting->minute);

        return $user_date;
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }


    public function createUserGroupTemplate(Request $request)
    {
        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_id' => 'required|exists:users,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist in list',
                'user_id.required' => 'A User must be provided.',
                'user_id.exists' => 'User MUST exist.',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                if($request->has('id')){

                    $user_group_template = UserGroupTemplate::updateOrCreate(
                        [
                            'user_id' => $request->user_id,
                            'group_id' => $request->group_id,
                        ],
                        []
                    );
                $transform = new UserGroupTemplateResource($user_group_template);

                return $this->successResponse($transform, 200);
                }

                $user_group_template = new UserGroupTemplate;
                $user_group_template->group_id = trim($request->group_id);
                $user_group_template->user_id = $request->user_id;
                $user_group_template->save();

                $transform = new UserGroupTemplateResource($user_group_template);

                return $this->successResponse($transform, 200);

                
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this Template', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this Template', 400);
        }
    }

    public function addUserToGroup($user_id, $group_id, $usergroup)
    {

        // add user to the group coaching
        $lessons = MemberGroupLesson::where('user_id', $user_id)->where('group_id', $usergroup->id)->where('invited_by', $user_id)->orderBy('lesson_order')->get(); //Get all lessons
        

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
        $date->setTimezone('UTC');

        foreach ($lessons as $key => $lesson) {
            $mgl = MemberGroupLesson::updateOrCreate(
                [
                    'user_id' => $user_id,
                    'group_id' => $usergroup->id,
                    'lesson_id' => $lesson->lesson_id,
                    'invited_by' => $user_id
                ],
                ['created_at' => $date, 'updated_at' => $date]
            );
            $date->addWeeks((int)$lesson->lesson_length);
            // $date = $date->next($usergroup->meeting_day);
            // $date->setTime(date('H', strtotime($usergroup->meeting_time)), date('i', strtotime($usergroup->meeting_time)), date('s', strtotime($usergroup->meeting_time)));
        }
    }

    public function preloadCustomGroupLessonMeetings($group)
    {
        // $user_id, $invited_by, $group_id, $usergroup

        $user_group = UserGroup::where('group_id', $group->id)->where('user_id', $group->owner)->get(); 

        // preload meetings for this user 
        $lessons = CustomGroupLesson::where('group_id', $group->id)->where('user_group_id', $user_group[0]->id)->orderBy('lesson_order')->get(); //Get all lessons

        foreach ($lessons as $key => $lesson) {
            $gclm = GroupCoachingLessonMeeting::updateOrCreate(
                [
                    'user_id' => $group->owner,
                    'group_id' => $user_group[0]->id,
                    'lesson_id' => $lesson->lesson_id,
                    'invited_by' => $group->owner
                ],
                [
                    'lesson_order' => $lesson->lesson_order,
                    'meeting_time' => $lesson->created_at,
                    'time_zone' => $user_group[0]->time_zone,
                    'meeting_url' => $user_group[0]->meeting_url,
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

     /**
     * Save a new group_lesson
     *
     * @param  $request
     * @return \Illuminate\Http\Response
     */
    public function createCustomGroupLesson($user_id, $group_id, $usergroup)
    {

        try {
            $lessons = MemberGroupLesson::where('group_id', $usergroup->id)->where('user_id', $user_id)->orderBy('lesson_order')->get();
            $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
            $start_date->setTimezone('UTC');

            if ($lessons && sizeof($lessons) > 0) {

                foreach ($lessons as $key => $lesson) {

                    $group_lesson = CustomGroupLesson::updateOrCreate(
                        [
                            'group_id' => $usergroup->group_id,
                            'user_group_id' => $usergroup->id,
                            'lesson_id' => $lesson->lesson_id,
                        ],
                        [
                            'lesson_order' => $key,
                            'lesson_length' => (int)$lesson->lesson_length,
                            'created_at' => $start_date,
                        ]
                    );

                    //created & updated including length of lesson

                    $start_date->addWeeks((int)$lesson->lesson_length);
                }

                return $this->showMessage($group_lesson, 200);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while trying to create lessons for this template', 400);
        }
    }
}
