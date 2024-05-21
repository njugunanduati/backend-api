<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserResourceFavorite extends Model {

	protected $table = 'user_resource_favorites';
	public $timestamps = true;

	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'resource_id', 'is_favorite'];

}