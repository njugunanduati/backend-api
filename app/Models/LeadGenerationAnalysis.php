<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadGenerationAnalysis extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'leadgen_video_analysis';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'company_id', 'path', 'video_id', 'video_name', 'video_progress', 'video_time_watched', 'video_length', 'notes'];

    public function company() {
		  return $this->belongsTo(Company::class,'company_id');
    }

    public function user() {
		  return $this->belongsTo(User::class,'user_id');
    }

    public function setVideoTimeWatchedAttribute($value){
        
        
        if(!isset($this->attributes['video_time_watched']) || $value>(float)$this->attributes['video_time_watched']){
            $this->attributes['video_time_watched']=$value;
        }
    }

}
