<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class AIResponseHistory extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'response_history';

    protected $fillable = ['user_id', 'response_id', 'question_id', 'assessment_id', 'path'];
    
    public function getIdAttribute($value = null)
    {
        return $value;
    }

    public function response(){
        return $this->belongsTo(AIResponses::class,'responses_id');
    }
}
