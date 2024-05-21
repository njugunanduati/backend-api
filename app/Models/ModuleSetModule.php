<?php

namespace App\Models;
use App\Helpers\Helper;
use App\Models\ModuleMeta;

use Illuminate\Database\Eloquent\Model;

class ModuleSetModule extends Model
{

    protected $table = 'module_set_modules';
    public $timestamps = false;

    public function moduleSet()
    {
        return $this->belongsTo('App\Models\ModuleSet');
    }

    public function meta()
    {
        $meta = new ModuleMeta();
        $meta->setTable(Helper::module_name_to_meta_table_name($this->module_name));
        return $meta->where('module_name', $this->module_name)->first();
    }

    public function scopeOrder($query)
    {
        return $query->orderBy('order', 'ASC');
    }
}
