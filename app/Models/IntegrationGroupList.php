<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IntegrationGroupList extends Model {

	protected $table = 'integration_group_lists';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['list_id', 'group_id', 'integration_id'];

    public function group()
    {
        return $this->belongsTo(UserGroup::class,'group_id');
	}

    public function integration()
    {
        return $this->belongsTo(Integration::class,'integration_id');
	}

    public function groupDetails() {
        return DB::table('user_groups')->where('id', $this->group_id)->first();
    }

    public function integrationDetails() {
        return DB::table('integrations')->where('id', $this->integration_id)->first();
    }
}
