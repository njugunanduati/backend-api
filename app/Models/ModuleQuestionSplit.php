<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use App\Models\ModuleQuestion;
	use App\Helpers\Helper;

	class ModuleQuestionSplit extends Model {

		public $timestamps = true;
		protected $table = 'm_alliances_questions';

	    public function question() {
	    	$question = new ModuleQuestion();
	    	$question->setTable(Helper::switch_table_section($this->table, 'question'));
	    	return $question->where('idate(format)', $this->question_id);
	    }

	    public function questionText() {
	    	$question = new ModuleQuestion();
	    	$question->setTable(Helper::switch_table_section($this->table, 'question'));
	    	return $question->where('id', $this->question_id)->first()->question_text;
        }

    /**
     * Get the .
     */
    public function splits()
    {
        return $this->belongsTo('App\Models\ModuleQuestion');
    }

	}
