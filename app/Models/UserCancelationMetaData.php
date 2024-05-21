<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserCancelationMetaData extends Model 
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_cancelation_meta_data';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','canceled_by', 'canceled_at'];

}
