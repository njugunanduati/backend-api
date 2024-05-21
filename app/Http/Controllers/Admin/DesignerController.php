<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\ModuleQuestion;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionOption;
use App\Models\ModuleQuestionResponse;
use App\Models\ModuleQuestionSplit;
use App\Models\ModuleMeta;
use App\Helpers\Helper;
use Carbon\Carbon;
use Cache;
use Exception;
use App\Traits\ApiResponses;

class DesignerController extends Controller
{
        use ApiResponses;

    public function save(Request $request)
    {

        // Clear all caches for module data
        Cache::tags('module_data')->flush();

        $module_name = $request->input('module');
        $module_section = $request->input('section');
        $row_id = $request->input('row_id');
        $this->validate($request, [
            'module' => 'required|alpha_dash',
            'section' => 'required|in:Question,QuestionNote,QuestionOption,QuestionSplit',
            'row_id' => 'integer',
        ]);
        switch ($module_section) {
            case 'Question':
                $module_class = new ModuleQuestion;
                $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                if ($row_id) {
                    $module_class = $module_class->where('id', $row_id)->first();
                    $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
                }
                $this->validate($request, [
                    'question' => 'required|max:20000',
                    'type' => 'required|in:text,split_y_n,decimal,percentage,impact,blank',
                    'next_question' => 'required|integer',
                ]);

                $module_class->question_text = $request->input('question');
                $module_class->question_type = $request->input('type');
                if ($request->input('type') == 'split_y_n') {
                    $module_class->next_question = 9999;
                } else {
                    $module_class->next_question = $request->input('next_question');
                }
                $module_class->save();

                return response('', 200);

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

                return response('', 200);

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
                return response('', 200);

                break;
            default:

                break;
        }
    }

