<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model {

	protected $table = 'resources';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'resource_type_id',
        'lesson_id',
        'url',
    ];


	public function resourcetypes() {
        return $this->belongsTo(ResourceType::class, 'resource_type_id');
    }

    public function lesson() {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    /**
     * Set the description. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = trimSpecial(strip_tags($value));
    }
    
}
