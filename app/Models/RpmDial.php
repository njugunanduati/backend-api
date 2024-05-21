<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RpmDial extends Model
{
    use SoftDeletes;

     /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'rpm_dial_responses';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'assessment_id', 'success_factor'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function assessment() {

		return $this->belongsTo(Assessment::class);
	}

}
