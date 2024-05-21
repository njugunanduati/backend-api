<?php
namespace App\Models;

use App\Helpers\Helper;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Models\ModuleQuestionOption;
use App\Models\ModuleQuestionResponse;
use Illuminate\Database\Eloquent\Model;

class ModuleAction extends Model
{

    protected $table = 'm_alliances_actions';
    public $timestamps = false;
    public $module;

    public function moduleName()
    {
        $table = $this->table;
        $module_name_exploded = explode("_", $table);
        $module_name = '';
        foreach ($module_name_exploded as $stub) {
            if ($stub != "actions") {
                $module_name = $module_name . ucfirst($stub);
            }
        }
        $real_module_name = substr($module_name, 1);
        return $real_module_name;
    }
}
