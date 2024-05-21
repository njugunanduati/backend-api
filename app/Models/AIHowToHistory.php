<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class AIHowToHistory extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'howto_history';

    protected $fillable = ['user_id', 'howto_id', 'question_id', 'assessment_id', 'response_id', 'path'];
    
    public function getIdAttribute($value = null)
    {
        return $value;
    }
    
}
