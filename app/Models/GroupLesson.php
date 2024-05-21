<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupLesson extends Model {

	protected $table = 'group_lessons';
	public $timestamps = true;

	protected $fillable = ['group_id', 'lesson_id'];

    public function __toString() {
		return $this->group_id .' '.$this->lesson_id;
	}
}
