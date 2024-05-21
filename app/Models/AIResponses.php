<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Relations\HasMany;


class AIResponses extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'suggestions';

    protected $fillable = ['description','business','howtos'];
    
    public function getIdAttribute($value = null)
    {
        return $value;
    }

    public function howtos()
    {
        return $this->embedsMany(AIHowTos::class);
    }
}
