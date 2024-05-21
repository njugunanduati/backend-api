<?php
namespace App\Models;


use App\Helpers\Helper;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Models\ModuleQuestionOption;
use App\Models\ModuleQuestionResponse;
use Illuminate\Database\Eloquent\Model;

class ModuleMeta extends Model
{

    protected $table = 'm_alliances_meta';
    public $timestamps = false;
    public $module;
}
