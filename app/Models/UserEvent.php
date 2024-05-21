<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEvent extends Model {

	protected $table = 'user_events';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['event_id', 'user_id', 'access'];

	public function user() {
        return $this->belongsTo(User::class);
    }
}
