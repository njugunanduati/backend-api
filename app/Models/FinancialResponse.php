<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Models\ModuleQuestion;
use Illuminate\Database\Eloquent\Model;

class FinancialResponse extends Model
{
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'm_financial_question_responses';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['question_id', 'assessment_id', 'response'];

    public function assessment() {
		  return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function question()
    {
        $question = new ModuleQuestion();
        $question->setTable(Helper::switch_table_section($this->table, 'question'));
        return $question->where('id', $this->question_id);
    }

}
