<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\ModuleQuestion;
use App\Models\ModuleQuestionSplit;
use Illuminate\Database\Eloquent\Model;

class ModuleQuestionResponse extends Model
{

    public $timestamps = true;
    protected $table = 'm_alliances_questions';
    protected $fillable = ['question_id', 'assessment_id', 'response'];

    /**
     * Set the assessment's responses.
     *
     * @param  string  $value
     * @return void
     */
    public function setResponseAttribute($value)
    {
        $this->attributes['response'] = trimSpecial(strip_tags($value));
		
    }

    public function question()
    {
        $question = new ModuleQuestion();
        $question->setTable(Helper::switch_table_section($this->table, 'question'));
        return $question->where('id', $this->question_id);
    }

    public function splitResult()
    {
        $split = new ModuleQuestionSplit();
        $split->setTable(Helper::switch_table_section($this->table, 'split'));
        $split = $split->where('question_id', $this->question_id)->where('split_criteria_operator', ucwords($this->response))->first();
        $result = 0;
        if($split){

            $result = $split->split_result;

        }
        return $result;
    }

    public function questionText()
    {
        $question = new ModuleQuestion();
        $question->setTable(Helper::switch_table_section($this->table, 'question'));
        return $question->where('id', $this->question_id)->first()->question_text;
    }
}
