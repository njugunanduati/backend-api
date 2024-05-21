<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $fillable = ['path','module','question','alias','client_question'];
}
