<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Relations\HasMany;


class AIHowTos extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'howtos';

    protected $fillable = ['description'];
    public function getIdAttribute($value = null)
    {
        return $value;
    }
}
