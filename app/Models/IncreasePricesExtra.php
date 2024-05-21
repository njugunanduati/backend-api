<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncreasePricesExtra extends Model
{

    public $timestamps = true;
    protected $table = 'increase_prices_extra';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['assessment_id', 'current_customer_number', 'leaving_customer_number', 'may_happen'];


    public function assessment() {
		return $this->belongsTo(Assessment::class);
	}
}
