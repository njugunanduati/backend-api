<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GetResponseIntegration extends Model
{
    //  use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'getresponse_integrations';
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

   /**
     * Set the auth_code. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setAuthCodeAttribute($value)
    {
        $this->attributes['auth_code'] = trimSpecial(strip_tags($value));
		
    }
     /**
     * Set the full_desc. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = trimSpecial(strip_tags($value));
		
    }

     /**
     * Set the refresh_token. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setRefreshTokenAttribute($value)
    {
        $this->attributes['refresh_token'] = trimSpecial(strip_tags($value));
		
    }
}
