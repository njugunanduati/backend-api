<?php

namespace App\Http\Controllers;

use Cache;
use Validator;
use App\Models\Module;
use App\Helpers\Helper;
use App\Models\ModuleSet;
use App\Models\ModuleMeta;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Models\ModuleQuestion;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Http\Controllers\Controller;
use App\Models\ModuleQuestionComment;
use App\Models\ModuleQuestionResponse;

use Illuminate\Database\QueryException;
use App\Http\Resources\Question as QuestionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QuestionController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Get the list of questions for a certain module
     *
     * @return \Illuminate\Http\Response
     */
    public function getModuleQuestions(Request $request)
    {

        try{
            $validator = Validator::make($request->all(), [
                'module' => 'required|alpha_dash',
                'section' => 'required|in:Question,QuestionNote,QuestionOption,QuestionSplit',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $module_name = $request->input('module');
            $module_section = $request->input('section');

            $module_class = new ModuleQuestion();
            $module_class->setTable(Helper::module_name_to_full_table_name($module_name, 'Question'));

            $notes = new ModuleQuestionNote();
            $notes->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionNote'));



            if ($module_section == 'QuestionOption') {
                $module_questions = $module_class->where('question_type', 'option')->get();
            } else if ($module_section == 'QuestionSplit') {
                $module_questions = $module_class->where('question_type', 'split_y_n')->get();
            } else {
                $module_questions = $module_class->where('id', '!=', '0')->get();
            }

            
            $questions_holder=[];

            foreach ($module_questions as $key => $value) {


                if ($value->question_type =='split_y_n') {
                    $split = $module_class->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionSplit'));
                    $split_result = $split->where('question_id', $value->id)->get();
                    $split_holder=[];
                        for ($i=0; $i < count($split_result) ; $i++) {
                            $split_holder[] = ['id' => $split_result[$i]->question_id,'split_criteria_operator'=> $split_result[$i]->split_criteria_operator,
                            'next_question_id' => $split_result[$i]->split_result];
                        }
                        $value->next_question = $split_holder;

                }

                $questions_holder[] = array(
                    'id' => $value->id,
                    'question' => $value->question_text,
                    'question_type' => $value->question_type,
                    'question_number' => $value->question_number ?? null,
                    'next_question' => $value->next_question,
                    'impact' => $value->impact,
                    'start' => ($value->start)? $value->start : 0,
                    'notes' => $notes->where('question_id', $value->id)->count() ? $notes->where('question_id', $value->id)->first() : '',
                );


            }

            // $transform = QuestionResource::collection($module_questions);

            return $this->successResponse($questions_holder,200);

        }

        catch (QueryException $ex) {
            return $this->errorResponse('Module not found', 404);
          }
        catch (Exception $ex) {
            return $this->errorResponse('An error occured', 404);
          }

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addModuleQuestion(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'module' => 'required|alpha_dash',
            'section' => 'required|in:Question,QuestionNote,QuestionOption,QuestionSplit',
            'row_id' => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $module_name = $request->input('module');
        $module_section = $request->input('section');
        $row_id = $request->input('row_id');

        // Clear all caches for module data
        Cache::tags('module_data')->flush();

        switch ($module_section) {
            case 'Question':
                $module_class = new ModuleQuestion;
                $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                if ($row_id) {
                    $module_class = $module_class->where('id', $row_id)->first();
                    $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                }

                $validator = Validator::make($request->all(), [
                    'question' => 'required|max:20000',
                    'type' => 'required|in:text,split_y_n,decimal,percentage,impact,blank',
                    'next_question' => 'required|integer',
                ]);

                if ($validator->fails()) {
                    return $this->errorResponse($validator->errors(), 400);
                }

                $module_class->question_text = $request->input('question');
                $module_class->question_type = $request->input('type');

                if ($request->input('type') == 'split_y_n') {
                    $module_class->next_question = 9999;
                } else {
                    $module_class->next_question = $request->input('next_question');
                }

                $module_class->save();

                // $transform = new QuestionsResource($module_class);

                return $this->successResponse($module_class,200);


                break;
            case 'QuestionNote':
                $this->validate($request, [
                    'note_text' => 'required',
                ]);
                if (!$row_id) {
                    $this->validate($request, [
                        'question_id' => 'required|integer',
                    ]);
                }
                $module_class = new ModuleQuestionNote;
                $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                if ($row_id) {
                    $module_class = $module_class->where('id', $row_id)->first();
                    $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                } else {
                    $module_class->question_id = $request->input('question_id');
                }
                $module_class->note_text = $request->input('note_text');
                $module_class->save();

                return response('', 200);

                break;
            case 'QuestionOption':
                $this->validate($request, [
                    'option_text' => 'required|max:255',
                    'option_value' => 'required|max:255',
                ]);
                if (!$row_id) {
                    $this->validate($request, [
                        'question_id' => 'required|integer',
                    ]);
                }
                $module_class = new ModuleQuestionOption;
                $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                if ($row_id) {
                    $module_class = $module_class->where('id', $row_id)->first();
                    $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                } else {
                    $module_class->question_id = $request->input('question_id');
                }
                $module_class->option_text = $request->input('option_text');
                $module_class->option_value = $request->input('option_value');
                $module_class->save();

                return $this->successResponse($module_class,200);

                break;
            case 'QuestionSplit':
                $this->validate($request, [
                    'split_criteria' => 'required|in:y/n',
                    'split_criteria_operator' => 'required|in:Y,N',
                    'split_result' => 'required|integer',
                ]);
                if (!$row_id) {
                    $this->validate($request, [
                        'question_id' => 'required|integer',
                    ]);
                }
                $module_class = new ModuleQuestionSplit;
                $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                if ($row_id) {
                    $module_class = $module_class->where('id', $row_id)->first();
                    $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                } else {
                    $module_class->question_id = $request->input('question_id');
                }
                $module_class->split_criteria = $request->input('split_criteria');
                $module_class->split_criteria_operator = $request->input('split_criteria_operator');
                $module_class->split_result = $request->input('split_result');
                $module_class->save();


                break;
            default:

                break;
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
    public function store(Request $request)
    { }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    { }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    { }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    { }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    { }
}
