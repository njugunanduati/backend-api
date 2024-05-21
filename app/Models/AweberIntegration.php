<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AweberIntegration extends Model
{
    //  use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'aweber_integrations';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'auth_code', 'state', 'access_token', 'refresh_token', 'expires_in'];

  public function user() {
    return $this->belongsTo(User::class,'user_id');
  }
}
