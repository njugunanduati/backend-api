<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroupTemplate extends Model {

	protected $table = 'user_group_templates';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['group_id', 'user_id'];

	public function group() {
        return $this->belongsTo(Group::class);
    }

	public function user() {
        return $this->belongsTo(User::class);
    }
}
