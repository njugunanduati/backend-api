<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class LiveSearch extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $fillable = ['user_id','question'];
}
