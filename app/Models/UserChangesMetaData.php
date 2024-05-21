<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserChangesMetaData extends Model 
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_changes_meta_data';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'current_value', 'new_value', 'column_name', 'description',
        'changed_by','changed_date'
    ];

}
