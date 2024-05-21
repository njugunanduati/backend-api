<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentMaxiAnalysis extends JsonResource
{

    
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;
        $this->processAllModuleImpact();
    }
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->processAllModuleIncrease();
        return [
            'assessment_id' => $this->id,
            'assessment_name' => $this->name,
            'company_id' => $this->company_id,
            'company_name' => $this->company->company_name,
            'module_set_name' => $this->moduleSet->name,
            'cumulative_expected_increase' => $this->cumulativeExpectedIncrease($this->id),
            'expected_increase_revenue' => $this->expectedIncreaseRevenue(),
            'new_annual_gross_revenue' => $this->newAnnualGrossRevenue(),
            'revenue_impact' => $this->revenueImpact(),
            'total_profit_impact' => $this->totalProfitImpact(),
            'new_annual_profit' => $this->newAnnualProfit(),
            'five_year_profit_impact' => $this->fiveYearProfitImpact(),
            'actual_percentage_impact' => $this->actualPercentageImpact(),
            'monthly_gain' => $this->monthlyGain(),
            'profit_impact_cost_reduced' => $this->profitImpactCostReduced(),
            'gross_profit_margin' => $this->grossProfitMargin(),
            'net_profit_margin' => $this->netProfitMargin(),
            'current_revenue' => $this->currentRevenue(),
            'current_profit' => $this->netProfit(),
            'gross_profit' => $this->grossProfit(),
            'variable_cost' => $this->variableCost(),
            'fixed_cost' => $this->fixedCost(),
            'current_expenses' => $this->currentExpenses(),
            'break_even_point' => $this->breakEvenPoint(),
            'starting_valuation' =>$this->startingValuation(),
            'potential_valuation' =>$this->potentialValuation(),
            'monthly_coaching_cost' => ((int)$this->monthly_coaching_cost > 0)? (int)$this->monthly_coaching_cost: 0,
            ];

    }
}
