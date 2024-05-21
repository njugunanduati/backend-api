<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveCampaignIntegration extends Model {

	protected $table = 'active_campaign_integrations';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'url', 'api_key' ,'is_active'];

    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

}