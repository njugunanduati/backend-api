<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSimulator extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'unique_url_token',
        'active',
    ];

    protected $dates = ['deleted_at'];
    protected $hidden = ['user_id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // UUID Mutator
    public function setUuidAttribute($value)
    {
        $this->attributes['uuid'] = $this->convertStringUuidToBinary($value);
    }

    // UUID Accessor (Getter)
    public function getUuidAttribute($value)
    {
        return $this->convertBinaryUuidToString($value);
    }

    private function convertStringUuidToBinary($uuidString)
    {
        return pack('H*', str_replace('-', '', $uuidString));
    }

    private function convertBinaryUuidToString($binaryUuid)
    {
        $stringUuid = unpack('H*', $binaryUuid)[1];
        return substr($stringUuid, 0, 8) . '-' . substr($stringUuid, 8, 4) . '-' . substr($stringUuid, 12, 4) . '-' . substr($stringUuid, 16, 4) . '-' . substr($stringUuid, 20);
    }
}
