<?php

namespace App\Models;

use Cache;
use App\Helpers\Helper;
use App\Models\Priority;
use App\Models\ModuleSet;
use App\Models\Assessment;
use App\Models\ModuleMeta;
use App\Models\ModuleQuestion;
use App\Models\ModuleSetModule;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Models\ModuleQuestionOption;
use App\Models\ModuleQuestionResponse;

class Module {

	public function __construct($module) {
    	$this->table_name = $module;
    	$module_name_exploded = explode("_", $module);
        $module_name = '';
        foreach ($module_name_exploded as $stub) {
        	if ($stub != "questions") {
        		$module_name = $module_name . ucfirst($stub);
        	}
        }
    	$this->module_name = substr($module_name, 1);
        $this->table_base = 'm_' . $module;
        $details = ModuleSetModule::where('module_name', $this->module_name)->first();
        $this->module_path = $details->path;
        $this->module_alias = $details->module_alias;
        $this->module_type = $details->type;
        $this->module_order = $details->order;
        $this->module_expense = Helper::getModuleExpense($this->module_path);
    }

    public function percentageCompleted($assessment_id) {
        $result = Cache::tags('assessment_'.$assessment_id)->rememberForever('module_perc_complete_module_'.$this->module_name.'_assessment_'.$assessment_id, function() use ($assessment_id) {
            $percentage_complete = 0;
            if($this->complete($assessment_id)) {
                return 100;
            }

            $responses_table = new ModuleQuestionResponse();
            $responses_table->setTable(Helper::module_name_to_full_table_name($this->module_name, 'QuestionResponse'));
            if (!$responses_table->where('assessment_id', $assessment_id)->count()) {
                    return 0;
            }

            if ($this->module_name =='Financial' || $this->module_name =='Valuation' || $this->module_name =='Coreintro' || $this->module_name =='DigitalIntroduction') {
                $average_questions=1;
            }else{

                $meta_table = new ModuleMeta();
                $meta_table->setTable(Helper::module_name_to_meta_table_name($this->module_name));

                if ($meta_table->where('id', '!=', '0')->count()) {
                    $average_questions = $meta_table->where('id', '!=', '0')->first()->average_questions;
                } else {
                    return 0;
                }

            }
            
            $completion = ($average_questions/$responses_table->where('assessment_id', $assessment_id)->count())*100;
            if ($completion > 90) {
                $percentage_complete = 90;
            } else {
                $percentage_complete = round($completion,0);
            }

            return $percentage_complete;
        });
        return $result;
	}

    public static function module_names() {
        $result = Cache::tags('module_data')->remember('module_names', 1440, function() {
    		$db_modules = \DB::select('SHOW TABLES');
    	    $modules = [];
    	    foreach ($db_modules as $db_module) {
                foreach ($db_module as $key => $table) {
                    if (substr($table, 0, 2) == "m_" && strpos($table, 'questions')) {
                        $module_name_exploded = explode("_", $table);
                        $module_name = '';
                        foreach ($module_name_exploded as $stub) {
                            if ($stub != "questions") {
                                $module_name = $module_name . ucfirst($stub);
                            }
                        }
                        $real_module_name = substr($module_name, 1);
                        $modules[] = $real_module_name;
                    }
    	        }
    	    }
    	    return $modules;
        });
        return $result;
	}

	public function firstQuestion() {
        $result = Cache::tags('module_data')->remember('first_question_module_'.$this->module_name, 1440, function() {
    		$question_table = Helper::module_name_to_full_table_name($this->module_name, 'Question');
    		$question = new ModuleQuestion();
            $question->setTable($question_table);

        	$first_question_id = \DB::table($question_table)->select('*')
        		->from($question_table)
        		->whereNotIn('id', function($query) use ($question_table) {
        			$query->select('next_question')->from($question_table);
        		})->whereNotIn('id', function($query) use ($question_table) {
        			$query->select('split_result')
        				->from(Helper::switch_table_section($question_table, 'split'));
                })->first()->id;
                
        	return $question->where('id', $first_question_id)->first();
        });
        return $result;
    }

