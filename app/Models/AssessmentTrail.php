<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentTrail extends Model
{

    public $timestamps = true;
    protected $table = 'assessment_trail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['assessment_id', 'path', 'module_name', 'trail'];


    public function assessment() {
      return $this->belongsTo(Assessment::class);
    }
}
