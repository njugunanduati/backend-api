<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentPercentage extends Model
{

    public $timestamps = true;
    protected $table = 'assessment_percentage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['assessment_id', 'modules'];


    public function assessment() {
      return $this->belongsTo(Assessment::class);
    }
}