	public function all() {
        $result = Cache::tags('module_data')->remember('all_modules', 1440, function() {
    		$db_modules = \DB::select('SHOW TABLES');
    	    $modules = [];
    	    foreach ($db_modules as $db_module) {
                foreach ($db_module as $key => $table) {
        	        if (substr($table, 0, 2) == "m_" && strpos($table, 'questions')) {
                        $module = new Module($table);
                        $meta_table = new ModuleMeta();
                        $meta_table->setTable(Helper::module_name_to_meta_table_name($module->module_name));
                        if ($meta_table->where('id', '!=', '0')->count()) {
                            $module_weight = $meta_table->where('id', '!=', '0')->first()->module_weight;
                        } else {
                            $module_weight = 0;
                        }
        	        	$modules[$module_weight] = new Module($table);
        	        }
                }
    	    }
            ksort($modules);
    	    return $modules;
        });
        return $result;
	}

    public function module_set($module_set_id) {
        $result = Cache::tags('module_data')->remember('module_set_'.$module_set_id, 2440, function() use ($module_set_id) {
            $module_set_modules = ModuleSetModule::where('module_set_id', $module_set_id)->get();
            $modules = [];
            foreach ($module_set_modules as $module_set_module) {
                $module = new Module(Helper::module_name_to_full_table_name($module_set_module->module_name, 'Question'));
                $meta_table = new ModuleMeta();
                $meta_table->setTable(Helper::module_name_to_meta_table_name($module->module_name));
                if ($meta_table->where('id', '!=', '0')->count()) {
                    $module_weight = (int)$meta_table->where('id', '!=', '0')->first()->module_weight;
                } else {
                    $module_weight = 0;
                }
                $modules[] = new Module(Helper::module_name_to_full_table_name($module_set_module->module_name, 'Question'));
            }
            ksort($modules);
            return $modules;
        });
        return $result;
    }

	public function complete($assessment_id) {
        $result = Cache::tags('assessment_'.$assessment_id)->rememberForever('module_completed_module_'.$this->module_name.'_assessment_'.$assessment_id, function() use ($assessment_id) {
    		$responses_table = new ModuleQuestionResponse();
            $responses_table->setTable(Helper::module_name_to_full_table_name($this->module_name, 'QuestionResponse'));
            if ($responses_table->where('assessment_id', $assessment_id)->count() == 0) {
            	return false;
            }
            $response = $responses_table->where('assessment_id', $assessment_id)->orderBy('updated_at', 'desc')->first();
            $response->setTable(Helper::module_name_to_full_table_name($this->module_name, 'QuestionResponse'));
            $response_question = new ModuleQuestion;
            $response_question->setTable(Helper::module_name_to_full_table_name($this->module_name, 'Question'));
            $response_question = $response_question->where('id', $response->question_id)->first();

            if($response_question->question_type == 'split_y_n') {
                if ($response->splitResult() == 0) {
                    return true;
                }
            } else {
                if ($response_question->next_question == 0) {
                    return true;
                }
            }
            return false;
        });
        return $result;
	}

