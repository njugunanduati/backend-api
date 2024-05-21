<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\ModuleQuestionNote;
use App\Models\ModuleQuestionSplit;
use App\Models\ModuleQuestionOption;
use App\Models\ModuleQuestionComment;
use App\Models\ModuleQuestionResponse;
use Illuminate\Database\Eloquent\Model;

class ModuleQuestion extends Model
{

    public $timestamps = true;
    protected $table = 'm_alliances_questions';
    public $module;

    public function note()
    {
        $note = new ModuleQuestionNote();
        $note->setTable(Helper::switch_table_section($this->table, 'note'));
        return $note->where('question_id', $this->id);
    }

    public function comment()
    {
        $comment = new ModuleQuestionComment();
        $comment->setTable(Helper::switch_table_section($this->table, 'comment'));
        return $comment->where('question_id', $this->id);
    }

    public function options()
    {
        $options = new ModuleQuestionOption();
        $options->setTable(Helper::switch_table_section($this->table, 'option'));
        return $options->where('question_id', $this->id);
    }

    public function responses()
    {
        $responses = new ModuleQuestionResponse();
        $responses->setTable(Helper::switch_table_section($this->table, 'response'));
        return $responses->where('question_id', $this->id);
    }

    public function split()
    {
        $split = new ModuleQuestionSplit();
        $split->setTable(Helper::switch_table_section($this->table, 'split'));
        return $split->where('question_id', $this->id);

    }

    public function next_question($id)
    {
        $next_question = $this->where('id', $id)->first()->next_question;
        return $this->where('id', $next_question);
    }

    public function lastQuestion()
    {
        if ($this->question_type == 'split_y_n') {
            return false;
        } else if ($this->next_question == 0) {
            return true;
        } else {
            return false;
        }
    }
}
