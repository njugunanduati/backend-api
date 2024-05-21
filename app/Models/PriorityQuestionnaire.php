<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriorityQuestionnaire extends Model
{
    protected $table = 'priorities_questionnaire';

    public $timestamps = true;

	use SoftDeletes;

	protected $dates = ['deleted_at'];

	protected $fillable = ['user_id','company_id', 'q1', 'q1b', 'q2', 'q3', 'q4', 'q5', 'recommendation'];

	public function company() {
		return $this->belongsTo(Company::class,'company_id');
	}

    public function user() {
		return $this->belongsTo(User::class,'user_id');
	}

    public function js_12_expertise()
	{
		return DB::table('js_12_expertise')->where('user_id', $this->user_id)->first();
	}

    public function js_40_expertise()
	{
		return DB::table('js_40_expertise')->where('user_id', $this->user_id)->first();
	}

    public function digital_expertise()
	{
		return DB::table('digital_expertise')->where('user_id', $this->user_id)->first();
	}

    public function sales_expertise()
	{
		return DB::table('sales_expertise')->where('user_id', $this->user_id)->first();
	}
}