	public function impactPercentage($assessment_id) {
        $result = Cache::tags('assessment_'.$assessment_id)->rememberForever('module_impact_perc_module_'.$this->module_name.'_assessment_'.$assessment_id, function() use ($assessment_id) {
            $impact = 1;

            if($this->module_name == 'SalesForce') {
                $sales_force_modules = array(
                    'm_sales_force_training_questions', 
                    'm_sales_force_trade_shows_questions', 
                    'm_sales_force_superstar_questions', 
                    'm_sales_force_sales_manager_questions', 
                    'm_sales_force_prospecting_and_list_questions', 
                    'm_sales_force_order_fulfillment_questions', 
                    'm_sales_force_general_questions', 
                    'm_sales_force_dream_clients_questions', 
                    'm_sales_force_dealing_with_dm_questions', 
                    'm_sales_force_compensation_questions', 
                    'm_sales_force_closing_the_sale_questions', 
                    'm_sales_force_buyers_remorse_questions');
                foreach ($sales_force_modules as $module_table) {
                    $questions = new ModuleQuestion;
                    $questions->setTable($module_table);
                    foreach($questions->where('question_type', 'impact')->get() as $question) {
                        $question->setTable($module_table);

                        $responses_table = new ModuleQuestionResponse();
                        $responses_table->setTable(Helper::switch_table_section($module_table, 'response'));
                        if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
                            $impact *= (1+($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response/100));
                        }
                    }
                }
            } else if ($this->module_name == 'DigitalCutCosts' || $this->module_name == 'CutCosts' || $this->module_name == 'Corecutcosts') {
                $cut_costs_modules = array('m_corecutcosts_questions', 'm_cut_costs_questions', 'm_digital_cut_costs_questions');
                foreach ($cut_costs_modules as $module_table) {
                    $questions = new ModuleQuestion;
                    $questions->setTable($module_table);
                    foreach($questions->where('question_type', 'impact')->get() as $question) {
                        $question->setTable($module_table);

                        $responses_table = new ModuleQuestionResponse();
                        $responses_table->setTable(Helper::switch_table_section($module_table, 'response'));
                        if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
                            $impact *= (1+($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response/100));
                        }
                    }
                }
            } else {
                $questions = new ModuleQuestion;
                $questions->setTable(Helper::module_name_to_full_table_name($this->module_name, 'Question'));
                foreach($questions->where('question_type', 'impact')->get() as $question) {
                    $question->setTable(Helper::module_name_to_full_table_name($this->module_name, 'Question'));

                    $responses_table = new ModuleQuestionResponse();
                    $responses_table->setTable(Helper::module_name_to_full_table_name($this->module_name, 'QuestionResponse'));
                    if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
                        $impact *= (1+($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response/100));
                    }
                }
            }
            $impact = round(($impact-1)*100,2);

            return $impact;
        });
        return $result;
    }
    
    public function processImpact($assessment_id) {
        $result = Cache::tags('assessment_'.$assessment_id)->remember('module_impact_'.$this->module_path.'_assessment_'.$assessment_id, 5, function() use ($assessment_id) {
            $impact = 0;
            $questions = new ModuleQuestion;
            $questions->setTable(Helper::module_name_to_full_table_name($this->module_name, 'Question'));
            foreach($questions->where('question_type', 'impact')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($this->module_name, 'Question'));

                $responses_table = new ModuleQuestionResponse();
                $responses_table->setTable(Helper::module_name_to_full_table_name($this->module_name, 'QuestionResponse'));
                if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
                    $impact += $responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response;
                }
            }
            
            return $impact;
        });
        return $result;
    }


    public function impactRevenue($assessment_id) {
        if ($this->module_path != 'costs') {
            return round(Assessment::find($assessment_id)->currentRevenue() * ($this->impactPercentage($assessment_id)/100));
        } else {
            return 0;
        }
    }

    public function impactProfit($assessment_id) {
        if ($this->module_path != 'costs') {
            $revenue_increase = $this->impactRevenue($assessment_id);
            $gross_margin = Assessment::find($assessment_id)->grossProfitMargin();

            return ($revenue_increase * ($gross_margin/100));
        } else {
            $annual_revenue = Assessment::find($assessment_id)->currentRevenue();
            $net_margin = Assessment::find($assessment_id)->netProfitMargin();

            return ($annual_revenue*(1-($net_margin/100)))*($this->impactPercentage($assessment_id)/100);
        }
    }

    public function prettyName() {
        $module_class = new ModuleMeta();
        $module_class->setTable(Helper::module_name_to_meta_table_name($this->module_name));
        $meta_data = $module_class->where('id', '!=', '0')->first();
        return $meta_data->module_name;
    }

    public function priorityCost($assessment_id) {
        if (Priority::where('assessment_id', $assessment_id)->where('module_name', $this->module_name)->count()) {
            $priority = Priority::where('assessment_id', $assessment_id)->where('module_name', $this->module_name)->first();
            return $priority->cost;
        }
        return 0;
    }

    public function priorityTime($assessment_id) {
        if (Priority::where('assessment_id', $assessment_id)->where('module_name', $this->module_name)->count()) {
            $priority = Priority::where('assessment_id', $assessment_id)->where('module_name', $this->module_name)->first();
            return $priority->time;
        }
        return 0;
    }

    public function actions() {
        $actions = new ModuleAction();
        $actions->setTable(Helper::switch_table_section($this->table_name, 'action'));
        return $actions->orderBy('weight')->get();
    }
}
