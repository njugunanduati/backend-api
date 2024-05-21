<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeadGenLastActivity extends Model
{

    protected $table = 'leadgen_last_activity';
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

    public function getHumanPageAttribute()
    {
        return Str::ucfirst($this->page);
    }

    public function getHumanTabAttribute()
    {
        switch($this->tab){
            case 'pre':
                $tab = 'Pre Event';
            break;
            case 'during':
                $tab = 'During The Event';
            break;
            case 'post':
                $tab = 'Post Event';
            break;
        }
        return Str::ucfirst($tab);
    }

    public function getHumanStepAttribute()
    {
        return Str::headline($this->step);
    }
}
