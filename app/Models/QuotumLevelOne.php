<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class QuotumLevelOne extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'quotum_level_one';

    protected $primaryKey = '_id';

    protected $fillable = ['module','path', 'step', 'status', 'parent_id', 'description'];

    protected $dates = ['created_at','updated_at','deleted_at'];

    public $timestamps = true;

    public function children()
    {
        return $this->hasMany(QuotumLevelTwo::class, 'parent_id', '_id');
    }

}
