<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponses;

use Cache;
use Validator;


use App\Models\Assessment;
use App\Models\User;
use App\Models\ModuleQuestionResponse;
use App\Models\ModuleQuestion;
use App\Models\IncreasePricesExtra;
use App\Models\ModuleQuestionComment;
use App\Models\ModuleSetModule;



use App\Helpers\Helper;


use Illuminate\Support\Facades\DB;
use App\Http\Resources\Assessment as AssessmentResource;
use App\Http\Resources\AssessmentMiniAnalysis as AssessmentAnalysis;

use App\Http\Resources\PricesExtraResponse as ExtraResource;




use Illuminate\Database\Eloquent\ModelNotFoundException;


class ModuleAnalysisController extends Controller
{
    use ApiResponses;

    protected $user;

    public function __construct(Request $request)
    {
        $this->user = $request->user();
    }
    //

    /**
     * Display a listing of the resource by user email.
     *
     * @return \Illuminate\Http\Response
     */
    public function moduleAccessAnalysis(Request $request)
    {
        try {

            $rules = [
                'date_from' => 'required',
                'date_to' => 'required',
            ];

            $info = [
                'date_from.required' => 'Date From is required',
                'date_to.required' => 'Date To is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }
            try {

                $date_limit = (object)array('from' => $request->date_from, 'to' => $request->date_to);
                $responses = [];
                $unique_module_sets = ModuleSetModule::distinct()->get();
                foreach ($unique_module_sets as $unique_module_set) {
                    $que_res = $this->getQuestionResponses($unique_module_set, $date_limit, $type = 'access');

                    $responses[] = ["module" => $unique_module_set->moduleSet->alias, "sub_module" => $unique_module_set->module_alias, "question_responses" => count($que_res) > 0 ? $que_res : []];
                }

                return $this->successResponse($responses, 200);
            } catch (ModelNotFoundException $ex) {
                return $this->errorResponse('That assessment does not exist', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while getting this data', 400);
        }
    }


    public function impactAnalysis(Request $request)
    {
        try {

            $rules = [
                'date_from' => 'required',
                'date_to' => 'required',
            ];

            $info = [
                'date_from.required' => 'Date From is required',
                'date_to.required' => 'Date To is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $unique_module_sets = ModuleSetModule::distinct()->get();
                $date_limit = (object)array('from' => $request->date_from, 'to' => $request->date_to);

                $responses = [];
                foreach ($unique_module_sets as $unique_module_set) {
                    $que_res = $this->getQuestionResponses($unique_module_set, $date_limit, $type = 'impact');
                    if (count($que_res) > 0) {
                        $responses[] = array("module" => $unique_module_set->moduleSet->alias, "sub_module" => $unique_module_set->module_alias, "responses" => $que_res);
                    }
                }
                return $this->successResponse($responses, 200);
            } catch (ModelNotFoundException $ex) {
                return $this->errorResponse('That Model does not exist', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while getting this data', 400);
        }
    }

    public function getQuestionResponses($module, $date_limit, $type)
    {

        try {

            $table = new ModuleQuestionResponse();
            $table_name = Helper::module_name_to_full_table_name($module->module_name, 'QuestionResponse');
            $table->setTable($table_name);

            if ($type == 'impact') {
                $question_table = new ModuleQuestion;
                $question_table->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                $question_table = $question_table->where('question_type', 'impact')->get();
                if (count($question_table) == 0) {

                    return [];
                }
                $responses = $table->select('question_id', DB::raw('AVG(response) as avg_response'))
                    ->join('assessments', 'assessments.id', '=', $table_name.'.assessment_id')
                    ->whereIn('question_id', $question_table->pluck('id'))
                    ->where('assessments.module_set_id', $module->module_set_id)
                    ->where('response', '<', 200)
                    ->where($table_name.'.updated_at', '!=', '0000-00-00 00:00:00')
                    ->where('assessments.deleted_at', '=', NULL)
                    ->whereBetween($table_name.'.updated_at', [$date_limit->from, $date_limit->to])
                    ->groupBy('question_id')->get()->toArray();

                $questions = $question_table->values()->toArray();
                if (count($responses) > 0) {

                    $transform = array('questions' => $questions, 'responses' => $responses);

                    return $transform;
                } else {

                    return [];
                }
            } else {


                $responses = $table->select('module_set_id', DB::raw('COUNT(' . $table_name . '.id) as response_count'), DB::raw('COUNT(DISTINCT assessment_id) as assessment_count'), DB::raw('MONTH(' . $table_name . '.updated_at) as month'), DB::raw('YEAR(' . $table_name . '.updated_at) as year'), $table_name . '.updated_at')
                    ->join('assessments', 'assessments.id', '=', $table_name . '.assessment_id')
                    ->where('assessments.module_set_id', $module->module_set_id)
                    ->where($table_name . '.updated_at', '!=', '0000-00-00 00:00:00')
                    ->where('assessments.deleted_at', '=', NULL)
                    ->whereBetween($table_name . '.updated_at', [$date_limit->from, $date_limit->to])
                    ->groupBy(DB::raw('MONTH(' . $table_name . '.updated_at)'), DB::raw('YEAR(' . $table_name . '.updated_at)', $table_name . '.assessment_id'))
                    ->orderBy($table_name . '.updated_at', 'ASC')->get();


                $transform = (object)$responses;

                return $transform;
            }
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That Model/s does not exist', 404);
        }
    }

    public function getResponsesByModule(Request $request)
    {

        try {

            $rules = [
                'module_set_id' => 'required',
            ];

            $info = [
                'module_set_id.required' => 'Module set ID is required',
            ];

            $validator = Validator::make($request->all(), $rules, $info);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            try {

                $unique_module_sets = ModuleSetModule::where('module_set_id', $request->module_set_id)->get();

                $date_limit = (object)array('from' => $request->date_from, 'to' => $request->date_to);

                $responses = [];
                foreach ($unique_module_sets as $unique_module_set) {
                    $responses[] = array("sub_module" => $unique_module_set->module_name, "responses" => $this->getQuestionResponses($unique_module_set->module_name, $date_limit, $type = 'access'));
                }

                return $this->successResponse($responses, 200);
            } catch (ModelNotFoundException $ex) {
                return $this->errorResponse('That Model does not exist', 404);
            }
        } catch (Exception $e) {
            return $this->errorResponse('Error occured while getting this data', 400);
        }
    }

    public function assesmentsStats(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'date_from' => 'required|string',
            'date_to' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 400);
        }

        $start = $request->input('date_from');
        $end = $request->input('date_to');

        try {

            $assessments = Assessment::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();

            $transform = AssessmentAnalysis::collection($assessments);

            return $this->successResponse($transform, 200);
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('That user does not exist', 404);
        }
    }
}
