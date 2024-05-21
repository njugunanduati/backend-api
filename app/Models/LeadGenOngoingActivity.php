<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadGenOngoingActivity extends Model
{

    protected $table = 'leadgen_ongoing_activity';
    protected $fillable = [
        "user_id",
        "step",
        "status",
        "adate",
        "page",
        "tab",
    ];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}