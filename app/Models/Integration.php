<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    // use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'integrations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'mpesa', 'paypal', 'stripe', 'aweber','getresponse', 'active_campaign'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    //protected $hidden = [''];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
	}

    public function lists()
    {
        return $this->hasMany(IntegrationGroupList::class);
	}

    public function stripeDetails() {
        return DB::table('stripe_integrations')->where('user_id', $this->user_id)->first();
    }

    public function aweberDetails() {
        return DB::table('aweber_integrations')->where('user_id', $this->user_id)->first();
    }

    public function activecampaignDetails() {
        return DB::table('active_campaign_integrations')->where('user_id', $this->user_id)->first();
    }

    public function getresponseDetails() {
        return DB::table('getresponse_integrations')->where('user_id', $this->user_id)->first();
    }

}
