<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserPasswordChangesMetaData extends Model 
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_password_changes_meta_data';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'description',
        'changed_by',
        'changed_date'
    ];

}
