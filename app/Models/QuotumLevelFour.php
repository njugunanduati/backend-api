<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class QuotumLevelFour extends Model
{
    use SoftDeletes;
    protected $connection = 'mongodb';

    protected $collection = 'quotum_level_four';

    protected $primaryKey = '_id';

    protected $fillable = ['description', 'status', 'parent_id'];

    protected $dates = ['created_at','updated_at','deleted_at'];

    public $timestamps = true;

    public function parent()
    {
        return $this->belongsTo(QuotumLevelThree::class, 'parent_id');
    }

}
