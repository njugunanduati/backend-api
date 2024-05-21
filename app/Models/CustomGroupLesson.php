<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomGroupLesson extends Model {

	protected $table = 'custom_group_lessons';
	public $timestamps = true;
	/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'group_id',
      'user_group_id',
      'lesson_id',
      'lesson_order',
      'lesson_length',
      'created_at',
      'updated_at'
    ];

    public function __toString() {
		return $this->group_id .' '.$this->lesson_id;
	}
}
