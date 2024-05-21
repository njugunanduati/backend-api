<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Models\Lesson;
use App\Models\LessonRecording;

use App\Models\GroupLesson;
use App\Models\Resource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\UserGroup;
use App\Models\CustomGroupLesson;
use App\Models\MemberGroupLesson;
use App\Models\GroupCoachingLessonMeeting;
use App\Models\GroupCoachingEmailNotification;

use App\Http\Resources\Lesson as LessonResource;
use App\Http\Resources\LessonRecording as LessonRecordingResource;

use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Resources\CustomGroupLesson as CustomGroupLessonResource;
use App\Http\Resources\MemberGroupLesson as MemberGroupLessonResource;
use App\Http\Resources\GroupCoachingEmailNotification as GroupCoachingEmailNotificationResource;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;



class LessonController extends Controller
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

        $lessons = Lesson::all(); //Get all lessons

        $transform = LessonResource::collection($lessons);

        return $this->successResponse($transform, 200);
    }

    public function addMGL($users, $invited_by, $lesson_id, $lesson_length, $lesson_order, $user_group_id, $date)
    {

        // Add this lesson to all the users in the MGL

        if(count($users) > 0){
            foreach ($users as $key => $user) {
                MemberGroupLesson::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'group_id' => $user_group_id,
                        'lesson_id' => $lesson_id,
                        'invited_by' => $invited_by,
                    ],
                    [
                        'lesson_length' => (int)$lesson_length,
                        'lesson_order' => (int)$lesson_order,
                        'created_at' => $date, 
                        'updated_at' => $date
                    ]);
            }
        }else{
            MemberGroupLesson::updateOrCreate(
                    [
                        'user_id' => $invited_by,
                        'group_id' => $user_group_id,
                        'lesson_id' => $lesson_id,
                        'invited_by' => $invited_by,
                    ],
                    [
                        'lesson_length' => (int)$lesson_length,
                        'lesson_order' => (int)$lesson_order,
                        'created_at' => $date, 
                        'updated_at' => $date
                    ]);
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

                if ($request->has('id')) {

                    $lesson = Lesson::updateOrCreate(
                        [
                            'id' => $request->id,
                        ],
                        [
                            'title' => $request->title,
                            'slug' => $request->slug,
                            'full_desc' => $request->description,
                            'owner' => $request->owner,
                            'lesson_img' => $request->lesson_img,
                            'lesson_video' => $request->lesson_video,
                            'quiz_url' => $request->has('quiz_url') ? $request->quiz_url : ' ',
                            'published' => $request->published,
                            'free_lesson' => $request->free_lesson,
                            'price' => $request->price,
                        ]
                    );

                    $transform = new LessonResource($lesson);

                    return $this->successResponse($transform, 200);

                }else{

                    $user_id = $this->user->id;
                    $group_id = $request->group_id;
                    $user_group_id = $request->user_group_id;
                    $lesson_length = $request->lesson_length;
                    $lesson_order = $request->lesson_order;

                    $lesson = new Lesson;
                    $lesson->title = trim($request->title);
                    $lesson->slug = $request->has('slug') ? $request->slug : ' ';
                    $lesson->full_desc = $request->has('description') ? $request->description : ' ';
                    $lesson->owner = $request->has('owner') ? $request->owner : 'PAS';
                    $lesson->lesson_img = $request->lesson_img;
                    $lesson->lesson_video = $request->lesson_video;
                    $lesson->quiz_url = $request->has('quiz_url') ? $request->quiz_url : ' ';
                    $lesson->published = $request->has('published') ? $request->published : 0;
                    $lesson->free_lesson = $request->has('free_lesson') ? $request->free_lesson : 0;
                    $lesson->price = $request->has('price') ? $request->price : 0;

                    $lesson->save();

                    $lesson = $lesson->refresh();
                    
                    // Add email templattes for this lessons
                    $this->addEmailTemplates($lesson->id);

                    $usergroup = UserGroup::findOrFail($user_group_id);

                    // Link this lesson to group
                    GroupLesson::firstOrCreate(['lesson_id' => $lesson->id,'group_id' => $group_id]);

                    // Add a new custom group lesson
                    $last_cgl = CustomGroupLesson::where('group_id', $group_id)->where('user_group_id', $user_group_id)->orderBy('lesson_order', 'DESC')->first();

                    if($last_cgl){
                        $date = Carbon::createFromFormat('Y-m-d H:i:s', $last_cgl->created_at);
                        $date->setTimezone('UTC');
                        $date->addWeeks((int)$lesson_length);
                    }else{
                        // This is probably the first lesson in this group
                        $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
                        $date->setTimezone('UTC');
                    }

                    // Create a custom group lesson
                    CustomGroupLesson::firstOrCreate([
                        'group_id' => $group_id,
                        'user_group_id' => $user_group_id,
                        'lesson_id' => $lesson->id,
                        'lesson_length' => $lesson_length,
                        'lesson_order' => $lesson_order,
                        'created_at' => $date, 
                        'updated_at' => $date
                    ]);

                    $users = $usergroup->users();

                    $this->addMGL($users, $this->user->id, $lesson->id, $lesson_length, $lesson_order, $user_group_id, $date);
                    
                    $mgl = MemberGroupLesson::where('group_id', $user_group_id)->where('invited_by', $this->user->id)->groupBy('lesson_id')->orderBy('lesson_order')->get();

                    $lesson->lesson_length = $lesson_length;

                    $a = new LessonResource($lesson);
                    $b = MemberGroupLessonResource::collection($mgl);

                    return $this->successResponse(['lesson' => $a, 'mgl' => $b], 200);

                }
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this lesson', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this lesson', 400);
        }
    }


    
    /**
     * Update the lessons order after drag and drop
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateLessonOrder(Request $request)
    {
        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'order' => 'required',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist',
                'order.required' => 'Lessons order is required',
            ];

            $group_id = $request->group_id;
            $user_group_id = $request->user_group_id;
            $order = $request->order;
            $invited_by = $this->user->id;

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $usergroup = UserGroup::find($user_group_id);

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
            $date->setTimezone('UTC');

            foreach ($order as $key => $each) {
                $lesson_id = $each['lesson_id'];
                $lesson_order = $each['lesson_order'];
                $lesson_length = $each['lesson_length'];

                CustomGroupLesson::updateOrCreate(
                [
                    'group_id' => $group_id,
                    'user_group_id' => $user_group_id,
                    'lesson_id' => $lesson_id,
                ],
                [
                    'lesson_order' => $lesson_order,
                    'created_at' => $date, 
                    'updated_at' => $date
                ]);

                MemberGroupLesson::where('group_id', $user_group_id)->where('lesson_id', $lesson_id)->update(
                [
                    'lesson_order' => $lesson_order,
                    'created_at' => $date, 
                    'updated_at' => $date
                ]);
                
                $date->addWeeks((int)$lesson_length);
            }

            $mgl = MemberGroupLesson::where('group_id', $user_group_id)->where('invited_by', $invited_by)->groupBy('lesson_id')->orderBy('lesson_order')->get();
            
            $lessons = $usergroup->customlessons();

            $a = LessonResource::collection($lessons);
            $b = MemberGroupLessonResource::collection($mgl);

            return $this->successResponse(['lessons' => $a, 'mgl' => $b], 200);

        } catch (Exception $e) {
            return $this->errorResponse('Error occured while reordering the lessons', 400);
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
     * Delete File from AWS S3 bucket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function resourceDelete($key, $path)
    {

        if ($key) {
            $s3 = \AWS::createClient('s3');

            $result = $s3->deleteObject([
                'Bucket'     => env('AWS_BUCKET_NAME', 'profitaccelerationsoftware'),
                'Key'        => $path . $key,
            ]);

            if ($result['DeleteMarker']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deleteS3Resources($resources)
    {
        if (count($resources) > 0) {
            $path = 'coaching-resources/lessons/custom/';
            foreach ($resources as $key => $item) {

                $key = $this->getResourceKey($item->url);

                if ($key) {
                    $this->resourceDelete($key, $path);
                }
            }
        }
    }

    public function getResourceKey($url)
    {
        if ($url) {
            $pieces = explode("/", $url);
            return $pieces[count($pieces) - 1];
        } else {
            return null;
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $group_id
     * @param  int  $lesson_id
     * @param  int  $user_group_id
     * @return \Illuminate\Http\Response
     */
    public function deleteLesson(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'lesson_id' => 'required|exists:lessons,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist',
            ];

            $group_id = $request->group_id;
            $lesson_id = $request->lesson_id;
            $user_group_id = $request->user_group_id;

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $lesson = Lesson::findOrFail($lesson_id);

            $isCustom = ($lesson->short_desc == 'MY CUSTOM LESSONS'); // Only custom lesson can be deleted
            $isOwner = ((int)$this->user->id == (int)$lesson->owner); // Only owner of lesson can delete it 

            // Delete only the custom lessons
            if ($isCustom && $isOwner) {

                $lesson->gclessonNotifications()->delete(); // Delete the email notifications
                $lesson->memberGroupLesson()->where('lesson_id', $lesson_id)->where('group_id', $user_group_id)->delete(); // Delete member group lesson
                $lesson->customGroupLessons()->where('lesson_id', $lesson_id)->where('user_group_id', $user_group_id)->delete(); // Delete custom group lesson

                GroupLesson::where('group_id', $group_id)->where('lesson_id', $lesson_id)->delete();
                GroupCoachingLessonMeeting::where('group_id', $user_group_id)->where('lesson_id', $lesson_id)->delete();

                // Delete all the lesson resources 
                $image_key = $this->getResourceKey($lesson->lesson_img);
                $video_key = $this->getResourceKey($lesson->lesson_video);

                $image_path = 'coaching-resources/lessons/custom/images/lessons/';
                $video_path = 'coaching-resources/lessons/custom/video/lessons/';

                $this->resourceDelete($image_key, $image_path);
                $this->resourceDelete($video_key, $video_path);

                // Delete all the S3 resources attached to this lesson
                $this->deleteS3Resources($lesson->resources()->get());

                $lesson->resources()->delete(); // Delete resources of a lesson from DB

                $lesson->delete(); //Delete the lesson

                $transform = new LessonResource($lesson);

                return $this->successResponse($transform, 200);
            } else {
                return $this->singleMessage('Probably you are not the owner of this lesson', 200);
            }
        } catch (ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {

                return $this->errorResponse('Lesson not found', 400);
            }
        }
    }

    /**
     * Delink the specified resource from other resources.
     *
     * @param  int  $group_id
     * @param  int  $lesson_id
     * @param  int  $user_group_id
     * @return \Illuminate\Http\Response
     */
    public function delinkLesson(Request $request)
    {

        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'lesson_id' => 'required|exists:lessons,id',
                'user_group_id' => 'required|exists:user_groups,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist',
            ];

            $group_id = $request->group_id;
            $lesson_id = $request->lesson_id;
            $user_group_id = $request->user_group_id;

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $lesson = Lesson::findOrFail($lesson_id);

            $lesson->memberGroupLesson()->where('lesson_id', $lesson_id)->where('group_id', $user_group_id)->delete(); // Delete member group lesson
            $lesson->customGroupLessons()->where('lesson_id', $lesson_id)->where('user_group_id', $user_group_id)->delete(); // Delete custom group lesson

            GroupLesson::where('group_id', $group_id)->where('lesson_id', $lesson_id)->delete();
            GroupCoachingLessonMeeting::where('group_id', $user_group_id)->where('lesson_id', $lesson_id)->delete();

            $transform = new LessonResource($lesson);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {
                return $this->errorResponse('Lesson not found', 400);
            }
        }
    }


    public function linkLessonToGroup(Request $request)
    {
        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'lesson_id' => 'required|exists:lessons,id',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist in list',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist in list',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_lesson = new GroupLesson;
            $group_lesson->group_id = trim($request->group_id);
            $group_lesson->lesson_id = $request->has('lesson_id') ? $request->lesson_id : ' ';
            $group_lesson->save();

            return $this->singleMessage('Link was created', 200);
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this link', 400);
        }
    }

    public function multiLessonLink(Request $request)
    {
        try {

            $rules = [
                'group_id' => 'required|exists:groups,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'user_id' => 'required|exists:users,id',
                'new_lesson_ids' => 'required',
                'lessons' => 'required',
            ];

            $info = [
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist in list',
                'new_lesson_ids.required' => 'New lesson IDs is required',
                'user_group_id.required' => 'User group ID is required',
                'user_group_id.exists' => 'User group ID MUST exist',
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User ID MUST exist',
                'lessons.required' => 'Lessons are required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $group_id = $request->group_id;
            $user_group_id = $request->user_group_id;
            $invited_by = $request->user_id;
            $new_lesson_ids = json_decode($request->new_lesson_ids);
            $lessons = json_decode($request->lessons); // All the lessons including old & new

            // Link the new lesson IDs
            foreach ($new_lesson_ids as $each) {
                GroupLesson::firstOrCreate(['lesson_id' => $each, 'group_id' => $group_id]);
            }

            $usergroup = UserGroup::findOrFail($user_group_id);
            $users = $usergroup->users();

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $usergroup->meeting_time);
            $date->setTimezone('UTC');

            // Add the lessons
            foreach ($lessons as $each) {

                // Add member group lessons
                $this->addMGL($users, $this->user->id, $each->lesson_id, $each->lesson_length, $each->lesson_order, $user_group_id, $date);

                // Create a custom group lesson
                CustomGroupLesson::updateOrCreate(
                    [
                        'group_id' => $group_id,
                        'user_group_id' => $user_group_id,
                        'lesson_id' => (int)$each->lesson_id,
                    ],
                    [
                        'lesson_length' => (int)$each->lesson_length,
                        'lesson_order' => (int)$each->lesson_order,
                        'created_at' => $date, 
                        'updated_at' => $date
                    ]);

                $date->addWeeks((int)$each->lesson_length);
            }

            $mgl = MemberGroupLesson::where('group_id', $user_group_id)->where('invited_by', $invited_by)->groupBy('lesson_id')->orderBy('lesson_order')->get();
            
            $lessons = $usergroup->customlessons();

            $a = LessonResource::collection($lessons);
            $b = MemberGroupLessonResource::collection($mgl);

            return $this->successResponse(['lessons' => $a, 'mgl' => $b], 200);

        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this link', 400);
        }
    }

    public function addmemberGroupLesson(Request $request)
    {
        try {

            $rules = [
                'user_id' => 'required|exists:users,id',
                'group_id' => 'required|exists:user_groups,id',
                'lesson_id' => 'required|exists:lessons,id',
                'lesson_length' => 'required',
                'lesson_order' => 'required',
            ];

            $info = [
                'user_id.required' => 'User ID is required',
                'user_id.exists' => 'User ID MUST exist in list',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist in list',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist in list',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                if ($request->has('id')) {

                    $member_group_lesson = MemberGroupLesson::updateOrCreate(
                        [
                            'id' => $request->id,
                            'lesson_id' => $request->lesson_id,
                            'group_id' => $request->group_id,
                        ],
                        [
                            'lesson_length' => $request->has('lesson_length') ? $request->lesson_length : '',
                            'lesson_order' => $request->has('lesson_order') ? $request->lesson_order : '',
                            'lesson_access' => $request->has('lesson_access') ? $request->lesson_access : '',
                            'invited_by' =>  $request->has('invited_by') ? $request->invited_by : '',
                        ]
                    );

                    $transform = new MemberGroupLessonResource($member_group_lesson);

                    return $this->successResponse($transform, 200);
                } else {

                    $member_group_lesson = new MemberGroupLesson();
                    $member_group_lesson->user_id = (int)$request->user_id;
                    $member_group_lesson->group_id = (int)$request->group_id;
                    $member_group_lesson->lesson_id = $request->lesson_id;
                    $member_group_lesson->lesson_length = $request->lesson_length;
                    $member_group_lesson->lesson_order = $request->lesson_order;
                    $member_group_lesson->lesson_access = $request->has('lesson_access') ? $request->lesson_access : 1;
                    $member_group_lesson->invited_by = $request->has('invited_by') ? $request->user_id : ' ';
                    $member_group_lesson->save();

                    $transform = new MemberGroupLessonResource($member_group_lesson);

                    return $this->successResponse($transform, 200);
                }
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this object', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this object', 400);
        }
    }

    public function addCustomGroupLesson(Request $request)
    {
        try {

            $rules = [
                'user_group_id' => 'required|exists:user_groups,id',
                'group_id' => 'required|exists:groups,id',
                'lesson_id' => 'required|exists:lessons,id',
                'lesson_length' => 'required',
                'lesson_order' => 'required',
            ];

            $info = [
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist in list',
                'group_id.required' => 'Group ID is required',
                'group_id.exists' => 'Group ID MUST exist in list',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist in list',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {
                $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $request->meeting_time);
                $start_date->setTimezone('UTC');

                $custom_group_lesson = new CustomGroupLesson();
                $custom_group_lesson->user_group_id = (int)$request->user_group_id;
                $custom_group_lesson->group_id = (int)$request->group_id;
                $custom_group_lesson->lesson_id = $request->lesson_id;
                $custom_group_lesson->lesson_length = $request->lesson_length;
                $custom_group_lesson->lesson_order = $request->lesson_order;
                $custom_group_lesson->save();

                $transform = new CustomGroupLessonResource($custom_group_lesson);

                return $this->successResponse($transform, 200);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this object', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this object', 400);
        }
    }

    public function addEmailTemplates($lesson_id)
    {
        try {

            $three_days_before_coach = "<p>Hi ~recipientfirstname~,</p>
            <p>Just a friendly reminder that your group coaching session, ~groupname~, is coming up in 3 days on ~datetimezone~.</p>.
            <p>To your success!</p>";
            $three_days_before_coach_sub = '~groupname~, Coaching session is coming up in 3 days';

            $three_days_before = "<p>Hi ~recipientfirstname~,</p>
            <p>This is a friendly reminder that our Group Coaching session, ~groupname~, is just 3 days away. This is definitly for you! </p>.<p><i>Don’t forget to login with the username and password received earlier. 
            <i><p><p>Your password is unique to you so please don’t share it.<p><p>I want to encourage you to take lots of notes during the presentation—it’s content-rich and I’m sure you’ll come away with some ideas on how to take your business to the next level.
             <p><p>To your success!</p>";
            $three_days_before_sub = '~groupname~, are you ready for our session together?';

            $one_day_before = '<p>Hi ~recipientfirstname~,</p>
            <p>This is another friendly reminder that our Group Coaching session, ~groupname~, is just 1 day away.</p>
            <p>Your video and checklist can both be found on this link: </p><a href="~student_url~">Click here</a>. <p>
            <p><i>Don’t forget to login with the username and password received earlier. <i><p>
            <p>Your password is unique to you so please don’t share it.<p>
            <p>To your success!</p>';
            $one_day_before_sub = '~recipientfirstname~, are you ready for our session together?';

            $one_hour_before = '<p>Hi ~recipientfirstname~,</p>
            <p>Today, is our Group Coaching session, ~groupname~. You are going to LOVE this! </p>
            <p>To your success!</p>';
            $one_hour_before_sub = '~recipientfirstname~, are you ready for our session together?';

            $one_day_after = "<p>Hi ~recipientfirstname~,</p>
            <p>As a reminder, your post-meeting action steps are found under your Resources section<p><p><i>Don’t forget to login with the username and password received earlier. <i><p>
            <p>Please be diligent in completing your steps. There is a direct correlation in the action you take and the success you find. <p>
            <p>As a reminder, if you have any questions, please message me through the coaching dashboard. I’ll get back to you as soon as I can. <p><p>To your success!</p>";
            $one_day_after_sub = "~recipientfirstname~, here are your post-meeting actions steps";



            $email_notification = new GroupCoachingEmailNotification;
            $email_notification->lesson_id = $lesson_id;
            $email_notification->three_days_before_coach = trim($three_days_before_coach);
            $email_notification->three_days_before_coach_sub = trim($three_days_before_coach_sub);
            $email_notification->three_days_before = trim($three_days_before);
            $email_notification->three_days_before_sub = trim($three_days_before_sub);
            $email_notification->one_day_before = trim($one_day_before);
            $email_notification->one_day_before_sub = trim($one_day_before_sub);
            $email_notification->one_hour_before = trim($one_hour_before);
            $email_notification->one_hour_before_sub = trim($one_hour_before_sub);
            $email_notification->three_min_after = '';
            $email_notification->three_min_after_sub = '';
            $email_notification->ten_min_after = '';
            $email_notification->ten_min_after_sub = '';
            $email_notification->one_day_after = trim($one_day_after);
            $email_notification->one_day_after_sub = trim($one_day_after_sub);

            $email_notification->save();

            $transform = new GroupCoachingEmailNotificationResource($email_notification);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Error occured while creating this email template', 400);
        }
    }

    /**
     * Delete a single resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteSingleResource(Request $request)
    {
        try {

            $rules = [
                'url' => 'required',
            ];

            $info = [
                'url.required' => 'Resource URL is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $key = $this->getResourceKey($request->url);

            $path = 'coaching-resources/lessons/custom/';

            if ($key) {
                $this->resourceDelete($key, $path);
                return $this->singleMessage('Resource Deleted', 200);
            } else {
                return $this->singleMessage('Resource Not found', 200);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this link', 400);
        }
    }

    /**
     * Get lesson in a group template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLessonCount(Request $request)
    {
        try {

            $rules = [
                'group_id' => 'required',
            ];

            $info = [
                'group_id.required' => 'Template ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $lessons = GroupLesson::select('id', 'group_id', 'lesson_id')->where('group_id', $request->group_id)->get();

            return $this->successResponse($lessons, 200);
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while executing', 400);
        }
    }

    public function addMeetingRecording(Request $request)
    {
        try {

            $rules = [
                'owner_id' => 'required|exists:users,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'lesson_id' => 'required|exists:lessons,id',
                'video_url' => 'required|url',
            ];

            $info = [
                'owner_id.required' => 'Owner ID is required',
                'owner_id.exists' => 'Owner ID MUST exist',
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist in list',
                'video_url.required' => 'Video url MUST be provided',
                'video_url.url' => 'Video url MUST be valid',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {


                $lesson_recording = LessonRecording::updateOrCreate(
                    [
                        'id' => $request->id,
                        'lesson_id' => $request->lesson_id,
                        'user_group_id' => $request->user_group_id,
                        'owner_id' => $request->owner_id,
                    ],
                    ['video_url' => $request->has('video_url') ? $request->video_url : '']
                );

                $transform = new LessonRecordingResource($lesson_recording);

                return $this->successResponse($transform, 200);


            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this object', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this object', 400);
        }
    }

    public function getMeetingRecording(Request $request)
    {
        try {

            $rules = [
                // 'owner_id' => 'required|exists:users,id',
                'user_group_id' => 'required|exists:user_groups,id',
                'lesson_id' => 'required|exists:lessons,id',
            ];

            $info = [
                'user_group_id.required' => 'User Group ID is required',
                'user_group_id.exists' => 'User Group ID MUST exist',
                'lesson_id.required' => 'Lesson ID is required',
                'lesson_id.exists' => 'Lesson ID MUST exist in list',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {


                $lesson_recording = LessonRecording::where('user_group_id', $request->user_group_id)->where('lesson_id', $request->lesson_id)->first();

                if($lesson_recording){

                    $transform = new LessonRecordingResource($lesson_recording);
                    return $this->successResponse($transform, 200);

                }

                

                return $this->successResponse(array(), 200);

            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while retrieving this object', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while retrieving this object', 400);
        }
    }

    public function deleteMeetingRecording(Request $request)
    {
        try {

            $rules = [
                'video_url' => 'required|url',
            ];

            $info = [
                'video_url.required' => 'Video url MUST be provided',
                'video_url.url' => 'Video url MUST be valid',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                //delete db & AWS
                $resource_name = $this->getResourceKey($request->video_url);
                $path = 'coaching-resources/lessons/recordings/';

                $lesson_recording = LessonRecording::where('video_url', $request->video_url)->first();
                if(!$lesson_recording){

                    return $this->showMessage('Resource does not exist', 400);
                    
                }

                $lesson_recording->delete();

                $delete_resource = $this->resourceDelete($resource_name, $path);

                if ($delete_resource) {
                    return $this->showMessage('Resource permanently deleted', 200);
                }          


            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while retrieving this object', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while retrieving this object', 400);
        }
    }
}
