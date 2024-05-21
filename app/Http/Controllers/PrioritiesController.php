<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use App\Helpers\Helper;
// cypher
use App\Helpers\Cypher;
use App\Http\Requests;
use App\Models\Priority;
use App\Models\Assessment;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\Priorities as PrioritiesResource;
use App\Http\Resources\AssessmentMaxiAnalysis as AssessmentAnalysis;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Cache;

class PrioritiesController extends Controller
{
    use ApiResponses;
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
                'assessment_id' => 'required',
                'modules' => 'required',
            ];

            $messages = [
                'assessment_id.required' => 'An assessment is required',
                'modules.required' => 'Modules list is required',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $cypher = new Cypher;
            $assessment_id = intval($cypher->decryptID(env('HASHING_SALT'), $request->input('assessment_id')));

            $assessment = Assessment::findOrFail($assessment_id);

            if($assessment){
                foreach ($request->input('modules') as $key => $item) {
                    $array = [
                        'time' => $item['time'],
                        'order' => $item['order']
                    ];

                    $priority = $assessment->priorities()->updateOrCreate([
                        'assessment_id' => $assessment_id,
                        'module_name' => $item['module_name'],
                    ], $array);
                }
            }
            
            return $this->singleMessage('Priority saved', 201);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Assessment not Found', 404);
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

        try {
            $response = Priority::findOrFail($id);

            $transform = new PrioritiesResource($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Priority does not exist', 200);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getByAssessmentId($id)
    {

        try {

            $assessment = Assessment::findOrFail($id);

            $response = $assessment->priorities()->where('time', '>', 0)->get()->sortBy('order');

            $transform = PrioritiesResource::collection($response);

            return $this->successResponse($transform, 200);

        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('Assessment does not exist', 200);
        }
    }


    /**
     * Get the assessment implementation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function prioritiesImplementation($id){
        try {
            $cypher = new Cypher;
            $id = intval($cypher->decryptID(env('HASHING_SALT'), $id));

            $assessment = Assessment::findOrFail($id);

            $assessment->processAllModuleImpact();
            $assessment->processAllModuleIncrease();
            
            $gross_profit_margin = $assessment->grossProfitMargin();
            $current_revenue = $assessment->currentRevenue();
            $current_profit = $assessment->netProfit();
            $prices_impact = Cache::tags('assessment_'.$assessment->id)->get('module_impact_prices_assessment_'.$assessment->id);
            $cost_amount = Cache::tags('assessment_'.$assessment->id)->get('costs_amount_assessment_'.$assessment->id);

            function getCostImpact($a, $b){
                if ($b == 0) {
                    return 0;
                }
                return (floatval($a) / floatval($b)) * 100;
            };

            $analysis = (object)[
                'gross_profit_margin' => $gross_profit_margin,
                'current_revenue' => $current_revenue,
                'prices_impact' => $prices_impact,
            ];
            
            $p = $assessment->priorities()->where('time', '>', 0)->get()->sortBy('order');
            $priorities = PrioritiesResource::collection($p);
            $list = $mtime = $array = [];
            $planning_meetings = (int)$assessment->planning_meetings;
            $planning_status = (bool)$assessment->add_planning_meetings;
            $review_status = (bool)$assessment->add_review_meetings;

            $list = json_decode(json_encode($priorities));

            if($planning_status){

                if ($planning_meetings > 0) {
                    $meeting = (object)[
                        'id' => uniqid(),
                        'path' => 'planning-meeting',
                        'time' => $planning_meetings,
                        'module_alias' => 'Initial Planning Meeting',
                    ];

                    $list = [$meeting, ...$list];
                }
            }

            if ($review_status) {
                $cnt = 1;
                // Break the modules list to have a single week each
                // e.g if MDP has 3 weeks, break it into 3 modules with a time of 1 week each
                    
                foreach ($list as $key => $m) {
                    
                    $module = clone($m);

                    $t = (int)$module->time;
                    if ($t > 1) {
                        $i = 1;

                        while ($i <= $t) {
                            $module->time = 1;
                            $mtime[] = $module;
                            $i++;
                        }

                    }else{
                       $mtime[] = $module; 
                    }
                }

                // Add the quarterly review meetings on every 12th week
                foreach ($mtime as $k => $a) {
                    if($k > 0){
                        $r = ($k + 1) % 12;
                        if ($r == 0) {
                            $m = (object) [
                                'id' => uniqid(),
                                'path' => 'quarterly-review',
                                'time' => 1,
                                'module_alias' => 'Quarterly Review',
                            ];
                            array_splice($mtime, $k, 0, [$m]);
                        }

                    }
                }
                
                // Consolidate the modules list adding it to the final list 
                foreach ($mtime as $b => $n) {
                    $a = clone($n);
                    $iset = isset($mtime[$b + 1]);

                    $paths[] = $a->path;
                        
                    if($iset){
                        $next = clone((object)$mtime[$b + 1]);
                        $status = ($a->path == $next->path);
                        if (!$status) {
                            $time = ($cnt == 0) ? 1 : $cnt;
                            $a->time = $time;
                            $array[] = $a;
                            $cnt = 1;
                        }else{
                           $cnt = $cnt + 1;
                        }
                    }else{
                        $time = ($cnt == 0) ? 1 : $cnt;
                        $a->time = $time;
                        $array[] = $a;
                    }
                }
                
            }else{
                $array = [...$list];
            }

            $start_date = ($assessment->implementation_start_date)? formatDate($assessment->implementation_start_date): getImplementationStartDate($assessment->created_at);

            // Add start and end dates
            foreach ($array as $index => $each) {
                $result = getStartEndDate($start_date, $array, $index);
                $each->startdate = $result->startdate;
                $each->enddate = $result->enddate;
            }

            // Get the 1 year (52 weeks) implementation modules
            $annual = getAnnualPriorities($array);

            // Add impact increase with expence
            foreach ($annual as $index => $e) {
                $increase = (($e->path == 'quarterly-review') || ($e->path == 'planning-meeting'))? 0 : ($e->path == 'costs')? getCostImpact($cost_amount,$current_profit) : Helper::addExpense($e->path, Helper::getModuleImpact($e->path,$assessment->id));
                $e->increase = $increase;
            }

            foreach ($array as $index => $e) {
                $increase = (($e->path == 'quarterly-review') || ($e->path == 'planning-meeting'))? 0 : ($e->path == 'costs')? getCostImpact($cost_amount,$current_profit) : Helper::addExpense($e->path, Helper::getModuleImpact($e->path,$assessment->id));
                $e->increase = $increase;
            }

            $currentRevenue = $assessment->currentRevenue();
            $profitMargin = $assessment->grossProfitMargin();


            $cleaned = cleanUpAnnualModules($annual);

            $revenueArray = [];
            $profitArray = [];

            foreach ($cleaned as $index => $j) {
                // We dont add cost impact to the revenue
                if($j->path != 'costs'){
                   $revenueArray[] = 1 + (floatval($j->increase) / 100);
                }
                $profitArray[] = 1 + (floatval($j->increase) / 100);
            }

            $revenue = floatval($currentRevenue) * array_product($revenueArray);
            $revenueIncrease = round(floatval($revenue) - floatval($currentRevenue), 2);
            
            $profit = $assessment->totalProfitImpact();
            $currentRevenue = $assessment->currentRevenue();
            $profitMargin = $assessment->grossProfitMargin();

            $cleaned = cleanUpAnnualModules($annual);

            $revenueArray = [];
            $profitArray = [];

            foreach ($cleaned as $index => $j) {
                // We dont add cost impact to the revenue
                if($j->path != 'costs'){
                   $revenueArray[] = 1 + (floatval($j->increase) / 100);
                }
                $profitArray[] = 1 + (floatval($j->increase) / 100);
            }

            foreach ($array as $index => $b) {
               if(($b->path != 'costs') && ($b->path != 'quarterly-review') && ($b->path != 'planning-meeting')){
                    $amount = $b->increase / 100 * floatval($currentRevenue);
                    $b->revenue_amount = round($amount, 2);
                    $profit_amount = round(($b->path == 'prices') ? floatval($amount) : (floatval($gross_profit_margin) / 100) * floatval($amount), 2);
                    $b->new_annual_profit_amount = round($current_profit + $profit_amount, 2);
               }else{
                    $b->new_annual_profit_amount = round($current_profit + $cost_amount, 2);
                    $b->revenue_amount = 0;
               }
            }

            foreach ($annual as $index => $b) {
               if(($b->path != 'costs') && ($b->path != 'quarterly-review') && ($b->path != 'planning-meeting')){
                    $amount = $b->increase / 100 * floatval($currentRevenue);
                    $b->revenue_amount = round($amount, 2);
                    $profit_amount = round(($b->path == 'prices') ? floatval($amount) : (floatval($gross_profit_margin) / 100) * floatval($amount));
                    $b->new_annual_profit_amount = round($current_profit + $profit_amount, 2);
               }else{
                    $b->new_annual_profit_amount = round($current_profit + $cost_amount, 2);
                    $b->revenue_amount = 0;
               }
            }
           
            $revenue = floatval($currentRevenue) * array_product($revenueArray);
            $revenueIncrease = round(floatval($revenue) - floatval($currentRevenue), 2);
             
            $profit = $this->annualProfitImpact($assessment, $revenueIncrease, $analysis);

            $other_details = [
                'gross_profit_margin' => $gross_profit_margin, 
                'current_revenue' => $current_revenue, 
                'current_profit' => $current_profit, 
                'prices_impact' => $prices_impact,
                'cost_amount' => $cost_amount, 
            ];

            return ['annualProfit' => round($profit,2), 'annualRevenue' => round($revenue,2), 'implementation' => $array, 'annual' => $annual, 'other' => $other_details];
            
        } catch (ModelNotFoundException $ex) {
            return $this->errorResponse('This assessment does not exist', 404);
        }
    }

    public function annualProfitImpact($assessment, $revenueIncrease, $analysis){
        
        if($assessment->module_set_id == 5){
            
            $a = (floatval($analysis->gross_profit_margin) / 100) * floatval($analysis->current_revenue);
            $b = Helper::calculateExpectedIncreaseRevenue($assessment->id);
            $c = $a * $b;

            $costs_amount = Cache::tags('assessment_'.$assessment->id)->get('costs_amount_assessment_'.$assessment->id);
            
            $prices = (floatval($analysis->prices_impact) / 100) * floatval($analysis->current_revenue);
            
            $result = $c + $costs_amount + $prices;
            return round($result, 2);

        }else{
            $amount = Cache::tags('assessment_'.$assessment->id)->get('costs_amount_assessment_'.$assessment->id);
            $increase = $assessment->expectedIncreaseGrossProfit($revenueIncrease);
            $result = floatval($amount) + floatval($increase);
            $remainder = $assessment->getRestOfIncreasePriceImpact();
            return (round($result, 2) + floatval($remainder));
        }
    }

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
    {
        try {

            $validator = Validator::make($request->all(), [
                'time' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 400);
            }

            $priority = Priority::findOrFail($id);

            $priority->update([
                'time' => $request->input('time')
            ]);


            $transform = new PrioritiesResource($priority);

            return $this->successResponse($transform, 200);
        } catch (Exception $ex) {

            return $this->errorResponse('Priority not found', 404);
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
        try {
            $priority = Priority::findOrFail($id);

            $priority->delete(); //Delete the priority
            return $this->singleMessage('Priority Deleted', 201);
        }
        // catch(Exception $e) catch any exception
        catch (ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {

                return $this->errorResponse('Priority not found', 400);
        }

        }
    }

}
