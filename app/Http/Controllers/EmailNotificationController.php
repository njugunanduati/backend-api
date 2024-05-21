<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\GroupCoachingEmailNotification;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;

use App\Http\Resources\GroupCoachingEmailNotification as GroupCoachingEmailNotificationResource;

use Illuminate\Database\Eloquent\ModelNotFoundException;


class EmailNotificationController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $email_notifications = GroupCoachingEmailNotification::all(); //Get all groups

        $transform = GroupCoachingEmailNotificationResource::collection($email_notifications);

        return $this->successResponse($transform, 200);
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
                'lesson_id' => 'required',
            ];

            $info = [
                'lesson_id.required' => 'Lesson is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $template = GroupCoachingEmailNotification::where('lesson_id', $request->lesson_id)->first();

                if($template){
                    

                    $template = GroupCoachingEmailNotification::updateOrCreate(
                        [
                            'lesson_id' => $request->lesson_id,
                        ],
                        ['three_days_before_coach' => $request->has('three_days_before_coach') ? trim($request->three_days_before_coach) : $template->three_days_before_coach,
                         'three_days_before_coach_sub' => $request->has('three_days_before_coach_sub') ? trim($request->three_days_before_coach_sub) : $template->three_days_before_coach_sub,
                         'three_days_before'=> $request->has('three_days_before') ? trim($request->three_days_before) : $template->three_days_before ,
                         'three_days_before_sub'=> $request->has('three_days_before_sub') ? trim($request->three_days_before_sub) : $template->three_days_before_sub ,
                         'one_day_before'=> $request->has('one_day_before') ? trim($request->one_day_before) : $template->one_day_before ,
                         'one_day_before_sub'=> $request->has('one_day_before_sub') ? trim($request->one_day_before_sub) : $template->one_day_before_sub ,
                         'one_hour_before'=> $request->has('one_hour_before') ? trim($request->one_hour_before) : $template->one_hour_before ,
                         'one_hour_before_sub'=> $request->has('one_hour_before_sub') ? trim($request->one_hour_before_sub) : $template->one_hour_before_sub ,
                         'three_min_after'=> $request->has('three_min_after') ? trim($request->three_min_after) : $template->three_min_after , 
                         'three_min_after_sub'=> $request->has('three_min_after_sub') ? trim($request->three_min_after_sub) : $template->three_min_after_sub ,
                         'ten_min_after'=> $request->has('ten_min_after') ? trim($request->ten_min_after) : $template->ten_min_after,
                         'ten_min_after_sub'=>$request->has('ten_min_after_sub') ? trim($request->ten_min_after_sub) : $template->ten_min_after_sub,
                         'one_day_after'=> $request->has('one_day_after') ? trim($request->one_day_after) : $template->one_day_after,
                         'one_day_after_sub'=> $request->has('one_day_after_sub') ? trim($request->one_day_after_sub) : $template->one_day_after_sub,
                        ]
                    );

                    $transform = new GroupCoachingEmailNotificationResource($template);

                    return $this->successResponse($transform, 200);

                }

                
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this email template', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured during transaction', 401);
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
        $email_notification = GroupCoachingEmailNotification::findOrFail($id); //Get all groups

        $transform = GroupCoachingEmailNotificationResource::collection($email_notification);

        return $this->successResponse($transform, 200);
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

}
