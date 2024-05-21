<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class Path extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $fillable = ['module','path'];
}
