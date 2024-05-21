<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachingActionStep extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'coaching_action_steps';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['company_id', 'assessment_id', 'path', 'status'];

    public function company() {
		  return $this->belongsTo(Company::class, 'company_id');
    }

    public function assessment() {
		  return $this->belongsTo(Assessment::class, 'assessment_id');
    }

}
