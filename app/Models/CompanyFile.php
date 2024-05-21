<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyFile extends Model
{

    protected $table = 'company_files';
    public $timestamps = true;
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'user_type',
        'url',
        'key',
        'size',
        'type'
    ];

    public function __toString()
    {
        return $this->name;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
