<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserModuleAccessMetaData extends Model 
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_module_access_meta_data';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'module_name',
        'description',
        'changed_by',
        'changed_at'
    ];

}
