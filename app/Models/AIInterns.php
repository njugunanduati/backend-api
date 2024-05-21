<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Relations\HasMany;


class AIInterns extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'interns';

    protected $fillable = ['first_name','last_name','email'];
}