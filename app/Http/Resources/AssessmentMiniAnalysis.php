<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class AssessmentMiniAnalysis extends JsonResource
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
            'company_id' => $this->company_id,
            'company_name' => $this->company->company_name,
            'expected_increase_revenue' => $this->expectedIncreaseRevenue(),
            'total_profit_impact' => $this->totalProfitImpact(),
            'actual_percentage_impact' => $this->actualPercentageImpact(),
            'current_revenue' => $this->currentRevenue(),
            'current_profit' => $this->netProfit(),
            'monthly_coaching_cost' => ((int)$this->monthly_coaching_cost > 0)? (int)$this->monthly_coaching_cost: 0,
            ];

    }
}