    public function delete(Request $request)
    {
        $module_name = $request->input('module');
        $module_section = $request->input('section');
        $row_id = $request->input('row_id');
        $this->validate($request, [
            'module' => 'required|alpha_dash',
            'section' => 'required|in:Question,QuestionNote,QuestionOption,QuestionSplit',
            'row_id' => 'required|integer',
        ]);

        // Delete all responses that are currently tied to this question

        $question_responses = new ModuleQuestionResponse();
        $question_responses->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionResponse'));
        foreach ($question_responses->where('question_id', $row_id)->get() as $response) {
            $response->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionResponse'));
            $response->delete();
        }

        // Switch any next_question or split_results that are pointing to this question to point to the id that this question was pointing to.

        $module_questions = new ModuleQuestion;
        $module_questions->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));

        $current_question = $module_questions->where('id', $row_id)->first();
        $current_question->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));

        $module_splits = new ModuleQuestionSplit();
        $module_splits->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionSplit'));

        if ($current_question->question_type == 'split_y_n') {
            if ($module_splits->where('question_id', $row_id)->where('split_criteria_operator', 'Y')->count()) {
                $next_question = $module_splits->where('question_id', $row_id)->where('split_criteria_operator', 'Y')->first()->split_result;
            } else {
                $next_question = 0;
            }
        } else {
            $next_question = $current_question->next_question;
        }

        foreach ($module_questions->where('next_question', $row_id)->get() as $question) {
            $question->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
            $question->next_question = $next_question;
            $question->save();
        }

        foreach ($module_splits->where('split_result', $row_id)->get() as $split) {
            $split->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionSplit'));
            $split->split_result = $next_question;
            $split->save();
        }

        // Delete the question

        if ($current_question->question_type == 'split_y_n') {
            foreach ($module_splits->where('question_id', $row_id)->get() as $split) {
                $split->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionSplit'));
                $split->delete();
            }
        }

        $current_question->delete();

        return response('', 200);
    }

    public function add_module(Request $request)
    {

        // Clear all caches for module data
        Cache::tags('module_data')->flush();

        $this->validate($request, [
            'module_name' => 'required|max:30|regex:/(^[A-Za-z0-9 ]+$)+/',
        ]);
        $module_name = $request->input('module_name');
        $current_module_names = Module::module_names();
        if (!in_array($module_name, $current_module_names)) {
            $real_class_name = str_replace(' ', '', ucwords(strtolower($module_name)));
            $table_base_name = 'm_' . str_replace(' ', '_', strtolower($module_name));

            $tables = ["questions", "question_splits", "question_options", "question_responses", "question_notes", "question_comments", "meta"];
            foreach ($tables as $table) {
                // Create Migrations

                $current_migration = \Storage::disk('local')->get('storage/app/stubs/' . $table . '_migration.stub');
                $current_migration = str_replace('{{migration_class}}', 'M' . $real_class_name, $current_migration);
                $current_migration = str_replace('{{table_name}}', $table_base_name, $current_migration);

                \Storage::disk('local')->put('database/migrations/2015_11_29_152849_create_' . $table_base_name . '_' . $table . '_table.php', $current_migration);
            }
        } else {
            \Session::flash('msg_class', 'danger');
            \Session::flash('msg', 'This module name already exists.');
            return redirect('/dashboard/admin/designer');
        }

        \Artisan::call('migrate', array('--force' => true));

        \Session::flash('msg_class', 'success');
        \Session::flash('msg', 'The module has been created successfully.');
        return redirect('/admin/designer');
    }

    public function module_designer_rows(Request $request)
    {
        $module_name = $request->input('module');
        $module_section = $request->input('section');
        $module_namespace = '\App\Models\Module' . $module_section;
        $module_class = new $module_namespace();
        $module_class->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
        $module_rows = $module_class->where('id', '!=', '0')->get();
        $rows = [];
        foreach ($module_rows as $module_row) {
            $module_row->setTable(Helper::module_name_to_full_table_name($module_name, $module_section));
            switch ($module_section) {
                case 'Question':
                    $rows[] = array(
                        $module_row->id,
                        $module_row->question_text,
                        $module_row->question_type,
                        $module_row->next_question,
                        '',
                        'Edit'
                    );
                    break;
                case 'QuestionNote':
                    $rows[] = array(
                        $module_row->id,
                        $module_row->questionText(),
                        $module_row->note_text,
                        '',
                        '',
                        'Edit'
                    );
                    break;
                case 'QuestionOption':
                    $rows[] = array(
                        $module_row->id,
                        $module_row->questionText(),
                        $module_row->option_text,
                        $module_row->option_value,
                        '',
                        'Edit'
                    );
                    break;
                case 'QuestionSplit':
                    $rows[] = array(
                        $module_row->id,
                        $module_row->questionText(),
                        $module_row->split_criteria,
                        $module_row->split_criteria_operator,
                        $module_row->split_result,
                        'Edit'
                    );
                    break;
                default:

                    break;
            }
        }

        return $rows;
    }

    public function module_designer_questions(Request $request)
    {
        // Get the list of questions for a certain module

        $module_name = $request->input('module');
        $module_section = $request->input('section');
        $module_class = new ModuleQuestion();
        $module_class->setTable(Helper::module_name_to_full_table_name($module_name, 'Question'));
        if ($module_section == 'QuestionOption') {
            $module_rows = $module_class->where('question_type', 'option')->get();
        } else if ($module_section == 'QuestionSplit') {
            $module_rows = $module_class->where('question_type', 'split_y_n')->get();
        } else {
            $module_rows = $module_class->where('id', '!=', '0')->get();
        }
        $questions = [];
        foreach ($module_rows as $module_row) {
            $questions[$module_row->id] = $module_row->question_text;
        }

        $questions[0] = 'None / No Next Question';

        return $questions;
    }

    public function edit_module_meta(Request $request)
    {

        // Clear all caches for module data
        Cache::tags('module_data')->flush();

        $this->validate($request, [
            'module_name' => 'required|max:255',
            'module_category' => 'required|max:100',
            'module_weight' => 'required|integer',
            'report_section_concept' => 'required',
            'report_section_current' => 'required',
            'report_section_keys' => 'required',
            'report_section_impact' => 'required',
            'average_questions' => 'required|integer',
        ]);

        $module_name = $request->input('module');
        $module_class = new ModuleMeta();
        $module_class->setTable(Helper::module_name_to_meta_table_name($module_name));
        if ($module_class->where('id', '!=', '0')->count()) {
            $module_class = $module_class->where('id', '!=', '0')->first();
            $module_class->setTable(Helper::module_name_to_meta_table_name($module_name));
        }
        $module_class->module_name = $request->input('module_name');
        $module_class->module_category = $request->input('module_category');
        $module_class->module_weight = $request->input('module_weight');
        $module_class->report_section_concept = $request->input('report_section_concept');
        $module_class->report_section_current = $request->input('report_section_current');
        $module_class->report_section_keys = $request->input('report_section_keys');
        $module_class->report_section_impact = $request->input('report_section_impact');
        $module_class->average_questions = $request->input('average_questions');
        $module_class->save();

        \Session::flash('msg_class', 'success');
        \Session::flash('msg', 'The meta data has been updated successfully.');
        return redirect('/admin/designer');
    }

    public function module_meta_data(Request $request)
    {

        try {

            $module_name = $request->input('module');
            $module_class = new ModuleMeta();
            $module_class->setTable(Helper::module_name_to_meta_table_name($module_name));
            if (!$module_class->where('id', '!=', '0')->count()) {
                return array(
                    'module_name' => '',
                    'module_category' => '',
                    'module_weight' => '',
                    'report_section_concept' => '',
                    'report_section_current' => '',
                    'report_section_keys' => '',
                    'report_section_impact' => '',
                    'average_questions' => '',
                );
            }
            $meta_data = $module_class->where('id', '!=', '0')->first();

            return array(
                'module_name' => $meta_data->module_name,
                'module_category' => $meta_data->module_category,
                'module_weight' => $meta_data->module_weight,
                'report_section_concept' => $meta_data->report_section_concept,
                'report_section_current' => $meta_data->report_section_current,
                'report_section_keys' => $meta_data->report_section_keys,
                'report_section_impact' => $meta_data->report_section_impact,
                'average_questions' => $meta_data->average_questions,
            );
        } catch (Exception $ex) {
            return $this->errorResponse('That module does not exist', 400);
        }
    }

    public function error_check(Request $request)
    {
        $module = new Module('m_advertising_questions');
        $modules = $module->all();

        // Errors to check:
        // 1. There is only one first question
        // 2. The split questions all have two paths set
        // 3. There are no endless loops

        $errors = [];
        foreach ($modules as $module) {
            if (in_array($module->module_name, array('CutCosts', 'SalesForce'))) {
                continue;
            }
            $loop_check = 0;
            $first_question_check = [];

            $module = new Module(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
            $all_questions = new ModuleQuestion;
            $all_questions->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
            foreach ($all_questions->where('id', '!=', '0')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                // Check if it's a first question

                $next_question_check = new ModuleQuestion;
                $next_question_check->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                if (!$next_question_check->where('next_question', $question->id)->count()) {
                    $split_check = new ModuleQuestionSplit();
                    $split_check->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionSplit'));
                    if (!$split_check->where('split_result', $question->id)->count()) {
                        $first_question_check[] = $question->id;
                    }
                }

                // Check that split questions have two paths set

                if ($question->question_type == 'split_y_n') {
                    $split_check = new ModuleQuestionSplit();
                    $split_check->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionSplit'));
                    if (!$split_check->where('question_id', $question->id)->where('split_criteria_operator', 'Y')->count()) {
                        $errors[] = 'Module: ' . $module->module_name . '. The YES path has not been set for Split Question ID: ' . $question->id . '.';
                    }
                    if (!$split_check->where('question_id', $question->id)->where('split_criteria_operator', 'N')->count()) {
                        $errors[] = 'Module: ' . $module->module_name . '. The NO path has not been set for Split Question ID: ' . $question->id . '.';
                    }
                }
            }

            if (count($first_question_check) > 1) {
                $errors[] = 'Module: ' . $module->module_name . '. Multiple first questions error, question IDs: ' . implode(',', $first_question_check) . '.';
            }

            // Check for endless loops

            $current_question_id = $module->firstQuestion()->id;
            $split_response = 'Y';
            while ($loop_check < 500) {
                $current_question = new ModuleQuestion;
                $current_question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
                if ($current_question->where('id', $current_question_id)->first()->question_type == 'split_y_n') {
                    $split = new ModuleQuestionSplit();
                    $split->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionSplit'));
                    $result = $split->where('question_id', $current_question_id)->where('split_criteria_operator', ucwords($split_response))->first()->split_result;
                    if (!$split->where('question_id', $current_question_id)->where('split_criteria_operator', 'Y')->count() || !$split->where('question_id', $current_question_id)->where('split_criteria_operator', 'N')->count()) {
                        $errors[] = 'Split Error on Module: ' . $module->module_name . ', Question ID: ' . $current_question_id;
                        break;
                    }
                    if ($result === 0 && $split_response == 'Y') {
                        $split_response = 'N';
                        $current_question_id = $module->firstQuestion()->id;
                        $loop_check++;
                    } else if ($result === 0) {
                        break;
                    } else {
                        $current_question_id = $result;
                        $loop_check++;
                    }
                } else {
                    $next = $current_question->where('id', $current_question_id)->first()->next_question;
                    if ($next === 0 && $split_response == 'Y') {
                        $split_response = 'N';
                        $current_question_id = $module->firstQuestion()->id;
                        $loop_check++;
                    } else if ($next === 0) {
                        break;
                    } else {
                        $current_question_id = $next;
                        $loop_check++;
                    }
                }
            }

            if ($loop_check >= 500) {
                $errors[] = 'LOOP Error on Module: ' . $module->module_name . ', Question ID: ' . $current_question_id;
            }
        }

        return $errors;
    }

}
