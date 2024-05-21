<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;
use Jenssegers\Mongodb\Relations\HasMany;


class Suggestion extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $fillable = ['path','module','question','alias','client_question'];

    public function responses()
    {
        return $this->embedsMany(AIResponses::class);
    }

    public function getIdAttribute($value = null)
    {
        return $value;
    }
}
