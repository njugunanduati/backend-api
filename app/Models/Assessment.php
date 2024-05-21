<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\FinancialResponse;
use App\Models\ModuleQuestionResponse;
use App\Models\User;
use Carbon\Carbon;
use Cache;

class Assessment extends Model
{
	protected $table = 'assessments';
	public $timestamps = true;

	use SoftDeletes;

	protected $dates = ['deleted_at'];

    protected $fillable = ['allow_percent', 'percent_added', 'monthly_coaching_cost', 'add_review_meetings', 'add_planning_meetings', 'planning_meetings'];

    protected $casts = ['implementation_start_date' => 'datetime:Y-m-d'];

	public function __toString() {
		return $this->name . ' - ' . $this->company->company_name;
	}

	/**
     * Set the assessment's name.
     *
     * @param  string  $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trimSpecial(strip_tags($value));
		
    }

	public function company() {
		return $this->belongsTo(Company::class,'company_id');
    }

    public function moduleSet()
    {
        return $this->belongsTo(ModuleSet::class,'module_set_id');
	}

	public function users() {
		return $this->belongsToMany('App\Models\User')->withPivot('view_rights', 'edit_rights', 'report_rights');
    }

    public function user()
    {
        return $this->users()->wherePivot('view_rights', '=', 1)
							 ->wherePivot('edit_rights', '=', 1)
							 ->wherePivot('report_rights', '=', 1)
							 ->first();
    }

    public function priorities()
    {
        return $this->hasMany(Priority::class);
    }

    public function rpm()
    {
        return $this->hasOne(RpmDial::class);
	}

	public function actualRevenues()
	{
	    return $this->hasMany(ActualRevenue::class);
	}

	public function trails()
	{
	    return $this->hasMany(AssessmentTrail::class);
	}

	public function monthlyCoachingCosts()
	{
	    return $this->hasMany(MonthlyCoachingCost::class);
	}

	public function impCoaching()
	{
	    return $this->hasMany(ImpCoaching::class);
	}
	
	public function impSettings()
	{
	    return $this->hasOne(ImpSettings::class);
	}

	public function prioritiesQuestionnaire()
    {
		$owner = $this->user();
		// Check if the owner of this assessment has access to quotum leap
		// OR this assessment is not a quotum leap assessment
		if(($this->quotum_assessment == 0) || ($owner->trainingAccess->quotum_access != 1)){
			return null;
		}else{
			return DB::table('priorities_questionnaire')->where('company_id', $this->company_id)->first();
		}
	}

	public function sessions()
	{
	    return $this->hasMany(Session::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class,'currency_id');
	}
	
	public function owner()
	{
		return DB::table('assessment_user')
                         ->where('assessment_id', $this->id)
		         		 ->where('view_rights', 1)
		         		 ->where('edit_rights', 1)
		         		 ->where('report_rights', 1)
                     	 ->first();
	}

    function defaultCurrency(){
        $default = Currency::where('symbol','LIKE','USD')->first();
        return $this->belongsTo(Currency::class,'currency_symbol')->withDefault([
            'id' => $default->id,
            'name' => $default->name,
            'code' => $default->code,
            'symbol' => $default->symbol,
            "created_at"=> $default->created_at,
            "updated_at"=> $default->updated_at,
            "deleted_at"=> $default->deleted_at
        ]);
    }

	public function percentageCompleted() {
		$result = Cache::tags('assessment_'.$this->id)->rememberForever('assessment_perc_completed_'.$this->id, function() {
			$module = new Module('m_advertising_questions');
			$modules = $module->module_set($this->module_set_id);
            $modules_percentage = 0;
            $complete_modules = [];
			$complete_percentage = [];
			foreach ($modules as $module) {
                $modules_percentage += $module->percentageCompleted($this->id);
                    if($module->percentageCompleted($this->id) != 0){
                        $complete_modules [] = $module->module_name;
                        $complete_percentage [] = $module->percentageCompleted($this->id);
                    }

            }
			
            // Make Financial module to be the first on the list of complete modules
            $index = array_search('Financial', $complete_modules);
            if($index){
            	unset($complete_modules[$index]);
            	array_unshift($complete_modules, 'Financial');
            }

            $complete = array('percentage' => round($modules_percentage / count($modules)),'modules' => $complete_modules,'percentages' => $complete_percentage);
			return $complete;
		});
		return $result;
	}

	public function currentRevenue() {
		$question_id = 3;

		$response = new ModuleQuestionResponse;
        $response->setTable(Helper::module_name_to_full_table_name('Financial', 'QuestionResponse'));
        if ($response->where('question_id', $question_id)->where('assessment_id', $this->id)->count()) {
        	$result = $response->where('question_id', $question_id)->where('assessment_id', $this->id)->first()->response;
        } else {
        	$result = 0;
        }

        return round($result, 2);
	}

	public function netProfitMargin() {
		$question_id = 2;

		$response = new ModuleQuestionResponse;
        $response->setTable(Helper::module_name_to_full_table_name('Financial', 'QuestionResponse'));
        if ($response->where('question_id', $question_id)->where('assessment_id', $this->id)->count()) {
        	$result = $response->where('question_id', $question_id)->where('assessment_id', $this->id)->first()->response;
        } else {
        	$result = 0;
        }

        return floatval($result);
	}

	public function grossProfitMargin() {
		$question_id = 1;

		$response = new ModuleQuestionResponse;
        $response->setTable(Helper::module_name_to_full_table_name('Financial', 'QuestionResponse'));
        if ($response->where('question_id', $question_id)->where('assessment_id', $this->id)->count()) {
        	$result = $response->where('question_id', $question_id)->where('assessment_id', $this->id)->first()->response;
        } else {
        	$result = 0;
        }

        return floatval($result);
	}

	// same as currentProfit
	public function netProfit() {
		$revenue = $this->currentRevenue();
		$margin = $this->netProfitMargin();
		if ($revenue > 0 && $margin > 0) {
			$result = $revenue * ($margin / 100);
		} else {
			$result = 0;
		}

        return round($result, 2);
	}

	public function grossProfit() {
		$revenue = $this->currentRevenue();
		$margin = $this->grossProfitMargin();
		if ($revenue > 0 && $margin > 0) {
			$result = $revenue * ($margin / 100);
		} else {
			$result = 0;
		}

        return round($result, 2);
	}

	public function variableCost(){
		$revenue = $this->currentRevenue();
		$profit = $this->grossProfit();

		$result = $revenue - $profit;
		return round($result, 2);
	}

	public function fixedCost(){
		$revenue = $this->currentRevenue();
		$profit = $this->netProfit();
		$cost = $this->variableCost();

		$result = $revenue - $cost - $profit;
		return round($result, 2);
	}

	public function breakEvenPoint(){
		$cost = $this->fixedCost();
		$margin = $this->grossProfitMargin();
		
		if ($cost > 0 && $margin > 0) {
			$result = $cost / ($margin / 100);
		}else{
			$result = 0;
		}
		
		return round($result, 2);
	}

	public function currentExpenses()
    {
		$revenue = $this->currentRevenue();
		$profit = $this->netProfit();
		$result = $revenue - $profit;

		return round($result, 2);
	}

	public function cumulativeExpectedIncrease($id){
		
		$module_id = $this->moduleSet->id; 

		if($module_id == 7){  // Jumpstart40
			if($id){
				$prices_impact = Cache::tags('assessment_'.$id)->get('module_impact_prices_assessment_'.$id);
				return $prices_impact + Helper::getMaxIncrease($id);
			}else{
				$prices_impact = Cache::tags('assessment_'.$this->id)->get('module_impact_prices_assessment_'.$this->id);
				return $prices_impact + Helper::getMaxIncrease($this->id);
			}
		}else{
			return ($id)? Helper::getMaxIncrease($id) : Helper::getMaxIncrease($this->id);
		}
	}

	public function expectedIncreaseRevenue(){

		$revenue = $this->currentRevenue();

		if($this->module_set_id == 5){
			$a = Helper::calculateExpectedIncreaseRevenue($this->id);
			$temp = $a * floatval($revenue);
			$prices_impact = Cache::tags('assessment_'.$this->id)->get('module_impact_prices_assessment_'.$this->id);
			$prices = (floatval($prices_impact) / 100) * floatval($revenue);
			$result = $temp + $prices;
		}else{
			$increase = $this->cumulativeExpectedIncrease($this->id);
			if ($increase > 0 && $revenue > 0) {
				$result = floatval($revenue) * (floatval($increase)) / 100;
			}else{
				$result = 0;
			}
		}

		return round($result, 2); 
		
	}

	public function newAnnualGrossRevenue(){
		$revenue = $this->currentRevenue();
		$increase = $this->expectedIncreaseRevenue();

		$result = (floatval($revenue) + floatval($increase));
		return round($result, 2);
	}

	public function revenueImpact(){

		$revenue = $this->currentRevenue();
		$increase = $this->expectedIncreaseRevenue();

		if ($increase > 0 && $revenue > 0) {
			$result = (floatval($increase) / floatval($revenue)) * 100;
		}else{
			$result = 0;
		}
		
		return round($result, 2);
	}

	public function expectedIncreaseGrossProfit($i = 0){

		$margin = $this->grossProfitMargin();
		$increase = ($i > 0)? $i : $this->expectedIncreaseRevenue();

		if ($increase > 0 && $margin > 0) {
			$result = ((floatval($margin) / 100) * floatval($increase));
		}else{
			$result = 0;
		}
		
		return round($result, 2);
	}

	public function getFixedCostImpactAmount($impact){
		$cost = $this->fixedCost();

		if ($cost > 0 && $impact > 0) {
			$result = floatval($cost) * (floatval($impact) / 100);
		}else{
			$result = 0;
		}
		return round($result, 2);
    }

    public function getVariableCostImpactAmount($impact){
		$cost = $this->variableCost();
		
		if ($cost > 0 && $impact > 0) {
			$result = floatval($cost) * (floatval($impact) / 100);
		}else{
			$result = 0;
		}
		return round($result, 2);
    }

    public function processCostAmount($module) {
		$assessment_id = $this->id;
        $result = Cache::tags('assessment_'.$assessment_id)->remember($module->module_path.'_amount_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module) {
            $questions = new ModuleQuestion;
            $questions->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
			$amount = 0;
            foreach($questions->where('question_type', 'impact')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                $responses_table = new ModuleQuestionResponse();
                $responses_table->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionResponse'));
                if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
					$value = $responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response;
					if(intval($question->id) == 5){
						$amount += $this->getFixedCostImpactAmount($value);
					}
                    if(intval($question->id) == 10){
						$amount += $this->getVariableCostImpactAmount($value);
					}
                }
            }
            
            return $amount;
        });
        return $result;
	}

	public function processCostImpact($module) {
		$assessment_id = $this->id;
        $result = Cache::tags('assessment_'.$assessment_id)->remember($module->module_path.'_impact_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module) {
            $questions = new ModuleQuestion;
            $questions->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
			$impact = 0;
            foreach($questions->where('question_type', 'impact')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                $responses_table = new ModuleQuestionResponse();
                $responses_table->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionResponse'));
                if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
					$value = $responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response;
					$impact += floatval($value);
                }
            }
            
            return $impact;
        });
        return $result;
	}

	public function getImpactAmount($path, $impact) {
		$revenue = $this->currentRevenue();
		$expense = Helper::getModuleExpense($path);
		// Add expense to impact percentage
		$actual_impact = ($impact > 0) ? (1 - (floatval($expense) / 100)) * floatval($impact) : 0;

		$amount = ($actual_impact > 0) ? (floatval($actual_impact) / 100) * floatval($revenue) : 0;

		return round($amount, 2);
	}

	public function processPriceAmount($module) {
		$assessment_id = $this->id;
        $result = Cache::tags('assessment_'.$assessment_id)->remember($module->module_path.'_amount_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module) {
            
            $questions = new ModuleQuestion;
            $questions->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
			$amount = 0;
            foreach($questions->where('question_type', 'impact')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                $responses_table = new ModuleQuestionResponse();
                $responses_table->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionResponse'));
                if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
					$value = $responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response;
					$amount += $this->getImpactAmount($module->module_path, $value);
                }
            }
            
            return $amount;
        });
        return $result;
	}
	
	public function getRestOfIncreasePriceImpact(){
		$margin = $this->grossProfitMargin();
		$amount = Cache::tags('assessment_'.$this->id)->get('prices_amount_assessment_'.$this->id);
		
		if (floatval($amount) > 0) {
		  $rem = 100 - floatval($margin);
		  $result = ((floatval($rem) / 100) * floatval($amount));
		  return round($result, 2);
		}
		return $amount;
	}

	public function getCutCostImpact(){
		$impact = Cache::tags('assessment_'.$this->id)->get('costs_impact_assessment_'.$this->id);
		$revenue = $this->currentRevenue();
		$margin = $this->netProfitMargin();
		if(floatval($revenue) > 0 && floatval($margin) > 0){
			$result = (floatval($revenue) * (1 - (floatval($margin) / 100))) * ((floatval($impact) / 100));
		}else{
			$result = 0;
		}

		return round($result,2);
	}

	public function totalProfitImpact(){
		
		$amount = Cache::tags('assessment_'.$this->id)->get('costs_amount_assessment_'.$this->id);
		$increase = $this->expectedIncreaseGrossProfit();
		$result = floatval($amount) + floatval($increase);
		
		$remainder = $this->getRestOfIncreasePriceImpact();
		return (round($result, 2) + floatval($remainder));
	}

	public function newAnnualProfit(){
		$profit = $this->netProfit();
		$impact = $this->totalProfitImpact();
		$result = floatval($profit) + floatval($impact);
		return round($result, 2);
	}

	public function fiveYearProfitImpact(){
		$impact = $this->totalProfitImpact();
		$result = floatval($impact) * 5;
		return round($result, 2);
	}

	public function actualPercentageImpact(){
		
		$profit = $this->netProfit();
		$impact = $this->totalProfitImpact();

		if(floatval($impact) > 0 && floatval($profit) > 0){
			$result = ((floatval($impact) / floatval($profit)) * 100);
		}else{
			$result = 0;
		}

		return round($result, 2);
	}

	public function monthlyGain(){
		
		$revenue = $this->currentRevenue();
		$annual = $this->newAnnualGrossRevenue();

		if(floatval($annual) > 0 && floatval($revenue) > 0){
			$result = (floatval($annual) / floatval($revenue));
			$result = (($result ** (1 / 12)) - 1) * 100;
		}else{
			$result = 0;
		}
		
		return round($result, 2);
	}

	public function profitImpactCostReduced(){
		$profit = $this->netProfit();
		$amount = Cache::tags('assessment_'.$this->id)->get('costs_amount_assessment_'.$this->id);
		$result = floatval($profit) + floatval($amount);
		return round($result, 2);
	}

	public function processAllModuleImpact(){
		$module = new Module('m_advertising_questions');
		$modules = $module->module_set($this->module_set_id);
		
		$invalid_modules = array('financials', 'introduction', 'foundational', 'valuation');
		
		foreach ($modules as $module) {

			if (!in_array($module->module_path, $invalid_modules)) {
				$module->processImpact($this->id);
				if($module->module_path == 'costs'){
					$this->processCostAmount($module);
					$this->processCostImpact($module);
				}
				if($module->module_path == 'prices'){
					$this->processPriceAmount($module);
				}
			}

			if($module->module_path == 'valuation'){
				$this->getValuationResponses($module);
			}
		}
	}

	public function processAllModuleIncrease(){
		
		$module = new Module('m_advertising_questions');
		$modules = $module->module_set($this->module_set_id);
		
		$invalid_modules = array('financials', 'introduction', 'foundational', 'costs', 'valuation');
		
		foreach ($modules as $module) {
			if (!in_array($module->module_path, $invalid_modules)) {
				$id = $this->moduleSet->id;
				if(($id == 1) || ($id == 2)){ // Digital Acceleration Jumpstart OR Digital Acceleration Deep Dive
					Helper::processDigitalIncrease($this->id, $module->module_path);
				}else if(($id == 3) || ($id == 4)){ // Free Profit Acceleration Jumpstart 12 OR Profit Acceleration Jumpstart 12
					Helper::processJS12Increase($this->id, $module->module_path);
				}else if($id == 7){ // Profit Acceleration Jumpstart 40
					Helper::processJS40Increase($this->id, $module->module_path);
				}else{
					Helper::processIncrease($this->id, $module->module_path);
				}
			}
		}
	}

	public function getValuationResponses($module) {
		$assessment_id = $this->id;
        $result = Cache::tags('assessment_'.$assessment_id)->remember($module->module_path.'_responses_assessment_'.$assessment_id, 5, function() use ($assessment_id, $module) {
            
            $questions = new ModuleQuestion;
            $questions->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));
			$responses = [];
            foreach($questions->where('question_type', '!=', 'blank')->get() as $question) {
                $question->setTable(Helper::module_name_to_full_table_name($module->module_name, 'Question'));

                $responses_table = new ModuleQuestionResponse();
                $responses_table->setTable(Helper::module_name_to_full_table_name($module->module_name, 'QuestionResponse'));
                if ($responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->count()) {
					$value = $responses_table->where('assessment_id', $assessment_id)->where('question_id', $question->id)->first()->response;
					$responses[] = $value;
                }
            }
            
            return $responses;
        });
        return $result;
	}

	public function addImpactManually($module_name, $percent, &$count, $path) {
		$questions = new ModuleQuestion;
		$questions->setTable(Helper::module_name_to_full_table_name($module_name, 'Question'));
		
		foreach($questions->where('question_type', 'impact')->get() as $question) {
			$responses_table = new ModuleQuestionResponse();
			$responses_table->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionResponse'));

			// Add or update impact on all the modules
			$responses_table->updateOrCreate(['assessment_id' => $this->id, 'question_id' => $question->id],['response' => $percent]);
			$count++;
			
			// Should fill the 2 impact questions/responses for the cut cost module
			if($path != 'costs'){
				break;
			}
			
		}
	}

	public function confirmFinancialsExists() {
		$results = FinancialResponse::where('assessment_id', $this->id)->get();
		if(count($results) > 0){
			return true;
		}
		return false;
	}

	public function confirmModuleHasImpact($module_name){
		$status = false;
		$questions = new ModuleQuestion;
		$questions->setTable(Helper::module_name_to_full_table_name($module_name, 'Question'));
		
		foreach($questions->where('question_type', 'impact')->get() as $question) {
			$responses_table = new ModuleQuestionResponse();
			$responses_table->setTable(Helper::module_name_to_full_table_name($module_name, 'QuestionResponse'));
			if ($responses_table->where('assessment_id', $this->id)->where('question_id', $question->id)->count() > 0) {
				$status = true;
			}
		}
		
		return $status;
	}

	public function calculateBusinessValuation($type, $revenue, $margin, $responses){
		$annualRevenue = floatval($revenue);
		$grossProfitMargin = floatval($margin) / 100;

		if (!$responses) {
			return 0;
		}

		$one = floatval($responses[9]) / 100;
		$two = floatval($responses[8]) / 100;
		$three = floatval($responses[7]); // inventory sales
		$four = ($responses[6] == 'y') ? 1.123459876 : 0.92349876;
		$five = ($responses[5] == 'y') ? 0.9 : 1.1;
		$six = ($responses[4] == 'y') ? 1.1 : 0.9;
		$seven = ($responses[3] == 'y') ? 1.1 : 0.75;
		$eight = ($responses[2] == 'y') ? 1.1 : 0.9;
		$nine = ($responses[1] == 'y') ? 1.1 : 0.75;
		$ten = ($responses[0]) ? floatval($responses[0]) : 0; // business valued at...

		$result = ($annualRevenue * $grossProfitMargin * 2 * $one * $two * $four * $five * $six * $seven * $eight * $nine) + $three;
		$cost = $this->getCutCostImpact();
		$timesThree = ($type == 'start') ? 0 : floatval($cost) * 3;

		$sum = ($result + $timesThree);

		$valuation = ($ten + $sum) / 2;

		return round($valuation, 2);
	}

	public function startingValuation(){
		$responses = Cache::tags('assessment_'.$this->id)->get('valuation_responses_assessment_'.$this->id);
		
		$revenue = $this->currentRevenue();
		$margin = $this->grossProfitMargin();
		if(count($responses) > 0){
			return $this->calculateBusinessValuation('start', $revenue, $margin, $responses);
		}else{
			return round($revenue, 2);
		}
		
	}

	public function potentialValuation(){
		$responses = Cache::tags('assessment_'.$this->id)->get('valuation_responses_assessment_'.$this->id);
		$revenue = $this->newAnnualGrossRevenue();
		$margin = $this->grossProfitMargin();

		if(count($responses) > 0){
			return $this->calculateBusinessValuation('potential', $revenue, $margin, $responses);
		}else{
			return round($revenue, 2);
		}
	}

}
