<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeIntegration extends Model {

	protected $table = 'stripe_integrations';
	public $timestamps = true;

  protected $fillable = [
        'user_id',
        'stripe_id',
  ];

  public function user() {
    return $this->belongsTo(User::class,'user_id');
  }
}
