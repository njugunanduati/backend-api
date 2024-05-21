<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Helpers\ModulePriority;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModuleSetModule;

class Priority extends Model
{
    protected $table = 'priorities';

	protected $fillable = ['assessment_id', 'module_name', 'time', 'order'];

	public function assessment() {
		return $this->belongsTo('App\Models\Assessment','assessment_id');
	}

    public function getImpactPercentageAttribute()
    {
        return $this->getModule()->impactPercentage($this->assessment_id);
	}

    public function getImpactProfitAttribute()
    {
        return $this->getModule()->impactProfit($this->assessment_id);
	}

    public function getModule()
    {
        return new Module(Helper::module_name_to_base_table_name($this->module_name));
    }

    public function getPriorityAttribute()
    {
        return ModulePriority::calculate($this->impact_profit, $this->cost, $this->time, $this->module_name);
    }

	public function getPath()
	{
		$name = $this->module_name;

        if($name == 'Sales'){
			return 'sales';
		}
		$obj = ModuleSetModule::where('module_name', $name)->first();
		return ($obj)? $obj->path : null;
	}

    public function getAlias()
	{
		$name = $this->module_name;

        if($name == 'Sales'){
			return 'Sales';
		}
		$obj = ModuleSetModule::where('module_name', $name)->first();
		return ($obj)? $obj->module_alias : null;
	}
}
