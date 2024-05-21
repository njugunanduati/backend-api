<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\ModuleQuestion;
use Illuminate\Database\Eloquent\Model;

class ModuleQuestionOption extends Model
{

    public $timestamps = true;
    protected $table = 'm_alliances_questions';

    public function question()
    {
        $question = new ModuleQuestion();
        $question->setTable(Helper::switch_table_section($this->table, 'question'));
        return $question->where('id', $this->question_id);
    }

    public function questionText()
    {
        $question = new ModuleQuestion();
        $question->setTable(Helper::switch_table_section($this->table, 'question'));
        return $question->where('id', $this->question_id)->first()->question_text;
    }
}
