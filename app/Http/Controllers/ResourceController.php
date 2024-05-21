<?php

namespace App\Http\Controllers;

use Validator;

use App\Models\Resource;
use App\Models\Lesson;
use App\Models\GroupLesson;
use App\Models\MemberGroupLesson;
use App\Models\Group;
use App\Models\UserGroup;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;

use App\Http\Resources\Resource as LResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class ResourceController extends Controller
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
    public function store(Request $request)
    {
        try {

            $rules = [
                'description' => 'required',
                'resource_type_id' => 'required|exists:resource_types,id',
                'lesson_id' => 'required|exists:lessons,id',
                'url' => 'required',
            ];

            $info = [
                'description.required' => 'A Description is required',
                'resource_type_id.required' => 'Resource Type is required',
                'lesson_id.required' => 'Lesson ID is required',
                'url.required' => 'A Url location is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                if($request->has('lesson_id')){

                    $lesson = Resource::updateOrCreate(
                        [
                            'resource_type_id' => $request->resource_type_id,
                            'lesson_id' => $request->lesson_id,
                        ],
                        ['description' => $request->description,
                         'url'=> $request->url ,
                        ]
                    );

                    $transform = new LResource($lesson);

                return $this->successResponse($transform, 200);

                }else{

                    $lesson = new Resource;
                    $lesson->description = trim($request->description);
                    $lesson->resource_type_id = $request->resource_type_id;
                    $lesson->lesson_id = $request->lesson_id;
                    $lesson->description = $request->has('description') ? $request->description : ' ';
                    $lesson->url = $request->url;
                    $lesson->save();

                    $transform = new LResource($lesson);

                    return $this->successResponse($transform, 200);

                }

                
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Error occured while creating this lesson resource', 400);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while creating this lesson resource', 400);
        }
    }
}
