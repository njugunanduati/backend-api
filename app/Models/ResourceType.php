<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourceType extends Model {

	protected $table = 'resource_types';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'owner',
        'order',
    ];


	public function resources() {
        return $this->hasMany(Resource::class);
    }
    
	

}
